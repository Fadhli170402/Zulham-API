<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Mockery\Generator\StringManipulation\Pass\Pass;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link Reset Password Berhasil Dikirim ke Email'])
            : response()->json(['message' => 'Gagal mengirimkan link Reset Password ke Email'], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|comfirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );
        return  $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password Berhasil Diubah.'])
            : response()->json(['message' => 'Gagal Mengubah Password.'], 500);
    }
}
