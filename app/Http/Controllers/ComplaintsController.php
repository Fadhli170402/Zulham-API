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
        Log::info('Update request received:', [
            'id'     => $id,
            'method' => $request->method(),
            'all'    => $request->all(),
            'files'  => $request->hasFile('media') ? 'Has files' : 'No files'
        ]);

        $validated = $request->validate([
            'complaint'        => 'required|string',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'complete_address' => 'required|string|max:255',
            'media.*'          => 'nullable|file|mimes:jpg,jpeg,png,mp4'
        ]);

        DB::beginTransaction();
        try {
            $complaint = complaints::where('id_complaint', $id)->first();
            if (!$complaint) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Pengaduan Tidak Ditemukan'], 404);
            }

            // update field utama
            $complaint->complaint      = $validated['complaint'];
            $complaint->complaint_date = now();

            if ($complaint->isDirty()) {
                if (!$complaint->save()) throw new \Exception('Failed to save complaint');
            }

            // update lokasi
            $location = locations::find($complaint->id_location);
            if (!$location) throw new \Exception('Location not found');

            $location->latitude         = $validated['latitude'];
            $location->longitude        = $validated['longitude'];
            $location->complete_address = $validated['complete_address'];
            if ($location->isDirty() && !$location->save()) {
                throw new \Exception('Failed to save location');
            }

            // update media jika ada file baru:
            if ($request->hasFile('media')) {
                // hapus media lama
                $oldMedias = medias::where('id_complaint', $complaint->id_complaint)->get();
                foreach ($oldMedias as $oldMedia) {
                    if (Storage::disk('public')->exists($oldMedia->path)) {
                        Storage::disk('public')->delete($oldMedia->path);
                    }
                    $oldMedia->delete();
                }

                $files = is_array($request->file('media')) ? $request->file('media') : [$request->file('media')];
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $mime = $file->getMimeType();
                        $mediaType = str_starts_with($mime, 'video') ? 'video' : 'image';
                        $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $pathfile = $file->storeAs('uploads', $filename, 'public');

                        medias::create([
                            'id_complaint' => $complaint->id_complaint,
                            'path'         => $pathfile,
                            'media_type'   => $mediaType,
                        ]);
                    }
                }
            }

            DB::commit();

            $complaint = $complaint->fresh(['media', 'location', 'user']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Pengaduan Berhasil Diupdate',
                'data'    => [
                    'id_complaint'   => $complaint->id_complaint,
                    'complaint'      => $complaint->complaint,
                    'complaint_date' => $complaint->complaint_date,
                    'status'         => $complaint->status, // ikutkan status juga di response
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
                    'user' => [
                        'id_users' => $complaint->user->id_users,
                        'username' => $complaint->user->username,
                        'email'    => $complaint->user->email,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat update complaint: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengupdate pengaduan',
                'error'   => $e->getMessage(),
            ], 500);
        }
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
