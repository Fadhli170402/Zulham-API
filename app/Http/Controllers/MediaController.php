<?php

namespace App\Http\Controllers;

use App\Models\medias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function stream($id)
    {
        $media = medias::find($id);
        if ($media->media_type !== 'video') {
            return response()->json(['error' => 'Invalid media type'], 400);
        }

        $path = storage_path("app/public/" . $media->path);
        if (!file_exists($path)) {
            return response()->json(['error' => 'file not found'], 400);
        }
        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}


    /**
     * Display the specified resource.
     */
    public function show(medias $medias)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, medias $medias)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(medias $medias) {}

    public function delete($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $media = medias::findOrFail($id);
        Storage::disk('public')->delete($media->path);
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully'], 200);
    }
}
