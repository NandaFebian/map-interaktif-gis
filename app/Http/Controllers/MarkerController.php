<?php

namespace App\Http\Controllers;

use App\Models\Marker;
use Illuminate\Http\Request;

class MarkerController extends Controller
{
    /**
     * Ambil semua marker dari database.
     */
    public function index()
    {
        return Marker::all();
    }

    /**
     * Simpan marker baru ke database.
     */
    public function store(Request $request)
    {
        // Validasi data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'nullable|string',
        ]);

        // Simpan marker ke database
        $marker = Marker::create($validated);

        // Kembalikan respons JSON dengan status 201 Created
        return response()->json($marker, 201);
    }

    /**
     * Hapus marker dari database.
     */
    public function destroy(Marker $marker)
{
    $marker->delete();
    return response()->json(null, 204);
}
}