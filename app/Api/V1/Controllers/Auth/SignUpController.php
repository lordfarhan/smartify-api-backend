<?php

namespace App\Api\V1\Controllers\Auth;

use Config;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use App\Http\Controllers\UserDetailController;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignUpController extends Controller
{
    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        $user = new User($request->all());
        if(!$user->save()) {
            throw new HttpException(500);
        }

        if(!Config::get('boilerplate.sign_up.release_token')) {
            $id = $user->id;
            $user_detail_controller = new UserDetailController();
            $user_detail_controller->generate($id);

            return response()->json([
                'status' => 'ok',
                'id' => $id
            ], 201);
        }

        $token = $JWTAuth->fromUser($user);
        return response()->json([
            'status' => 'ok',
            'token' => $token
        ], 201);
    }

    public function check(SignUpRequest $request) {
        $email = $request->input('email');
        $user = User::where('email', $email)->pluck('id')->first();
        if ($user != null) {
            return response()->json([
                'status' => 'error'
            ]);
        } else {
            return response()->json([
                'status' => 'ok'
            ]);
        }
    }
}
