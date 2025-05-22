<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function login(Request $request)
    {
        try {
            $username = $request->username;
            $password = $request->password;

            if (Auth::attempt([
                'username' => $username,
                'password' => $password,
            ])) {
                $user = User::where('username', $username)->first();

                return response()->json([
                    'message' => 'Berhasil masuk',
                    'data' => $user,
                ]);
            }

            return response()->json([
                'message' => 'Username atau password salah',

            ], 401);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
