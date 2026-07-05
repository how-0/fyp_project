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
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($user);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Wrong password.',
            ], 401);
        }

        $user = Auth::guard('web')->user();

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

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $user->session_id = $request->session()->getId();
        $user->save();

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->session_id = null;
            $user->save();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
