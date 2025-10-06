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
use PDO;

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
        'status' => $complaint->status, // <- AMBIL STATUS
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
        'media.*' => 'file|mimes:jpg,jpeg,png,mp4',
        // 'status' => 'in:pending,proses,selesai' // opsional
    ]);

    $user = $request->user();

    $location = locations::create([
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'complete_address' => $request->complete_address,
    ]);

    $complaint = complaints::create([
        'complaint' => $request->complaint,
        'complaint_date' => now(),
        'id_users' => $user->id_users,
        'id_location' => $location->id_location,
        'id_tour' => $this->getTourIdFromToken($request),
        // 'status' => $request->input('status', 'pending'), // kalau mau override default
    ]);

    // ... simpan media (punyamu sudah OK)

    return response()->json([
        'status' => 'success',
        'message' => 'Pengaduan Berhasil Disimpan',
        'data' => [
            'id_complaint' => $complaint->id_complaint,
            'complaint' => $complaint->complaint,
            'complaint_date' => $complaint->complaint_date,
            'status' => $complaint->status, // <- KIRIM STATUS
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
        Log::info('Update request received:', [
            'id' => $id,
            'method' => $request->method(),
            'all_data' => $request->all(),
            'files' => $request->hasFile('media') ? 'Has files' : 'No files'
        ]);

        // Validasi input
        $validated = $request->validate([
            'complaint' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'complete_address' => 'required|string|max:255',
            'media.*' => 'nullable|file|mimes:jpg,jpeg,png,mp4'
        ]);

        Log::info('Validation passed:', $validated);

        // Gunakan DB transaction untuk memastikan konsistensi data
        DB::beginTransaction();

        try {
            // Cari complaint berdasarkan ID
            $complaint = complaints::where('id_complaint', $id)->first();

            if (!$complaint) {
                DB::rollBack();
                Log::error('Complaint not found with ID: ' . $id);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengaduan Tidak Ditemukan'
                ], 404);
            }

            Log::info('Complaint found:', $complaint->toArray());

            // Simpan data lama untuk perbandingan
            $oldComplaint = $complaint->complaint;
            $oldDate = $complaint->complaint_date;

            // Update complaint dengan data baru
            $complaint->complaint = $validated['complaint'];
            $complaint->complaint_date = now();

            // Cek apakah ada perubahan sebelum save
            if ($complaint->isDirty()) {
                Log::info('Complaint has changes, saving...');
                $saveResult = $complaint->save();
                Log::info('Complaint save result: ' . ($saveResult ? 'SUCCESS' : 'FAILED'));

                if (!$saveResult) {
                    throw new \Exception('Failed to save complaint');
                }
            } else {
                Log::info('No changes in complaint data');
            }

            // Verify complaint was actually updated
            $complaint->refresh();
            Log::info('Complaint after save:', [
                'old_complaint' => $oldComplaint,
                'new_complaint' => $complaint->complaint,
                'old_date' => $oldDate,
                'new_date' => $complaint->complaint_date
            ]);

            // Update lokasi
            $location = locations::find($complaint->id_location);
            if ($location) {
                Log::info('Location found:', $location->toArray());

                $oldLocation = $location->toArray();

                $location->latitude = $validated['latitude'];
                $location->longitude = $validated['longitude'];
                $location->complete_address = $validated['complete_address'];

                if ($location->isDirty()) {
                    Log::info('Location has changes, saving...');
                    $locationSaveResult = $location->save();
                    Log::info('Location save result: ' . ($locationSaveResult ? 'SUCCESS' : 'FAILED'));

                    if (!$locationSaveResult) {
                        throw new \Exception('Failed to save location');
                    }
                } else {
                    Log::info('No changes in location data');
                }

                // Verify location was actually updated
                $location->refresh();
                Log::info('Location after save:', [
                    'old_location' => $oldLocation,
                    'new_location' => $location->toArray()
                ]);
            } else {
                Log::error('Location not found with ID: ' . $complaint->id_location);
                throw new \Exception('Location not found');
            }
            if ($request->hasFile('media')) {
                // 🔥 TAMBAHAN: Hapus media lama sebelum simpan media baru
                $oldMedias = medias::where('id_complaint', $complaint->id_complaint)->get();

                foreach ($oldMedias as $oldMedia) {
                    if (Storage::exists($oldMedia->path)) {
                        Storage::delete($oldMedia->path); // Hapus file dari storage
                    }
                    $oldMedia->delete(); // Hapus entri dari database
                }

                Log::info('Old media deleted for complaint ID: ' . $complaint->id_complaint);

                // 🔁 Lanjutkan ke proses simpan media baru
                $files = is_array($request->file('media')) ? $request->file('media') : [$request->file('media')];

                Log::info('Processing ' . count($files) . ' media files');

                foreach ($files as $index => $file) {
                    if ($file->isValid()) {
                        $mime = $file->getMimeType();
                        $mediaType = str_starts_with($mime, 'video') ? 'video' : 'image';
                        $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $pathfile = $file->storeAs('uploads', $filename, 'public');

                        $media = medias::create([
                            'id_complaint' => $complaint->id_complaint,
                            'path' => $pathfile,
                            'media_type' => $mediaType,
                        ]);

                        Log::info('Media saved:', [
                            'index' => $index,
                            'filename' => $filename,
                            'path' => $pathfile,
                            'media_id' => $media->id_media
                        ]);
                    } else {
                        Log::error('Invalid file at index: ' . $index);
                    }
                }
            }
            // Commit transaction
            DB::commit();
            Log::info('Transaction committed successfully');

            // Refresh model dengan relasi terbaru
            $complaint = $complaint->fresh(['media', 'location', 'user']);

            if (!$complaint) {
                Log::error('Failed to refresh complaint model');
                throw new \Exception('Failed to refresh complaint data');
            }

            Log::info('Final data after refresh:', $complaint->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaduan Berhasil Diupdate',
                'data' => [
                    'id_complaint' => $complaint->id_complaint,
                    'complaint' => $complaint->complaint,
                    'complaint_date' => $complaint->complaint_date,
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
                    'user' => [
                        'id_users' => $complaint->user->id_users,
                        'username' => $complaint->user->username,
                        'email' => $complaint->user->email,
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saat update complaint: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengupdate pengaduan',
                'error' => $e->getMessage(),
                'debug' => [
                    'request_data' => $request->all(),
                    'complaint_id' => $id
                ]
            ], 500);
        }
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

    public function getComplaintById($id_users, $id_tour)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])
            ->where('id_users', $id_users)
            ->where('id_tour', $id_tour)
            ->orderBy('complaint_date', 'desc')
            ->get();

        if ($complaint->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada pengaduan ditemukan untuk pengguna ini'
            ], 404);
        }
        $data = $complaint->map(function ($complaint) {
            return [
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
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => $data
        ], 200);
    }

    public function getComplaintByIdUser($id_users)
    {
        $complaint = complaints::with(['media', 'user', 'location', 'tour'])
            ->where('id_users', $id_users)
            ->orderBy('complaint_date', 'desc')
            ->get();

        if ($complaint->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada pengaduan ditemukan untuk pengguna ini'
            ], 404);
        }

        $data = $complaint->map(function ($complaint) {
            return [
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
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data Pengaduan Berhasil Ditemukan',
            'data' => $data
        ], 200);
    }
}
