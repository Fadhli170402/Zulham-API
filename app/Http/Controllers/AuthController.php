<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $users = new User();

        $rules = [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'email' => 'required|email|max:255',
            'role' => 'in:admin,user',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal Memasukan data',
                'data' => $validate->errors(),
            ]);
        }

        $users->username = $request->username;
        $users->password = Hash::make($request->password);
        $users->email = $request->email;
        $users->role = $request->role ?? 'user';

        $post = $users->save();

        //$token = $users->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            //'token' => $token,
            'message' => 'Data Berhasil Disimpan',
            //'token' => $token,

        ], 200);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate(
            [
                'username' => 'required|string',
                'password' => 'required|string',
            ],
            [
                'username.required' => 'Username Wajib Diisi',
                'password.required' => 'Password Wajib Diisi',
            ]
        );

        //Log::info('User logged : ' . $$request->user());
        $user = User::where('username', $credentials['username'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau Password Salah',
            ], 401);
        }
        //$user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login Berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'role' => $user->role,
            ],
        ], 200);
    }
    public function logout(Request $request)
    {
        //$users = User::where('username', $request->username);
        //$request->user()->currentAccessToken()->delete();
        // return response()->json([
        //   'status' => 'success',
        ///   'message' => 'Logout Berhasil',
        // ], 200);
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        //Log::info('User logged out: ' . $request->user()->username);

        return response()->json([
            'status' => 'success',
            'message' => 'Logout Berhasil',
        ], 200);
    }
}
