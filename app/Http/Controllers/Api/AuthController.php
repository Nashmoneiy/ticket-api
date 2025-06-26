<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function register(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|max:191|unique:users',
            'password' => 'required|min:7|max:225'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            if ($user) {
                $token = $user->createToken($user->name. 'Auth-Token')->plainTextToken;
    
            return response()->json([
                'status' => 200,
                'message' => 'registration successful',
                'token_type' => 'Bearer',
                'token' => $token,
                'name' => $user->name
            ], 200);
    
            }else {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong'
            ], 500);
            }
        }
    }

    
     public function login(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required|min:5|max:225'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }else {
            $user = User::where('email', $request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'invalid credentials'
                ],401);
            }
            $token = $user->createToken($user->name. 'Auth-Token')->plainTextToken;
            return response()->json([
    
                'message' => 'login successful',
                'token_type' => 'Bearer',
                'token' => $token,
                'user' => $user->name,
                'status' => 200,
                'role' => $user->role_as =='1'?'admin':'users'
                
            ]);
        }

       
    }

    public function logout() {
    auth()->user()->tokens()->delete();
    return response()->json([
        'status' => 200,
        'message' => 'you are logged out'
    ]);
}
}
