<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

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

     public function update()
     {
     }
}
