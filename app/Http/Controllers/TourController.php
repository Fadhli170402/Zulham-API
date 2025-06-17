<?php

namespace App\Http\Controllers;

use App\Models\tours;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tour = tours::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil ditampilkan',
            'data' => $tour
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'address_tour' => 'required|string|max:255',
            'tour_name' => 'required|string|max:255',
        ]);

        $tour = tours::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Disimpan',
            'data' => $tour,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tour = tours::with(['location', 'ratings'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $tour,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, tours $tours, $id)
    {
        $request->validate([
            'address_tour' => 'sometimes|string|max:255',
            'tour_name' => 'sometimes|string|max:255',
        ]);
        try {
            $tours = tours::findOrFail($id);
            $tours->update($request->only(['address_tour', 'tour_name']));
            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Diupdate',
                'data' => $tours,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Gagal Diupdate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $tours = tours::findOrFail($id);
            $tours->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Dihapus',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Gagal Dihapus: ' . $e->getMessage(),
            ], 500);
        }
    }
}
