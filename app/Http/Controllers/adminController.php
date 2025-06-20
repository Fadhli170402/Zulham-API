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
        $pengaduan = User::with(['complaints.media'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => $pengaduan,
        ], 200);
    }

    public function getPengaduanbyId($id)
    {
        $pengaduan = complaints::find($id)->load(['media']);
        if (!$pengaduan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Pengaduan Tidak Ditemukan',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => ([
                'id_complaint' => $pengaduan->id_complaint,
                'complaint' => $pengaduan->complaint,
                'complaint_date' => $pengaduan->complaint_date,
                'user' => $pengaduan->user->username,
                'location' => $pengaduan->location->complete_address,
                'tour' => $pengaduan->tour ? $pengaduan->tour->tour_name : null,
                'media' => $pengaduan->media,
            ]),
        ], 200);
    }
    public function destroyMedia(Request $request)
    {
        // Check if the authenticated user is an admin
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
