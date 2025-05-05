<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil ditampilkan',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $users = User::find($id);
        if ($users) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Ditemukan',
                'data' => $users,
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Tidak Ditemukan',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $users = User::find($id);
        if (!$users) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Tidak Ditemukan',
            ], 404);
        }

        $rules = [
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'email' => 'required|email|max:255'
        ];
        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal Mengubah data',
                'data' => $validate->errors(),
            ]);
        }

        $users->username = $request->username;
        $users->password = Hash::make($request->password);
        $users->email = $request->email;

        $post = $users->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Diubah',

        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $users = User::find($id);
        if (!$users) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Tidak Ditemukan',
            ], 404);
        }



        $post = $users->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Dihapus',

        ], 200);
    }
}
