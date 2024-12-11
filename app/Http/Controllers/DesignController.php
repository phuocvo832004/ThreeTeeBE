<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;

use App\Models\Design;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\DesignResource;
use App\Http\Resources\DesignCollection;

class DesignController extends Controller
{
    /**
     * Danh sách thiết kế
     */
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
     * Tải file lên GitHub và lưu thông tin vào cơ sở dữ liệu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'file' => 'required|file|max:20480', // 20MB giới hạn file tải lên
            'description' => 'nullable|string|max:255',
        ]);

        $file = $request->file('file');

        // Tải file lên GitHub bằng API
        $githubResponse = $this->uploadToGitHub($file);
        if (!$githubResponse['success']) {
            return response()->json(['message' => 'Failed to upload file to GitHub', 'error' => $githubResponse['error']], 500);
        }

        $publicUrl = $githubResponse['url']; // URL công khai của file trên jsDelivr

        $design = Design::create([
            'product_id' => $validated['product_id'],
            'user_id' => Auth::id(),
            'file_path' => $publicUrl, // Lưu URL công khai vào cơ sở dữ liệu
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
        // Logic cập nhật tương tự như trước nhưng không lưu file cục bộ
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

        // GitHub không hỗ trợ xóa file qua URL công khai
        $design->delete();

        return response()->json(['message' => 'Design deleted successfully']);
    }

    /**
     * Hàm tải file lên GitHub thông qua API
     */
    private function uploadToGitHub($file)
    {
        $githubUsername = 'phuocvo832004';
        $repositoryName = 'glb-repo';
        $branch = 'main'; // Hoặc branch bạn đang sử dụng
        $accessToken = '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'; // Token cá nhân GitHub

        $fileName = $file->getClientOriginalName();
        $fileContent = base64_encode(file_get_contents($file->getPathname()));

        $url = "https://api.github.com/repos/{$githubUsername}/{$repositoryName}/contents/uploads/{$fileName}";
        $payload = [
            'message' => "Upload file {$fileName}",
            'content' => $fileContent,
            'branch' => $branch,
        ];

        $response = Http::withToken($accessToken)
            ->withHeaders(['Accept' => 'application/vnd.github.v3+json'])
            ->put($url, $payload);

        if ($response->failed()) {
            return ['success' => false, 'error' => $response->json()];
        }

        $jsDelivrUrl = "https://cdn.jsdelivr.net/gh/{$githubUsername}/{$repositoryName}@{$branch}/uploads/{$fileName}";
        return ['success' => true, 'url' => $jsDelivrUrl];
    }
}
