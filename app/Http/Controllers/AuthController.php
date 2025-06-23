<?php

namespace App\Http\Controllers;

use PDO;
use App\Models\User;
use App\Models\tours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $users = new User();

        $rules = [
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8',
            'email' => 'required|email|max:255|unique:users,email',
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

        $users->save();

        //$token = $users->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Disimpan',


        ], 200);
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username Wajib Diisi',
            'password.required' => 'Password Wajib Diisi',
        ]);

        $user = User::where('username', $credentials['username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau Password Salah',
            ], 401);
        }

        // Jika admin, tidak perlu id_tour
        if ($user->role === 'admin') {
            $token = $user->createToken('auth_token', ['admin'])->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login Admin Berhasil',
                'data' => [
                    'user' => [
                        'id_users' => $user->id_users,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                ],
            ], 200);
        }

        // Validasi dan proses untuk user biasa
        $request->validate([
            'id_tour' => 'required|exists:tours,id_tour',
        ], [
            'id_tour.required' => 'ID Tour Wajib Diisi',
            'id_tour.exists' => 'ID Tour Tidak Ditemukan',
        ]);

        $token = $user->createToken('auth_token', [
            'id_tour:' . $request->id_tour,
            'id_users:' . $user->id_users
        ])->plainTextToken;
        // $wisata = tours::find($request->id_tour);
        // $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'status' => 'success',
            'message' => 'Login Berhasil',
            'data' => [
                'user' => [
                    'id_users' => $user->id_users,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
                'id_tour' => $request->id_tour,
                'role' => $user->role,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {

        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout Berhasil',
        ], 200);
    }


    // public function me(Request $request)
    // {
    //     $user = $request->user();

    //     $abilities = $user->currentAccessToken()->abilities;
    //     $id_tours = null;

    //     foreach ($abilities as $ability) {
    //         if (strpos($ability, 'tours:') === 0) {
    //             $id_tours = (int) str_replace('tours', '', $ability)[1];
    //             break;
    //         }
    //     }
    //     if (!$id_tours) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'ID Tour tidak ditemukan dalam token'
    //         ], 403);
    //     }

    //     $wisata = tours::find($id_tours);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Data User Berhasil Ditemukan',
    //         'data' => [
    //             'user' => [
    //                 'id_users' => $user->id_users,
    //                 'username' => $user->username,
    //                 'email' => $user->email,
    //                 'role' => $user->role,
    //             ],
    //             'wisata' => [
    //                 'id_tour' => $wisata,
    //             ],
    //         ],
    //     ], 200);
    // }
}
