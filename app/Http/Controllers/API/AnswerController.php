<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\PaymentMethod;
use App\Models\Question;
use App\Models\Transaction;
use App\Models\UserTopic;
use App\Services\FileService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use LaravelInteraction\Vote\Vote;

class AnswerController extends Controller
{

    /**
     * show answer details
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function show($id): Response|Application|ResponseFactory
    {
        $answer = Answer::whereId($id)->firstOrFail();
        return response(new AnswerResource($answer));
    }

    /**
     * @param AnswerRequest $request
     * @return Response|Application|ResponseFactory
     */
    public function store(AnswerRequest $request): Response|Application|ResponseFactory
    {
        if ($question = Question::whereId($request->get('question_id'))->first()) {
            $input = $request->only('body', 'question_id');
            $input['user_id'] = $request->user()->id;
            $input['satisfy'] = false;
            $answer = Answer::create($input);
            $request->user()->topics()->sync($question->topics->pluck('id'));
            if ($request->file('files')) {
                (new FileService('answers'))->storeFiles($request->file('files'), $answer);
            }
            return response(new AnswerResource($answer));
        } else {
            return  response([
                'message' => 'No such question'
            ], 406);
        }
    }

    /**
     * @param AnswerRequest $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function update(AnswerRequest $request, $id): Response|Application|ResponseFactory
    {
        $user = $request->user();
        $input = $request->only('body');
        $answer = $user->answers()->where('id', $id)->firstOrFail();
        $answer->update($input);
        return response(new AnswerResource($answer));
    }


    /**
     * satisfy for an answer
     * @param Request $request
     * @param $answerId
     * @return Response|Application|ResponseFactory
     */
    public function satisfy(Request $request, $answerId): Response|Application|ResponseFactory
    {
        if ($answer = Answer::whereId($answerId)->first()) {
            $user = $request->user();
            $transaction = $user->transactions()->where('question_id', $answer->question_id)->where('type', 'GIFT_QUESTION')
                ->where('status', 'pending')->first();
            if ($transaction) {
                $amount = $transaction->amount - ($transaction->amount * getSettingsOf('admin_percentage'));

                $answerWallet = $answer->user->wallet;
                $answerWallet->balance = $answerWallet->balance + $amount;
                $answerWallet->save();

                $transaction->answer_id = $answerId;
                $transaction->status = 'complete';
                $transaction->save();

                $question = $answer->question;
                $question->gift = $question->gift - $transaction->amount;
                $question->save();
                return response([
                    'message' => 'Gift has been send to the user',
                    'answer' => new AnswerResource($answer),
                    'question' => new QuestionResource($question)
                ]);
            } else {
                return response(['message' => 'No such gift transaction '], 406);
            }
        } else {
            return response (['message' => 'No such answer'], 406);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function sendGift(Request $request, $id): Response|Application|ResponseFactory
    {
        $min_tip = (int)getSettingsOf('min_tip');
        $max_tip = (int)getSettingsOf('max_tip');
        $validation = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(PaymentMethod::whereStatus(true)->pluck('code'))],
            'amount' => "required|numeric|min:$min_tip|max:$max_tip",
        ]);
        if ($validation->fails()) {
            return response([
                'message' => $validation->errors()->first(),
                'errors' => $validation->errors()
            ], 400);
        }

        $answer = Answer::whereId($id)->firstOrFail();
        $fee = (double)getSettingsOf('admin_fee') * $request->get('amount');
        $amount = $request->get('amount') ;
        $user = $request->user();
        $wallet = $user->wallet;
        $answerWallet = $answer->user->wallet;

        if ($wallet->balance < $amount) {
            return response([
                'message' => 'Insufficient balance in your account'
            ], 406);
        }

        DB::beginTransaction();
        $id = generateTransactionId();
        $transaction = new Transaction();
        $transaction->type = 'TIP_ANSWER';
        $transaction->fee = $amount *  $fee;
        $transaction->amount = $amount -($amount *  $fee);
        $transaction->user_id = $user->id;
        $transaction->answer()->associate($answer);
        $transaction->transaction_id = $id;
        $transaction->external_id = $id;
        $transaction->payment_method_id = PaymentMethod::whereCode($request->get('payment_method'))->first()->id;
        $transaction->save();

        $wallet->balance = $wallet->balance - $amount;
        $wallet->save();

        $answerWallet->balance = $answerWallet->balance + ($amount * getSettingsOf('answerer_percentage'));
        $answerWallet->save();

        $questionWallet = $answer->question->user->wallet;
        $questionWallet->balance = $answerWallet->balance + ($amount * getSettingsOf('questioner_percentage'));
        $questionWallet->save();
        DB::commit();
        return response([
           'message'=>'Tip has been successfully sent to the user',
            'user' => new UserResource($request->user())
        ]);
    }

    /**
     * vote
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function vote(Request $request, $id): Response|Application|ResponseFactory
    {
        if ($answer = Answer::whereId($id)->first()) {
            $user = $request->user();
            $win = true;
            $question = $answer->question;
            if ($user->hasVoted($answer)) {
                $user->cancelVote($answer);
                $message = 'Vote remove';
                $win = false;
            } else {
                if ($question->answers()->where('user_id', $request->user()->id)->exists()) {
                    return response([
                        'message' => 'You have already voted for another answer'
                    ], 406);
                }
                $user->vote($answer);
                $message = 'This answer has been voted and will be shown to many people';
            }
            $usersTopic = UserTopic::whereIn('topic_id', $answer->question->topics->pluck('id'))
                ->select([ 'user_id', 'id', 'rating','topic_id', 'confidence_score'])->get()->toArray();
            updateRanking($usersTopic, $answer->user_id, $win);
            if ($question->gift == 0.0) {
                $totalAnswerVote = Vote::whereMorphedTo('voteable', $answer)->count();
                if (sqrt($question->answers->count()) >= ($totalAnswerVote * 0.5)) {
                    $question->has_correct_answer = true;
                    $question->save();
                }
            }
            return response([
                'message' => $message,
                'question' => new AnswerResource($answer)
            ]);
        } else {
            return response([
                'message' => 'No such answer exists'
            ], 406);
        }
    }

    /**
     * add a comment to a question
     * @param Request $request
     * @param $id
     * @return Application|ResponseFactory|Response
     */
    public function addComment(Request $request, $id): Response|Application|ResponseFactory
    {
        $validation = Validator::make($request->all(), [
            'comment' => "required"
        ]);
        if ($validation->fails()) {
            return response([
                'message' => $validation->errors()->first(),
                'errors' => $validation->errors()
            ], 400);
        }
        $user = $request->user();
        $answer = Answer::whereId($id)->first();
        if ($answer) {
            $comment = $user->comment($answer, $request->get('comment'));
            return response([
                'message' => 'Your comment has been successfully added',
                'comment' => new CommentResource($comment),
                'answer' => new AnswerResource($answer),
            ]);
        } else {
            return response([
                'message' => 'No such question exists'
            ], 406);
        }
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function getComments(Request $request, $id): Response|Application|ResponseFactory
    {
        if ($answer = Answer::whereId($id)->first()) {
            $comments = $answer->comments()->paginate()->through(function (Comment $comment) {
                return new CommentResource($comment);
            });
            return response($comments->items());
        } else {
            return response([
                'message' => 'No such answer exists',
            ], 406);
        }
    }


    /**
     * delete answer
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function destroy(Request $request, $id): Response|Application|ResponseFactory
    {
        $user = $request->user();
        $answer = $user->answers()->where('id', $id)->firstOrFail();
        $answer->delete();
        return response([
            'message' => 'The answer has been deleted'
        ]);
    }



}
