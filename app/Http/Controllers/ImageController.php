<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use App\Http\Resources\ImageResource;
use App\Http\Resources\ImageCollection;

class ImageController extends Controller
{
    public function index()
    {
        $images = Image::with('product')->get();
        return new ImageCollection($images);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'image_link' => 'required|string',
        ]);

        $image = Image::create($validated);

        return new ImageResource($image);
    }

    public function show($id)
    {
        $image = Image::with('product')->find($id);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        return new ImageResource($image);
    }

    public function update(Request $request, $id)
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'exists:products,id',
            'image_link' => 'string',
        ]);

        $image->update($validated);

        return new ImageResource($image);
    }

    public function destroy($id)
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
