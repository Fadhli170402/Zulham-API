<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\ratings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\DB;

class RatingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // Ambil ID User dari Token

    public function getByTour($id_tour)
{
    try {
        $ratings = ratings::with(['tour', 'users'])
            ->where('id_tour', $id_tour)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($ratings->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rating untuk tour ini tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data rating berhasil ditemukan',
            'data' => $ratings
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengambil data rating: ' . $e->getMessage(),
        ], 500);
    }
}

    
    private function getUserIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) {
            return null;
        }

        $abilities = $user->currentAccessToken()->abilities;

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_users:')) {
                return explode(':', $ability)[1]; // Ambil ID user
            }
        }
        return null;
    }
    // Ambil ID Tour dari Token
    private function getTourIdFromToken(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->currentAccessToken()) {
            return null;
        }

        $abilities = $user->currentAccessToken()->abilities;

        foreach ($abilities as $ability) {
            if (Str::startsWith($ability, 'id_tour:')) {
                return explode(':', $ability)[1]; // Ambil ID tour
            }
        }

        return null;
    }
    public function index(Request $request)
    {
        $id_user = $this->getUserIdFromToken($request);
        $id_tour = $this->getTourIdFromToken($request);

        if (!$id_user || !$id_tour) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID User atau ID Tour tidak ditemukan dalam token'
            ], 403);
        }

        // Query rating berdasarkan user dan tour
        $ratings = ratings::with('users')
            ->where('id_users', $id_user)
            ->where('id_tour', $id_tour)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Data rating berhasil ditemukan',
            'data' => $ratings
        ], 200);
    }

    public function indexAll()
    {
        $ratings = ratings::with(['tour', 'users'])->orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data rating berhasil ditemukan',
            'data' => $ratings
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'value' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ]);
        $user = $request->user();

        // Get id_tour from request login
        $ability = collect($user->currentAccessToken()->abilities)
            ->first(fn($a) => str_starts_with($a, 'id_tour:'));
        if (!$ability) {
            return response()->json([
                'status' => 'error',
                'message' => 'ID Tour tidak ditemukan dalam token'
            ], 403);
        }
        // $id_tour = explode('id_tour:', '', $ability);
        $id_tour = explode(':', $ability)[1];
        $rating = ratings::updateOrCreate(
            [
                'id_tour' => $id_tour,
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
        Log::info('Update request received:', [
            'id' => $id,
            'method' => $request->method(),
            'all_data' => $request->all(),
        ]);

        $validate = $request->validate([
            'value' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:255',
        ]);

        Log::info('Validation passed:', $validate);

        DB::beginTransaction();
        try {
            $rating = ratings::where('id_rating', $id)->first();
            if (!$rating) {
                DB::rollback();
                Log::error('Rating not found for update:', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rating tidak ditemukan',
                ], 404);
            }
            // Simpan data lama untuk perbandingan
            $oldrating = $rating->value;
            $oldcomment = $rating->comment;

            // Update data rating baru 
            $rating->value = $validate['value'];
            $rating->comment = $validate['comment'];

            // Cek Aakah Data Berhasil Diupdate
            if ($rating->isDirty()) {
                Log::info('Rating has changes, saving... ');
                $savedrating = $rating->save();
                Log::info('Rating saved successfully:' . ($savedrating ? 'SUCESS' : 'FAILED'));
                if (!$savedrating) {
                    throw new \Exception('Failed to save rating');
                }
            } else {
                Log::info('No changes detected, skipping save.');
            }
            // Verify if the rating was updated successfully
            $rating->refresh();
            Log::info('Rating after update:', [
                'id' => $rating->id_ratings,
                'value' => $rating->value,
                'comment' => $rating->comment,
            ]);
            DB::commit();
            Log::info('Transaction committed successfully');

            if (!$rating) {
                Log::error('Failed to update rating:', ['id' => $id]);
                throw new \Exception('Failed to update rating');
            }
            Log::info('Final data After Refresh: ', $rating->toArray());

            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Diubah',
                'data' => $rating,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error during update:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate rating: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyRatings(string $id)
    {
        Log::info('Delete request received for rating ID:', ['id' => $id]);

        DB::beginTransaction();
        try {
            $rating = ratings::where('id_rating', $id)->first();

            if (!$rating) {
                DB::rollback();
                Log::error('Rating not found for deletion:', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rating tidak ditemukan',
                ], 404);
            }
            Log::info('Rating found for deletion:', [
                'id' => $rating->id_rating,
                'value' => $rating->value,
                'comment' => $rating->comment,
            ]);
            $deleted = $rating->delete();

            if (!$deleted) {
                Log::error('Failed to delete rating:', ['id' => $id]);
                throw new \Exception('Failed to delete rating');
            }
            Log::info('Rating deleted successfully:', ['id' => $id]);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data Berhasil Dihapus',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error during deletion:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus rating: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getRating(Request $request)
    {

        $ratings = ratings::with(['tour', 'users'])->get();
        return response()->json([
            'status' => 'success',
            'message' => 'Data Berhasil Ditemukan',
            'data' => $ratings,
        ], 200);
    }
}
