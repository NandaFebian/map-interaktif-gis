<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function storeFeature(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:polygon,line',
            'coordinates' => 'required|array',
        ]);

        $feature = Feature::create($request->all());
        return response()->json($feature);
    }

    public function loadFeatures()
    {
        return Feature::all();
    }

        public function deleteFeature($id)
    {
        $feature = Feature::findOrFail($id);
        $feature->delete();
        return response()->json(['success' => true]);
    }

    public function updateFeature(Request $request, $id)
{
    $request->validate([
        'coordinates' => 'required|array',
    ]);

    $feature = Feature::findOrFail($id);
    $feature->update([
        'coordinates' => $request->coordinates
    ]);

    return response()->json(['success' => true]);
}

}
