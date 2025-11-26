<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login (LoginRequest $request)
    {
        // dd(get_class(auth('api')));
        $credentials = $request->validated();

        $user = User::where('nama_lengkap', $credentials['nama_lengkap'])
            ->where('tanggal_lahir', $credentials['tanggal_lahir'])
            ->first();

        if (! $user) {
            return response()->json([
                'statusCode' => 401,
                'message' => 'Akun tidak ada',
            ], 401);
        }

        $token = auth('api')->login($user);
        return response()->json([
            'statusCode' => 200,
            'message' => 'Login berhasil',
            'data' => [
                'id' => $user->id,
                'nama_lengkap' => $user->nama_lengkap,
                'tanggal_lahir' => $user->tanggal_lahir->format('d-m-Y'),
                'role' => $user->role,
                'token' => $token,
            ]], 200);
    }

    public function logout (Request $request)
    {
        auth('api')->logout(true);

        return response()->json([
            'statusCode' => 200,
            'message' => 'Logout berhasil',
        ], 200);
    }
}
