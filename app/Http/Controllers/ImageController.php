<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ImageResource;
use App\Http\Resources\ImageCollection;
use Google\Cloud\Storage\StorageClient;

class ImageController extends Controller
{

    public function getImagesByProduct($productId)
    {
        $images = Image::where('product_id', $productId)->get();

        if ($images->isEmpty()) {
            return response()->json(['message' => 'No images found for this product'], 404);
        }

        return new ImageCollection($images);
    }

    public function index()
    {
        $images = Image::with('product')->get();
        return new ImageCollection($images);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'images.*' => 'required|file|mimes:jpg,jpeg,png,gif|max:20480', // Danh sách file
        ]);
    
        $uploadedImages = [];
    
        foreach ($request->file('images') as $imageFile) {
            // Gọi hàm upload file lên Google Cloud Storage
            $uploadResult = $this->uploadToGoogleCloud($imageFile);
            if (!$uploadResult['success']) {
                return response()->json(['message' => 'Failed to upload image', 'error' => $uploadResult['error']], 500);
            }
    
            $publicUrl = $uploadResult['url'];
    
            // Lưu thông tin vào database
            $image = Image::create([
                'product_id' => $validated['product_id'],
                'image_link' => $publicUrl,
            ]);
    
            $uploadedImages[] = new ImageResource($image);
        }
    
        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $uploadedImages,
        ], 200);
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
            'product_id' => 'integer',
            'image' => 'file|mimes:jpg,jpeg,png,gif|max:20480',
        ]);

        // Nếu có file mới, thay thế file cũ trên Google Cloud Storage
        if ($request->hasFile('image')) {
            $imageFile = $request->file('image');

            // Xóa file cũ khỏi Google Cloud Storage
            $this->deleteFromGoogleCloud($image->image_link);

            // Upload file mới lên Google Cloud Storage
            $uploadResult = $this->uploadToGoogleCloud($imageFile);
            if (!$uploadResult['success']) {
                return response()->json(['message' => 'Failed to upload new image', 'error' => $uploadResult['error']], 500);
            }

            $image->image_link = $uploadResult['url'];
        }

        if ($request->has('product_id')) {
            $image->product_id = $validated['product_id'];
        }

        $image->save();

        return response()->json([
            'message' => 'Image updated successfully',
            'image' => new ImageResource($image),
        ], 200);
    }

    public function destroy($id)
    {
        $image = Image::find($id);

        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        // Xóa file khỏi Google Cloud Storage
        $this->deleteFromGoogleCloud($image->image_link);

        // Xóa record khỏi database
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }

    /**
     * Hàm tải file lên Google Cloud Storage
     */
    private function uploadToGoogleCloud($file)
    {
        try {
            $fileName = 'images/' . uniqid() . '.' . $file->getClientOriginalExtension();

            $storageClient = new StorageClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => storage_path('app/neon-research-441708-j6-eeca532c4182.json'),
            ]);

            $bucket = $storageClient->bucket(env('GOOGLE_CLOUD_STORAGE_BUCKET'));
            if (!$bucket->exists()) {
                throw new \Exception("Bucket not found: " . env('GOOGLE_CLOUD_STORAGE_BUCKET'));
            }

            $object = $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                ['name' => $fileName]
            );

            return [
                'success' => true,
                'url' => $object->info()['mediaLink'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Hàm xóa file khỏi Google Cloud Storage
     */
    private function deleteFromGoogleCloud($fileUrl)
    {
        try {
            $storageClient = new StorageClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => storage_path('app/neon-research-441708-j6-eeca532c4182.json'),
            ]);

            $bucket = $storageClient->bucket(env('GOOGLE_CLOUD_STORAGE_BUCKET'));

            $filePath = parse_url($fileUrl, PHP_URL_PATH);
            $object = $bucket->object(ltrim($filePath, '/'));

            if ($object->exists()) {
                $object->delete();
            }
        } catch (\Exception $e) {
            // Bạn có thể ghi log hoặc xử lý lỗi tại đây
        }
    }
}
