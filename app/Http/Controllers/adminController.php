<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\medias;
use App\Models\complaints;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class adminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil ditampilkan',
            'users' => User::all(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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

    public function getPengaduan()
    {
        $pengaduan = User::with(['complaints.media'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => $pengaduan,
        ], 200);
    }
    public function destroyMedia(Request $request)
    {
        // $user = Auth::user();
        // if ($user->role !== 'admin') {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        // $media = medias::all();

        // if (Storage::disk('public')->exists($media->path)) {
        //     Storage::disk('public')->delete($media->path);
        // }
        // $media->delete();

        // return response()->json(['message' => 'Media deleted successfully'], 200);
        // $user = Auth::user();
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $allmedia = medias::all();
        foreach ($allmedia as $media) {
            Storage::disk('public')->delete($media->path);
        }

        //Delete all media records
        medias::truncate();
        return response()->json([
            'message' => 'All media deleted successfully',
            'total_deleted' => $allmedia->count()
        ], 200);
    }

    public function showMedia()
    {
        $media = medias::all();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Media Berhasil Ditemukan',
            'data' => $media,
        ], 200);
    }
}
