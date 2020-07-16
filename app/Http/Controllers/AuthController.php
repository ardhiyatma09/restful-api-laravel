<?php

namespace App\Http\Controllers;

use App\User;
Use JWTAuth;
Use JWTException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        $credential = [
            'email' => $request->email,
            'password' => $request->password
        ];

        if($user->save())
        {
            $token = null;
            try{
                if(!$token = JWTAuth::attempt($credential))
                {
                    return response()->json([
                        'msg' => 'Email or Password are incorrect',
                    ],404);
                }
            }catch(JWTAuthException $e){
                return response()->json([
                    'msg' => 'failed_to_create_token',
                ],404);
            }

            $user->signin = [
                'href' => 'api/v1/signin',
                'method' => 'POST',
                'params' => 'email, password'
            ];

            $response = [
                'msg' => 'User Created',
                'user' => $user,
                'token' => $token
            ];

            return response()->json($response,201);
        }

        $response = [
            'msg' => 'An Error in create user'
        ];

        return response()->json($response,404);
    }

    public function signin(Request $request)
    {
        $this->validate($request,[
            'email' => 'required',
            'password' => 'required',
        ]);

        $email = $request->email;
        $password = $request->password;

        if($user = User::where('email',$email)->first())
        {
            $credential = [
                'email' => $email,
                'password' => $password
            ];

            $token = null;
            try{
                if(!$token = JWTAuth::attempt($credential)){
                    return response()->json(['msg' => 'Email or password are incorrent'],404);
                }
            }catch(JWTAuthException $e){
                return response()->json(['msg' => 'failed_to_create_token'],404);
            }
            $response = [
                'msg' => 'User signin',
                'user' => $user,
                'token' => $token
            ];
            return response()->json($response, 201,);
        }  

        $response = [
            'msg' => 'An error'
        ];

        return response()->json($response, 404);
    }
}
