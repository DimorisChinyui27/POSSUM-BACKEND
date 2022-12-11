<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserTopic;
use App\Models\Wallet;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * login to the system
     * @param LoginRequest $request
     * @return Application|ResponseFactory|JsonResponse|Response
     */
    public function login(LoginRequest $request): Response|JsonResponse|Application|ResponseFactory
    {
        $credentials = request(['email', 'password']);
        if (!$token = auth()->attempt($credentials)) {
            return response(['message' => 'Invalid credentials'], 401);
        }
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => new UserResource(User::whereEmail($request->get('email'))->first()),
            'message' => 'User has successfully login',
            'expires_in' => auth()->factory()->getTTL() * 600
        ]);

    }

    /**
     * logout the user
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * register user account
     * @param RegisterRequest $request
     * @return Application|ResponseFactory|Response
     */
    public function register(RegisterRequest $request): Response|Application|ResponseFactory
    {
        $user = new User();
        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->password = Hash::make($request->get('password'));
        $user->username = generateUsername($request->get('email'));
        $user->save();
        $user->attachRole('user');
//        $user->notify(new VerifyEmail());
        return response([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    /**
     * complete registration to activate your account
     * @param CompleteRegistrationRequest $request
     * @return Application|ResponseFactory|Response
     */
    public function completeRegistration(CompleteRegistrationRequest $request): Response|Application|ResponseFactory
    {
        $user = Auth::user();
        $user->dob = $request->get('dob');
        $user->headline = $request->get('headline');
        foreach ($request->get('topics') as $topic) {
            if (Topic::whereId((int)$topic)->exists()) {
                if (!UserTopic::whereUserId($user->id)->where('topic_id', $topic)->exists()) {
                    $userTopic = new UserTopic();
                    $userTopic->user_id = $user->id;
                    $userTopic->topic_id = $topic;
                    $userTopic->save();
                }
            }
        }
        $user->save();
        if (!$user->wallet) {
            $wallet = new Wallet();
            $wallet->user()->associate($user);
            $wallet->balance = 10000;
            $wallet->wallet_id = generateWalletId();
            $wallet->save();
        }
        return response([
            'user' => new UserResource($user),
            'message' => 'Registration has been successfully completed'
        ]);
    }

    /**
     * @param Request $request
     * @return Redirector|Application|RedirectResponse
     */
    public function verifyEmail(Request $request): Redirector|Application|RedirectResponse
    {
        $url = env('FRONT_END_URL', '');
        $user = User::whereId($request->get('id'))->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();
        }
        return redirect($url);
    }

    /**
     * @return Response|Application|ResponseFactory
     */
    public function resentVerificationEmail(): Response|Application|ResponseFactory
    {
        $user = Auth::user();
        $user->notify(new VerifyEmail());
        return response([
            'message' => 'Please verify your email, we sent a verification link'
        ]);
    }
}
