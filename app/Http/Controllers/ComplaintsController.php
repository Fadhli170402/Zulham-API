<?php

namespace App\Http\Controllers;

use App\Models\medias;
use App\Models\complaints;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ComplaintsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return complaints::with('media')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_users' => 'required|exists:users,id_users',
            'id_tour' => 'required|exists:tours,id_tour',
            'complaint_date' => 'required|date',
            'complaint' => 'required|string|max:255',
            'media' => 'file|mimes:jpg,jpeg,png,mp4,mov|max:20480',
        ]);

        $complaint = complaints::create($request->only([
            'id_users',
            'id_tour',
            'complaint_date',
            'complaint',
        ]));

        // $complaint = complaints::create([
        //     'id_users' => $request->id_users,
        //     'id_tour' => $request->id_tour,
        //     'complaint_date' => $request->complaint_date,
        //     'complaint' => $request->complaint,
        // ]);

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $media) {
                $path = $media->store('uploads', 'public');
                $mediaType = str_starts_with($media->getMimeType(), 'video') ? 'video' : 'image';
                // $complaint->addMedia(storage_path('app/public/' . $path))->toMediaCollection('media');
                medias::create([
                    'id_complaint' => $complaint->id_complaint,
                    'path' => $path,
                    'media_type' => $mediaType,
                ]);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Disimpan',
                'data' => $complaint,
            ], 201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal Menyimpan Media',
            ], 500);
        }
    }

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
        $complaint = complaints::with('media')->findOrFail($id);

        //delete media in storage
        foreach ($complaint->media() as $media) {
            Storage::disk('public')->delete($media->path);
        }

        $complaint->delete();
        return response()->json(['message' => 'Pengaduan Dan Media Berhasil Dihapus'], 200);
        // $user = Auth::user();
        // // $user = auth()->user();
        // if ($user->role !== 'admin') {
        //     return response()->json([
        //         'message' => 'Unauthorized'
        //     ], 403);
        // }
        // $media = medias::findOrFail($id);
        // Storage::disk('public')->delete($media->path);

    }
}
