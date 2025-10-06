<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\medias;
use App\Models\locations;
use App\Models\complaints;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComplaintsController extends Controller
{
    /**
     * Ambil id_tour dari Personal Access Token (ability: id_tour:<ID>)
     */
    public function getTourIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) return null;

        $abilities = $user->currentAccessToken()->abilities ?? [];
        Log::info('Abilities from token: ', ['abilities' => $abilities]);

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_tour:')) {
                return explode(':', $ability)[1];
            }
        }
        return null;
    }

    /**
     * List pengaduan by tour (dari token)
     */
    public function index(Request $request)
    {
        $id_tour = $this->getTourIdFromToken($request);
        if (!$id_tour) {
            return response()->json([
                'status'  => 'error',
                'message' => 'ID Tour tidak ditemukan dalam token'
            ], 403);
        }

        $complaints = complaints::where('id_tour', $id_tour)
            ->with(['media', 'user', 'tour']) // <- tambahkan tour
            ->orderBy('complaint_date', 'desc')
            ->get();

        $complaintsData = $complaints->map(function ($complaint) {
            return [
                'id_complaint'   => $complaint->id_complaint,
                'complaint'      => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'status'         => $complaint->status, // status ikut
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email'    => $complaint->user->email,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media'   => $media->id_media,
                        'path'       => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour'      => $complaint->tour ? $complaint->tour->id_tour : null,
                    'tour_name'    => $complaint->tour ? $complaint->tour->tour_name : null,
                    'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
                ],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data'    => $complaintsData,
        ], 200);
    }

    /**
     * Simpan pengaduan baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'complaint'        => 'required|string',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'complete_address' => 'required|string|max:255',
            'media.*'          => 'file|mimes:jpg,jpeg,png,mp4',
            // 'status'        => 'in:pending,proses,selesai' // opsional kalau mau kirim dari client
        ]);

        $user = $request->user();

        // lokasi
        $location = locations::create([
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
            'complete_address' => $request->complete_address,
        ]);

        // complaint
        $complaint = complaints::create([
            'complaint'      => $request->complaint,
            'complaint_date' => now(),
            'id_users'       => $user->id_users,
            'id_location'    => $location->id_location,
            'id_tour'        => $this->getTourIdFromToken($request),
            // 'status'       => $request->input('status', 'pending'), // jika ingin override default DB
        ]);

        // media (jika ada)
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $mime = $file->getMimeType();
                $mediaType = str_starts_with($mime, 'video') ? 'video' : 'image';
                $filename = time() . '_' . $file->getClientOriginalName();
                $pathfile = $file->storeAs('uploads', $filename, 'public');
                medias::create([
                    'id_complaint' => $complaint->id_complaint,
                    'path'         => $pathfile,
                    'media_type'   => $mediaType,
                ]);
            }
        }

        // response konsisten
        $complaint->load(['media', 'user', 'location']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengaduan Berhasil Disimpan',
            'data'    => [
                'id_complaint'   => $complaint->id_complaint,
                'complaint'      => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'status'         => $complaint->status, // kirim status
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email'    => $complaint->user->email,
                ],
                'location' => [
                    'id_location'      => $complaint->location->id_location,
                    'latitude'         => $complaint->location->latitude,
                    'longitude'        => $complaint->location->longitude,
                    'complete_address' => $complaint->location->complete_address,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media'   => $media->id_media,
                        'path'       => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
            ]
        ], 201);
    }

    /**
     * Detail pengaduan by id
     */
    public function show(string $id)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])->findOrFail($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data'    => [
                'id_complaint'   => $complaint->id_complaint,
                'complaint'      => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'status'         => $complaint->status, // <- tambahkan
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email'    => $complaint->user->email,
                ],
                'location' => [
                    'id_location'      => $complaint->location->id_location,
                    'latitude'         => $complaint->location->latitude,
                    'longitude'        => $complaint->location->longitude,
                    'complete_address' => $complaint->location->complete_address,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media'   => $media->id_media,
                        'path'       => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour'      => $complaint->tour ? $complaint->tour->id_tour : null,
                    'tour_name'    => $complaint->tour ? $complaint->tour->tour_name : null,
                    'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
                ],
            ]
        ], 200);
    }

    /**
     * Update pengaduan (teks, lokasi, media). Status tidak diubah di sini.
     */
   public function update(Request $request, string $id)
{
    $validated = $request->validate([
        'status' => 'required|in:pending,proses,selesai'
    ]);

    $complaint = complaints::find($id);
    if (!$complaint) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Pengaduan tidak ditemukan'
        ], 404);
    }

    // Update hanya status
    $complaint->status = $validated['status'];
    $complaint->save();

    // Ambil ulang data lengkap untuk response
    $complaint->load(['media', 'user', 'location', 'tour']);

    return response()->json([
        'status'  => 'success',
        'message' => 'Status pengaduan berhasil diperbarui',
        'data'    => [
            'id_complaint'   => $complaint->id_complaint,
            'complaint'      => $complaint->complaint,
            'complaint_date' => $complaint->complaint_date,
            'status'         => $complaint->status,
            'user' => [
                'id_users' => $complaint->user->id_users,
                'username' => $complaint->user->username,
                'email'    => $complaint->user->email,
            ],
            'location' => [
                'id_location'      => $complaint->location->id_location,
                'latitude'         => $complaint->location->latitude,
                'longitude'        => $complaint->location->longitude,
                'complete_address' => $complaint->location->complete_address,
            ],
            'media' => $complaint->media->map(function ($media) {
                return [
                    'id_media'   => $media->id_media,
                    'path'       => Storage::url($media->path),
                    'media_type' => $media->media_type,
                ];
            }),
            'tour' => [
                'id_tour'      => $complaint->tour ? $complaint->tour->id_tour : null,
                'tour_name'    => $complaint->tour ? $complaint->tour->tour_name : null,
                'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
            ],
        ]
    ], 200);
}


    /**
     * Hapus pengaduan + media
     */
    public function destroy(string $id)
    {
        $complaint = complaints::with('media')->findOrFail($id);

        // Hapus media di storage & DB
        foreach ($complaint->media as $media) { // <- gunakan property, bukan media()
            if (Storage::disk('public')->exists($media->path)) {
                Storage::disk('public')->delete($media->path);
            }
            $media->delete();
        }

        $complaint->delete();
        return response()->json(['message' => 'Pengaduan Dan Media Berhasil Dihapus'], 200);
    }

    /**
     * List pengaduan by user & tour
     */
    public function getComplaintById($id_users, $id_tour)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])
            ->where('id_users', $id_users)
            ->where('id_tour', $id_tour)
            ->orderBy('complaint_date', 'desc')
            ->get();

        if ($complaint->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada pengaduan ditemukan untuk pengguna ini'
            ], 404);
        }

        $data = $complaint->map(function ($complaint) {
            return [
                'id_complaint'   => $complaint->id_complaint,
                'complaint'      => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'status'         => $complaint->status,
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email'    => $complaint->user->email,
                ],
                'location' => [
                    'id_location'      => $complaint->location->id_location,
                    'latitude'         => $complaint->location->latitude,
                    'longitude'        => $complaint->location->longitude,
                    'complete_address' => $complaint->location->complete_address,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media'   => $media->id_media,
                        'path'       => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour'      => $complaint->tour ? $complaint->tour->id_tour : null,
                    'tour_name'    => $complaint->tour ? $complaint->tour->tour_name : null,
                    'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
                ],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data'    => $data
        ], 200);
    }

    /**
     * List pengaduan by user
     */
    public function getComplaintByIdUser($id_users)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])
            ->where('id_users', $id_users)
            ->orderBy('complaint_date', 'desc')
            ->get();

        if ($complaint->isEmpty()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak ada pengaduan ditemukan untuk pengguna ini'
            ], 404);
        }

        $data = $complaint->map(function ($complaint) {
            return [
                'id_complaint'   => $complaint->id_complaint,
                'complaint'      => $complaint->complaint,
                'complaint_date' => $complaint->complaint_date,
                'status'         => $complaint->status,
                'user' => [
                    'id_users' => $complaint->user->id_users,
                    'username' => $complaint->user->username,
                    'email'    => $complaint->user->email,
                ],
                'location' => [
                    'id_location'      => $complaint->location->id_location,
                    'latitude'         => $complaint->location->latitude,
                    'longitude'        => $complaint->location->longitude,
                    'complete_address' => $complaint->location->complete_address,
                ],
                'media' => $complaint->media->map(function ($media) {
                    return [
                        'id_media'   => $media->id_media,
                        'path'       => Storage::url($media->path),
                        'media_type' => $media->media_type,
                    ];
                }),
                'tour' => [
                    'id_tour'      => $complaint->tour ? $complaint->tour->id_tour : null,
                    'tour_name'    => $complaint->tour ? $complaint->tour->tour_name : null,
                    'address_tour' => $complaint->tour ? $complaint->tour->address_tour : null,
                ],
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data'    => $data
        ], 200);
    }
}
