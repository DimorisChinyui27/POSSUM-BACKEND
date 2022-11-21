<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\UserResource;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\PaymentMethod;
use App\Models\Question;
use App\Models\Transaction;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        if (Question::whereId($request->get('question_id'))->exists()) {
            $input = $request->only('body', 'question_id');
            $input['user_id'] = $request->user()->id;
            $input['satisfy'] = false;
            $answer = Answer::create($input);
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
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function sendGift(Request $request, $id): Response|Application|ResponseFactory
    {
        $min_tip = getSettingsOf('min_tip');
        $max_tip = getSettingsOf('max_tip');
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
            if ($user->hasVoted($answer)) {
                $user->cancelVote($answer);
                $message = 'Vote remove';
            } else {
                $user->vote($answer);
                $message = 'This answer has been voted and will be shown to many people';
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
