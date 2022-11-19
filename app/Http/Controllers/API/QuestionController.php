<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Media;
use App\Models\PaymentMethod;
use App\Models\Question;
use App\Models\Transaction;
use App\Services\FileService;
use App\Traits\FileUploadTrait;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    use FileUploadTrait;

    /**
     * get all user questions
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function index(Request $request): Response|Application|ResponseFactory
    {
        $questions = getQuestions();
        if ($request->get('topics')) {
            $topics = explode(',', $request->get('topics'));
            $questions = $questions->whereHas('topics', function ($q) use ($topics) {
                $q->whereIn('topic_id', $topics);
            });
        }
        if ($request->get('with_gift')) {
            $questions = $questions->where('gift', '>', 0);
        }
        if ($request->get('is_answered')) {
            $questions = $questions->where('has_correct_answer', true);
        }
        $questions = $questions->paginate()->through(function (Question $question) {
            return new QuestionResource($question);
        });
        return response($questions->items());
    }

    /**
     * how question
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function show($id): Response|Application|ResponseFactory
    {
        $question = Question::whereId($id)->first();
        if ($question) {
            return response(new QuestionResource($question));
        } else {
            return response(['message' => 'No such question exists'], 406);
        }
    }

    /**
     * @param QuestionRequest $request
     * @return Application|ResponseFactory|Response
     */
    public function store(QuestionRequest $request): Response|Application|ResponseFactory
    {
        $input = $request->only('title', 'body');
        $input['user_id'] = auth()->id();
        $question = Question::create($input);
        if ($request->file('files')) {
            (new FileService())->storeFiles($request->file('files'), $question);
        }
        $question->topics()->attach($request->get('topics'));
        return response(new QuestionResource($question));
    }

    /**
     * update question details
     * @param QuestionRequest $request
     * @param $question_id
     * @return Response|Application|ResponseFactory
     */
    public function update(QuestionRequest $request, $question_id): Response|Application|ResponseFactory
    {
        $user = $request->user();
        if ($question = $user->questions()->whereId($question_id)->first()) {
            $input = $request->only('title', 'body');
            $question->update($input);
            $question->topics()->sync($request->get('topics'));
            if ($request->file('files')) {
                (new FileService())->storeFiles($request->file('files'), $question);
            }
            return response(['message' => 'Question has been successfully updated',
                'question' => (new QuestionResource($question))]);
        } else {
            return response(['message' => 'No such question'], 406);
        }
    }

    /**
     * delete q question
     * @param Request $request
     * @param $questionId
     * @return Response|Application|ResponseFactory
     */
    public function destroy(Request $request, $questionId): Response|Application|ResponseFactory
    {
        $question = $request->user()->questions()->where('id', $questionId)->first();
        if ($question) {
            if ($question->media->count() > 0) {
                foreach ($question->media as $media) {
                    (new FileService())->unlinkFile($media->file_name);
                    $media->delete();
                }
            }
            $question->delete();
            return response([
                'message' => 'Question has been successfully deleted'
            ]);
        } else {
            return response(['message' => 'No such question exists'], 406);
        }
    }

    /**
     * add Gift to a question
     * @param Request $request
     * @param $question_id
     * @return Application|ResponseFactory|Response
     */
    public function addGift(Request $request, $question_id): Response|Application|ResponseFactory
    {
        $min_tip = getSettingsOf('min_tip');
        $max_tip = getSettingsOf('max_tip');
        $validation = Validator::make($request->all(), [
            'payment_method' => ['required', Rule::in(PaymentMethod::whereStatus(true)->pluck('code'))],
            'amount' => "required|min:$min_tip|max:$max_tip"
        ]);
        if ($validation->fails()) {
            return response([
                'message' => $validation->errors()->first(),
                'errors' => $validation->errors()
            ], 400);
        } else {
            $question = Question::whereId($question_id)->first();
            if (!$question) {
                return response([
                    'message' => 'No such question exists'
                ], 406);
            }
            $amount = $request->get('amount');
            $user = $request->user();
            $wallet = $user->wallet;
            if ($wallet->balance < $amount) {
                return response([
                    'message' => 'Insufficient balance in your POSSUM WALLET',
                ], 406);
            }
            $transaction = $user->transactions()->where('question_id', $question_id)->where('type', 'GIFT_QUESTION')
                ->where('status', 'pending')->first();
            if (!$transaction) {
                $id = generateTransactionId();
                $transaction = new Transaction();
                $transaction->type = 'GIFT_QUESTION';
                $transaction->amount = $amount;
                $transaction->user_id = $user->id;
                $transaction->question_id = $question_id;
                $transaction->transaction_id = $id;
                $transaction->external_id = $id;
                $transaction->payment_method_id = PaymentMethod::whereCode($request->get('payment_method'))->first()->id;
                $transaction->save();
            }
            $question->gift = $question->gift + $amount;
            $question->save();

            $wallet->balance = $wallet->balance - $amount;
            $wallet->save();

            return response([
                'message' => 'Gift has been successfully added to the question',
                'question' => new QuestionResource($question),
                'user' => new UserResource($user)
            ]);
        }
    }

    /**
     * @param Request $request
     * @param $questionId
     * @return Response|Application|ResponseFactory
     */
    public function getComments(Request $request, $questionId): Response|Application|ResponseFactory
    {
        if ($question = Question::whereId($questionId)->first()) {
            $comments = $question->comments()->paginate()->through(function (Comment $comment) {
               return new CommentResource($comment);
            });
            return response($comments->items());
        } else {
            return response([
                'message' => 'No such question exists',
            ], 406);
        }
    }

    /**
     * remove a tip from question
     * @param Request $request
     * @param $questionId
     * @return Response|Application|ResponseFactory
     */
    public function removeGift(Request $request, $questionId): Response|Application|ResponseFactory
    {
        $user = $request->user();
        $transaction = $user->transactions()->where('question_id', $questionId)
            ->where('type', 'GIFT_QUESTION')->where('status', 'pending')->first();
        if ($transaction) {
            $transaction->status = 'cancelled';
            $transaction->save();

            $wallet = $user->wallet;
            $wallet->balance = $wallet->balance + $transaction->amount;
            $wallet->save();

            $question = Question::whereId($questionId)->first();
            $question->gift = $question->gift - $transaction->amount;
            $question->save();

            return response([
                'message' => 'Gift has been successfully removed',
                'user' => new UserResource($user),
                'question' => new QuestionResource($question)
            ]);
        } else {
            return response([
                'message' => 'No such question exists'
            ], 406);
        }
    }

    /**
     * remove a file
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function removeFile(Request $request): Response|Application|ResponseFactory
    {
        $media = Media::whereId($request->get('id'))->first();
        if ($media && $media->mediable->user_id == $request->user()->id) {
            (new FileService())->unlinkFile($media->file_name);
            $media->delete();
            return response([
                'message' => 'The file has been successfully removed'
            ]);
        } else {
            return response([
                'message' => 'No such file'
            ], 406);
        }
    }

    /**
     * upvote successfully upvoted
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function vote(Request $request, $id): Response|Application|ResponseFactory
    {
        if ($question = Question::whereId($id)->first()) {
            $user = $request->user();
            if ($user->hasVoted($question)) {
                $user->cancelVote($question);
                $message = 'Vote remove';
            } else {
                $user->vote($question);
                $message = 'This question has been voted and will be shown to many people';
            }
            return response([
                'message' => $message,
                'question' => new QuestionResource($question)
            ]);
        } else {
            return response([
                'message' => 'No such question exists'
            ], 406);
        }
    }

    /**
     * downvote a question
     * @param Request $request
     * @param $id
     * @return Response|Application|ResponseFactory
     */
    public function downvote(Request $request, $id): Response|Application|ResponseFactory
    {
        if ($question = Question::whereId($id)->first()) {
            $user = $request->user();
            if ($user->hasDownvoted($question)) {
                $user->cancelVote($question);
                $message = 'Downvote remove';
            } else {
                $user->cancelVote($question);
                $user->downvote($question);
                $message = 'This question has been downvoted and will be shown to fewer people';
            }
            return response([
                'message' => $message,
                'question' => new QuestionResource($question)
            ]);
        } else {
            return response([
                'message' => 'no such question exists'
            ], 406);
        }
    }

    /**
     * add a comment to a question
     * @param Request $request
     * @param $question_id
     * @return Application|ResponseFactory|Response
     */
    public function addComment(Request $request, $question_id): Response|Application|ResponseFactory
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
        $question = Question::whereId($question_id)->first();
        if ($question) {
            $comment = $user->comment($question, $request->get('comment'));
            return response([
               'message' => 'Your comment has been successfully added',
                'comment' => new CommentResource($comment),
                'question' => new QuestionResource($question),
            ]);
        } else {
            return response([
                'message' => 'No such question exists'
            ], 406);
        }
    }
}
