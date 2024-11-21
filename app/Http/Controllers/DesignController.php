<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\DesignResource;
use App\Http\Resources\DesignCollection;

class DesignController extends Controller
{

    public function index()
    {
        $designs = Design::with(['user', 'product'])->paginate(); 
        return new DesignCollection($designs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'user_id' => 'required|integer',
            'file' => 'required|file|max:20480',
            'description' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');

        $path = $file->storeAs('uploads', $file->getClientOriginalName(), 'public');

        $design = Design::create([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'file_path' => $path,
            'description' => $validated['description'] ?? null,
        ]);

        return (new DesignResource($design))
            ->additional(['message' => 'File uploaded successfully']);
    }


    public function show($id)
    {
        $design = Design::with(['user', 'product'])->find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        return new DesignResource($design);
    }


    public function update(Request $request, $id)
    {
        $design = Design::find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'exists:users,id',
            'product_id' => 'exists:products,id',
            'file' => 'file|mimes:jpg,png,pdf|max:2048',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($design->file_path);
            $filePath = $request->file('file')->store('designs', 'public');
            $design->file_path = $filePath;
        }

        $design->update($validated);

        return new DesignResource($design);
    }

    public function destroy($id)
    {
        $design = Design::find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        Storage::disk('public')->delete($design->file_path);

        $design->delete();

        return response()->json(['message' => 'Design deleted successfully']);
    }
}
