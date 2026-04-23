<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TempUploadController extends Controller
{
    /**
     * AJAX 上傳檔案到暫存目錄 (使用唯一識別)
     * route: POST /admin/uploads/tmp/upload
     */
    public function upload(Request $request)
    {
        // 大檔案上傳：僅此端點放寬限制，不影響其他路由
        set_time_limit(3600);           // PHP 最大執行時間 1 小時
        ini_set('max_input_time', 3600); // 接收資料最大時間 1 小時
        ini_set('memory_limit', '2G');   // 記憶體上限

        // 根據上傳類型決定驗證規則
        $uploadType = $request->input('upload_type', 'video'); // 預設為影片
        
        $validationRules = [
            'files'   => 'required|array',
        ];
        
        $validationMessages = [];
        
        // 根據不同的上傳類型設定驗證規則
        switch ($uploadType) {
            case 'video':
                $validationRules['files.*'] = 'required|file|mimes:mp4,mov,avi,wmv,flv|max:5242880'; // 5GB = 5 * 1024 * 1024 KB
                $validationMessages['files.*.mimes'] = '只允許上傳影片檔案 (mp4, mov, avi, wmv, flv)';
                $validationMessages['files.*.max'] = '影片檔案大小不能超過 5GB';
                break;
                
            case 'audio':
                $validationRules['files.*'] = 'required|file|mimes:mp3,wav,m4a,flac|max:1048576'; // 1GB = 1024 * 1024 KB
                $validationMessages['files.*.mimes'] = '只允許上傳音訊檔案 (mp3, wav, m4a, flac)';
                $validationMessages['files.*.max'] = '音訊檔案大小不能超過 1GB';
                break;
            
            case 'image':
                $validationRules['files.*'] = 'required|file|mimes:jpg,jpeg,png,gif,webp|max:20480'; // 20MB
                $validationMessages['files.*.mimes'] = '只允許上傳圖片檔案 (jpg, jpeg, png, gif, webp)';
                $validationMessages['files.*.max'] = '圖片檔案大小不能超過 20MB';
                break;
                
            default:
                // 預設規則（原本的）
                $validationRules['files.*'] = 'file|max:512000';
                $validationMessages['files.*.max'] = '檔案大小不能超過 500MB';
                break;
        }
        
        $request->validate($validationRules, $validationMessages);

        $token = Str::uuid()->toString();
        $uploaded = [];
        
        // 根據上傳類型決定儲存路徑
        $storePath = match($uploadType) {
            'audio' => 'tmp/audios',
            'video' => 'tmp/videos',
            'image' => 'tmp/images',
            default => 'tmp/files'
        };

        foreach ($request->file('files') as $file) {
            // 儲存到對應的暫存目錄
            $tmpPath = $file->store($storePath, 'public');
            $uploaded[] = [
                'original_name' => $file->getClientOriginalName(),
                'tmp_path'      => $tmpPath,
                'size'          => $file->getSize(),
                'mime'          => $file->getMimeType(),
                'extension'     => $file->getClientOriginalExtension(),
            ];
        }

        // 回傳符合前端和資料庫需求的格式
        return response()->json([
            'success'           => true,
            'url'              => asset('storage/' . $uploaded[0]['tmp_path']),
            'filename'         => basename($uploaded[0]['tmp_path']),
            'original_filename' => $uploaded[0]['original_name'],     // 資料庫欄位
            'video_file_path'  => $uploaded[0]['tmp_path'],          // 資料庫欄位
            'file_size'        => round($uploaded[0]['size'] / 1024 / 1024, 2), // 資料庫欄位(MB)
            'video_format'     => $uploaded[0]['extension'],         // 資料庫欄位
            'video_type'       => 'upload',                          // 資料庫欄位
        ]);
    }

    /**
     * AJAX 刪除單一暫存檔
     * route: POST /admin/uploads/tmp/remove
     */
    public function remove(Request $request)
    {
        $tmpPath = $request->input('file');
        if (Storage::disk('public')->exists($tmpPath)) {
            Storage::disk('public')->delete($tmpPath);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => '檔案不存在或已過期'], 404);
    }
    /**
     * 清除所有暫存影片檔案
     * route: POST /admin/uploads/tmp/clear-all
     */
    public function clearAll(Request $request)
    {
        try {
            // 清理所有暫存目錄
            $tmpDirs = ['tmp/videos', 'tmp/audios', 'tmp/files'];
            
            foreach ($tmpDirs as $dir) {
                if (Storage::disk('public')->exists($dir)) {
                    // 只清理超過 2 小時的檔案，避免誤刪正在處理的檔案
                    $files = Storage::disk('public')->allFiles($dir);
                    $twoHoursAgo = now()->subHours(2);
                    
                    foreach ($files as $file) {
                        $lastModified = Storage::disk('public')->lastModified($file);
                        if ($lastModified < $twoHoursAgo->timestamp) {
                            Storage::disk('public')->delete($file);
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true, 
                'message' => '暫存檔案已清理'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => '清理失敗: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 清除指定用戶的暫存檔案（根據時間或其他條件）
     */
    public function clearUserTempFiles(Request $request)
    {
        try {
            // 清理超過 2 小時的暫存檔案
            $files = Storage::disk('public')->allFiles('tmp/videos');
            $twoHoursAgo = now()->subHours(2);
            
            foreach ($files as $file) {
                $lastModified = Storage::disk('public')->lastModified($file);
                if ($lastModified < $twoHoursAgo->timestamp) {
                    Storage::disk('public')->delete($file);
                }
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }
    
}

