<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('user')->attempt($request->only('email', 'password'))) {
            $user = Auth::guard('user')->user();

            // Invalidate the previous session so only one device can be logged in at a time
            if ($user->session_id) {
                DB::table('sessions')->where('id', $user->session_id)->delete();
            }

            $request->session()->regenerate();

            $user->session_id = $request->session()->getId();
            $user->save();

            return response()->json([
                'message' => 'Logged in successfully',
                'user' => $user,
            ]);
        }

        return response()->json([
            'message' => 'Wrong password.',
        ], 401);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user  = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => $user,
        ], 201);
    }
}
