<?php

namespace App\Http\Controllers;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\DesignResource;
use App\Http\Resources\DesignCollection;
use Google\Cloud\Storage\StorageClient;

class DesignController extends Controller
{

    public function index()
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            $designs = Design::withoutGlobalScope('creator')->with(['user', 'product'])->paginate();
        } else {
            $designs = Design::with(['user', 'product'])->paginate();
        }

        return new DesignCollection($designs);
    }

    /**
     * Tải file lên Google Cloud Storage và lưu thông tin vào cơ sở dữ liệu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'file' => 'required|file|max:20480', // 20MB giới hạn file tải lên
            'description' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');

        // Tải file lên Google Cloud Storage
        $uploadResult = $this->uploadToGoogleCloud($file);
        if (!$uploadResult['success']) {
            return response()->json(['message' => 'Failed to upload file to Google Cloud Storage', 'error' => $uploadResult['error']], 500);
        }

        $publicUrl = $uploadResult['url']; // URL công khai của file

        // Lưu thông tin thiết kế vào cơ sở dữ liệu
        $design = Design::create([
            'product_id' => $validated['product_id'],
            'user_id' => Auth::id(),
            'file_path' => $publicUrl,
            'description' => $validated['description'] ?? null,
        ]);

        return (new DesignResource($design))
            ->additional(['message' => 'File uploaded successfully']);
    }

    /**
     * Hiển thị thông tin thiết kế
     */
    public function show($id)
    {
        $design = Design::with(['user', 'product'])->find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        return new DesignResource($design);
    }

    /**
     * Cập nhật thiết kế
     */
    public function update(Request $request, $id)
    {
        $design = Design::find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'integer',
            'file' => 'file|max:20480', // 20MB giới hạn file tải lên
            'description' => 'nullable|string|max:255',
        ]);

        // Nếu có file mới, tải lên Google Cloud Storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Xóa file cũ khỏi Google Cloud Storage
            Storage::disk('gcs')->delete(parse_url($design->file_path, PHP_URL_PATH));

            $uploadResult = $this->uploadToGoogleCloud($file);
            if (!$uploadResult['success']) {
                return response()->json(['message' => 'Failed to upload file to Google Cloud Storage', 'error' => $uploadResult['error']], 500);
            }

            $design->file_path = $uploadResult['url'];
        }

        $design->update([
            'product_id' => $validated['product_id'] ?? $design->product_id,
            'description' => $validated['description'] ?? $design->description,
        ]);

        return (new DesignResource($design))
            ->additional(['message' => 'Design updated successfully']);
    }

    /**
     * Xóa thiết kế
     */
    public function destroy($id)
    {
        $design = Design::find($id);

        if (!$design) {
            return response()->json(['message' => 'Design not found'], 404);
        }

        // Xóa file khỏi Google Cloud Storage
        Storage::disk('gcs')->delete(parse_url($design->file_path, PHP_URL_PATH));

        $design->delete();

        return response()->json(['message' => 'Design deleted successfully']);
    }

    /**
     * Hàm tải file lên Google Cloud Storage
     */
    private function uploadToGoogleCloud($file)
{
    try {
        // Lấy tên file gốc
        $fileName = $file->getClientOriginalName();
        
        // Khởi tạo đối tượng StorageClient của Google Cloud
        $storageClient = new StorageClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => storage_path('app/neon-research-441708-j6-eeca532c4182.json'),
        ]);
        
        // Kiểm tra xem bucket có tồn tại không
        $bucket = $storageClient->bucket(env('GOOGLE_CLOUD_STORAGE_BUCKET'));
        if (!$bucket->exists()) {
            throw new \Exception("Bucket not found: " . env('GOOGLE_CLOUD_STORAGE_BUCKET'));
        }

        // Đưa file lên Google Cloud Storage
        $object = $bucket->upload(
            fopen($file->getRealPath(), 'r'), // Đọc file từ đường dẫn
            [
                'name' => 'uploads/' . $fileName, // Tên file lưu trong bucket
            ]
        );

        // Lấy URL công khai của file
        $publicUrl = $object->signedUrl(new \DateTime('1 hour')); // Link hợp lệ trong 1 giờ

        return [
            'success' => true,
            'url' => $publicUrl,
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}





}
