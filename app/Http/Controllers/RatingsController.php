<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\ratings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Validated;

class RatingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Ambil ID User dari Token
    private function getUserIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) {
            return null;
        }

        $abilities = $user->currentAccessToken()->abilities;

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_users:')) {
                return explode(':', $ability)[1]; // Ambil ID user
            }
        }
        return null;
    }
    // Ambil ID Tour dari Token
    private function getTourIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) {
            return null;
        }

        $abilities = $user->currentAccessToken()->abilities;

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_tour:')) {
                return explode(':', $ability)[1]; // Ambil ID tour
            }
        }

        return null;
    }
    public function index(Request $request)
    {
        $id_user = $this->getUserIdFromToken($request);
        $id_tour = $this->getTourIdFromToken($request);

        if (!$id_user || !$id_tour) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID User atau ID Tour tidak ditemukan dalam token'
            ], 403);
        }

        // Query rating berdasarkan user dan tour
        $ratings = ratings::with('users')
            ->where('id_users', $id_user)
            ->where('id_tour', $id_tour)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data rating berhasil ditemukan',
            'data' => $ratings
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'value' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ]);
        $user = $request->user();

        // Get id_tour from request login
        $ability = collect($user->currentAccessToken()->abilities)
            ->first(fn($a) => str_starts_with($a, 'id_tour:'));
        if (!$ability) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Tour tidak ditemukan dalam token'
            ], 403);
        }
        // $id_tour = explode('id_tour:', '', $ability);
        $id_tour = explode(':', $ability)[1];
        $rating = ratings::updateOrCreate(
            [
                'id_tour' => $id_tour,
                'id_users' => $user->id_users,
            ],
            [
                'value' => $request->value,
                'comment' => $request->comment,
            ]
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Disimpan',
            'data' => $rating,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rating = ratings::with(['tour', 'users'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $rating,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getRating(Request $request)
    {

        $ratings = ratings::with(['tour', 'users'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $ratings,
        ], 200);
    }
}
