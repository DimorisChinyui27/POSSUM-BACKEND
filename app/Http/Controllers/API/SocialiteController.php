<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Jenssegers\Agent\Agent;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{

    /**
     * Get's the default user agent
     * @var Agent
     */
    private $deviceAgent;

    private $home;

    private $login;

    public function __construct()
    {
        $this->deviceAgent = new Agent();
        $this->home = env('FRONT_END_URL');
        $this->login = env('FRONT_END_URL') . '/login';
    }

    /**
     * @param $driver
     * @return Application|ResponseFactory|Response
     */
    public function redirectToProvider($driver): Response|Application|ResponseFactory
    {
        $supportedDrivers = config('services.supported_drivers');
        if (!in_array($driver, $supportedDrivers)) {
            return response([
                'message' => 'No driver found'
            ], 406);
        }
        return response([
            'url' => Socialite::driver($driver)->stateless()->redirect()->getTargetUrl()
        ]);
    }

    public function handleCallback(string $driver)
    {
        try {
            $user = Socialite::driver($driver)->stateless()->user();
            $newUser = $this->findOrCreateUser($driver, (object)$user);
            $tokenResult = $newUser->createToken('AFESHOP_ACCESS_TOKEN');
            $token = $tokenResult->token;
            $token->save();
            return redirect($this->home . "?redirect_id=" . $tokenResult->accessToken);
        } catch (Exception $exception) {
            return redirect($this->login);
        }
    }

    /**
     * @param string $provider
     * @param $user
     * @return User|Builder|Model|object|null
     */
    public function findOrCreateUser(string $provider, $user)
    {
        $email = $user->email ? $user->getEmail() : $user->id . '@' . $provider . '.com';
        if ($authUser = User::whereEmail($email)->first()) {
            return $authUser;
        }
        $authUser = new User();
        $authUser->name = $user->getName();
        $authUser->email = $email;
        $authUser->profile_picture = $user->getAvatar();
        $authUser->email_verified_at = Carbon::now();
        $authUser->os = $this->deviceAgent->platform();
        $authUser->signup_type = $provider;
        $authUser->password = bcrypt('');
        $authUser->save();
        $authUser->attachRole('user');
        return $authUser->refresh();
    }
}
