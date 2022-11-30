<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    /**
     * get all authenticated user info
     * @return Response|Application|ResponseFactory
     */
    public function edit(): Response|Application|ResponseFactory
    {
        return response(new UserResource(Auth::user(), 3));
    }

    /**
     * show user information
     * @param $username
     * @return Application|ResponseFactory|Response
     */
    public function show($username): Response|Application|ResponseFactory
    {
        $user = User::whereUsername($username)->first();
        if ($user) {
            return response(new UserResource($user, 2));
        } else {
            return response([
                'message' => 'No such data in our records'
            ], 406);
        }
    }

    /**
     * @param UserRequest $request
     * @return Response|Application|ResponseFactory
     */
    public function update(UserRequest $request): Response|Application|ResponseFactory
    {
        $input = $request->only('dob', 'name', 'about', 'headline', 'address', 'country_id', 'city_id');
        $user = $request->user()->update($input);
        if ($request->get('topics')) {
            $user->topics()->sync($request->get('topics'));
        }
        return response(new UserResource($user, 3));
    }

    /**
     * get user answers
     * @param $username
     * @return Response|Application|ResponseFactory
     */
    public function getAnswers($username): Response|Application|ResponseFactory
    {
        $user = User::whereUsername($username)->firstOrFail();
        $answers = $user->answers()->paginate()->through(function (Answer $answer) {
            return new AnswerResource($answer);
        });
        return response($answers->items());
    }

    /**
     * get questions
     * @param $username
     * @return Response|Application|ResponseFactory
     */
    public function getQuestions($username): Response|Application|ResponseFactory
    {
        $user = User::whereUsername($username)->firstOrFail();
        $questions = $user->questions()->paginate()->through(function (Question $question) {
            return new QuestionResource($question);
        });
        return response($questions->items());
    }

    public function removeTopic(Request $request)
    {
        $request->user()->topics()->detach([$request->get('topics')]);
        return response([
            'message' => 'Topic has been successfully removed',
            'user' => new UserResource($request->user())
        ]);
    }

    /**
     * @param Request $request
     * @return Response|Application|ResponseFactory
     */
    public function addTopic(Request $request): Response|Application|ResponseFactory
    {
        $validator = Validator::make($request->all(),
            ['topic_name' => 'required|max:100']
        );
        if ($validator->fails()) {
            return response([
                'message' => $validator->errors()->first()
            ], 400);
        }
        $topicName = $request->get('topic_name');
        $topic = new Topic();
        $topic->name = ['en' => $topicName, 'fr' => $topicName];
        $topic->save();
        $request->user()->topics()->sync([$topic->id]);
        return response(new UserResource($request->user(), 3));
    }
}
