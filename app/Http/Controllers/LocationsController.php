<?php

namespace App\Http\Controllers;

use App\Models\locations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = locations::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Lokasi Berhasil ditampilkan',
            'data' => $locations
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $locations = new locations();

        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'complete_address' => 'required|string|max:255',
        ];

        $valdite = Validator::make($request->all(), $rules);
        if ($valdite->fails()) {
            return response()->json([
                'status' => 'error ',
                'message' => $valdite->errors(),
            ], 422);
        }

        $locations->latitude = $request->latitude;
        $locations->longitude = $request->longitude;
        $locations->complete_address = $request->complete_address;
        $locations->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Lokasi Berhasil Disimpan',
            'data' => $locations,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $locations = locations::find($id);
        if (!$locations) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi Tidak Ditemukan',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Data Lokasi Berhasil Ditemukan',
            'data' => $locations,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $locations = locations::find($id);
        if (!$locations) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Lokasi Tidak Ditemukan',
            ], 404);
        }

        $rules = [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'complete_address' => 'required|string|max:255',
        ];
        $valdite = Validator::make($request->all(), $rules);
        if ($valdite->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $valdite->errors(),
            ], 422);
        }
        $locations->latitude = $request->latitude;
        $locations->longitude = $request->longitude;
        $locations->complete_address = $request->complete_address;
        $locations->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Lokasi Berhasil Diubah',
            'data' => $locations,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
