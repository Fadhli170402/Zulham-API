<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\medias;
use App\Models\locations;
use App\Models\complaints;
use Illuminate\Support\Str;
use Termwind\Components\Dd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ComplaintsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function getTourIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) {
            return null;
        }

        $abilities = $user->currentAccessToken()->abilities;

        Log::info('Abilities from token: ', ['abilities' => $abilities]);

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_tour:')) {
                return explode(':', $ability)[1];
            }
        }
        return null;
    }
    public function index(Request $request)
    {
        $id_tour = $this->getTourIdFromToken($request);

        if (!$id_tour) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Tour tidak ditemukan dalam token'
            ], 403);
        }
        $complaints = complaints::where('id_tour', $id_tour)
            ->with(['media', 'user'])
            ->orderBy('complaint_date', 'desc')
            ->get();

        $complaintsData = $complaints->map(function ($complaint) {
            return [
                'id_complaint' => $complaint->id_complaint,
                'complaint' => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email' => $complaint->user->email,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media' => $media->id_media,
                        'path' => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour' => $complaint->tour->id_tour,
                    'tour_name' => $complaint->tour->tour_name,
                    'address_tour' => $complaint->tour->address_tour,
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => $complaintsData,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'complaint' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'complete_address' => 'required|string|max:255',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4|max:2048'
        ]);

        // Get id_users 
        $user = $request->user();

        // Store location
        $location = locations::create([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'complete_address' => $request->complete_address,
        ]);

        // Store complaint
        $complaint = complaints::create([
            'complaint' => $request->complaint,
            'complaint_date' => now(),
            'id_users' => $user->id_users,
            'id_location' => $location->id_location,
            'id_tour' => $this->getTourIdFromToken($request),
        ]);

        // Store media
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                //Deteksi otomatis media type
                $mime = $file->getMimeType();
                $mediaType = str_starts_with($mime, 'video') ? 'video' : 'image';
                // Simpan file ke storage
                $filename = time() . '_' . $file->getClientOriginalName();
                $pathfile = $file->storeAs('uploads', $filename, 'public');
                // Simpan ke database   
                medias::create([
                    'id_complaint' => $complaint->id_complaint,
                    'path' => $pathfile,
                    'media_type' => $mediaType,
                ]);
            }
        }
        // dd($complaint->load('media'));
        return response()->json([
            'status' => 'success',
            'message' => 'Pengaduan Berhasil Disimpan',
            'data' => [
                'id_complaint' => $complaint->id_complaint,
                'complaint' => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'user' => [
                    'id_users' => $user->id_users,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
                'location' => [
                    'id_location' => $location->id_location,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'complete_address' => $location->complete_address,
                ],
                'media' => $complaint->load('media')
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => [
                'id_complaint' => $complaint->id_complaint,
                'complaint' => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email' => $complaint->user->email,
                ],
                'location' => [
                    'id_location' => $complaint->location->id_location,
                    'latitude' => $complaint->location->latitude,
                    'longitude' => $complaint->location->longitude,
                    'complete_address' => $complaint->location->complete_address,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media' => $media->id_media,
                        'path' => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour' => $complaint->tour ? $complaint->tour->id_tour : null,
                    'tour_name' => $complaint->tour ? $complaint->tour->tour_name : null,
                    'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
                ],
            ]
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
        $complaint = complaints::with('media')->findOrFail($id);

        //delete media in storage
        foreach ($complaint->media() as $media) {
            Storage::disk('public')->delete($media->path);
        }

        $complaint->delete();
        return response()->json(['message' => 'Pengaduan Dan Media Berhasil Dihapus'], 200);
    }
}
