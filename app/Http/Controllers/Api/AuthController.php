<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Orders;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

public function checkout(Request $request) {
     $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email',
        'phone' => 'required|min:11|max:11',
        'address' => 'required',
        'state' => 'required',
        'total' => 'required|numeric',
        'items' => 'required|array',
      
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->messages(),
        ], 422);
    }
    $amount = $request->total * 100;

    $response = Http::withHeaders([
        'Authorization' => 'Bearer sk_test_5f51876ef5ba1a3ea542c81b04310311fa8a87ba',
        'Content-Type' => 'application/json',
    ])->post('https://api.paystack.co/transaction/initialize', [
        'amount' => $amount,
        'email' => $request->email,
        //'callback_url' => "http://localhost:5173/home",
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),

    ])->json();

    $order = new Orders;
    $order->name = $request->name;
    $order->email = $request->email;
    $order->phone = $request->phone;
    $order->address = $request->address;
    $order->state = $request->state;
    $order->status= 'pending';
    $order->reference = $response['data']['reference'];
    $order->total = $request->total;
    $order->save();
    
  return response()->json([
        'access_code' => $response['data']['access_code'],
        'reference' => $response['data']['reference'],
    ], 200);
}

public function verify ($reference) {
     $response = Http::withHeaders([
        'Authorization' => 'Bearer sk_test_5f51876ef5ba1a3ea542c81b04310311fa8a87ba',
    ])->get("https://api.paystack.co/transaction/verify/{$reference}");

    if ($response->successful() && $response['data']['status'] === 'success') {
         $order = Orders::where('reference', $reference)->first();
        if ($order) {
            $order->status = 'paid';
            $order->update();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified and order updated successfully.',
                'order_id' => $order->id,
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Payment verified but order not found.',
            ], 404);
        }
    } else {
        return response()->json([
            'status' => 'failed',
            'message' => 'Payment verification failed with Paystack.',
          
        ], 400);
    }
}
}

