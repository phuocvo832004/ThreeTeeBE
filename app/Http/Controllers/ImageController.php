<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use App\Http\Resources\ImageResource;
use App\Http\Resources\ImageCollection;
use Cloudinary\Cloudinary;

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
            'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:20480',
        ]);
    
        $image = $request->file('image');
    
        if (!$image) {
            return response()->json(['message' => 'No image file uploaded'], 400);
        }
    
        try {
            $cloudinary = new Cloudinary();
            $preset = 'unsigned'; 
    
            $upload = $cloudinary->uploadApi()->upload(
                $image->getRealPath(),
                [
                    'upload_preset' => $preset,
                ]
            );
    
            $imageUrl = $upload['secure_url'];
    
            // Lưu thông tin hình ảnh vào cơ sở dữ liệu
            $imageData = [
                'image_link' => $imageUrl,
                'product_id' => $request->product_id, // Nếu có product_id từ request
            ];
    
            $image = Image::create($imageData);
    
            return response()->json(['image_url' => $imageUrl, 'image' => $image], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
        }
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
            'file' => 'file|image|max:20480', 
        ]);

        if ($request->hasFile('file')) {
            $publicId = basename($image->image_link, '.' . pathinfo($image->image_link, PATHINFO_EXTENSION));
            Cloudinary::destroy($publicId);

            $uploadedFileUrl = Cloudinary::upload($request->file('file')->getRealPath())->getSecurePath();
            $image->image_link = $uploadedFileUrl;
        }

        if ($request->has('product_id')) {
            $image->product_id = $validated['product_id'];
        }

        $image->save();

        return new ImageResource($image);
    }

    public function destroy($id)
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $publicId = basename($image->image_link, '.' . pathinfo($image->image_link, PATHINFO_EXTENSION));
        Cloudinary::destroy($publicId);

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
