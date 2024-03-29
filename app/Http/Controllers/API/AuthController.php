<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthController extends Controller
{
    
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|max:255|min:6|confirmed',
        ]);

        if($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        return $this->getResponse($user);

    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        if($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = \request(['email', 'password']);
        if(Auth::attempt($credentials)) {
            $user = $request->user();
            return $this->getResponse($user);
        }
    }

    public function logout(Request $request) {
        $request->user()->token()->revoke();
        return response('Successfully logged out', 200);
    }

    public function user(Request $request) {
        return $request->user();
    }

    public function getResponse(User $user) {
        //TOKEN
        $tokenResult = $user->createToken("Personal Access Token");
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return response([
            'accessToken' => $tokenResult->accessToken,
            'tokenType' => "Bearer",
            'expiresAt' => Carbon::parse($token->expires_at)->toDateTimeString()
        ], 200);
    }

    public function authFailed(){ 
        return response('Unauthenticated', 401);
    }

}
