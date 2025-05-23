<?php

namespace App\Http\Controllers;

use App\Models\ratings;
use Illuminate\Http\Request;

class RatingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ratings = ratings::with(['tour', 'users'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $ratings,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_tour' => 'required|exists:tours,id_tour',
            'value' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $rating = ratings::updateOrCreate(
            [
                'id_tour' => $request->id_tour,
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

    public function getRatingByTour($id)
    {
        $ratings = ratings::where('id_tour', $id)->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $ratings,
        ], 200);
    }
}
