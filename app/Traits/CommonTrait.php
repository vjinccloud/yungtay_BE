<?php

namespace App\Traits;
use App\Services\EventService;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait CommonTrait{

    protected $eventService;
    /**
     * 初始化
     *
     * @return void
     */
    public function initializeTrait() {
        $this->eventService =  app(EventService::class);
    }
    

    /**
    回傳處理
    **/
    public function ReturnHandle($status,$msg='',$redirect='',$data=null){
        $ReturnHandle   =   ['status'   =>  $status,    'msg'   =>  $msg,'redirect'=>$redirect,'data'=>$data];
        return $ReturnHandle;
    }

    /**
     * 解析圖片 URL：支援外部 URL、uploads 與 storage 三種來源
     */
    public function resolveImageUrl(?string $path, ?string $filename = null): string
    {
        if (empty($path)) return '';
        
        // 絕對 URL（外部圖片，如 RSS 來源）直接回傳
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        $clean = ltrim($path, '/');
        if (str_starts_with($clean, 'uploads/')) {
            // Slim 上傳走 uploads 磁碟，public 根目錄可直接訪問
            return '/' . $clean;
        }
        
        // 回退到 storage 公開目錄
        return '/storage/' . $clean;
    }

    /**
     * 數字轉中文
     *
     * @param int $number
     * @return string
     */
    public function numberToChinese($number)
    {
        $chinese = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十'];
        if ($number <= 10) {
            return $chinese[$number];
        }
        return (string) $number;
    }

    /**
     * 串流媒體檔案（支援影片、音訊）
     * 
     * @param \Illuminate\Http\Request $request
     * @param string $filePath
     * @param string $contentType
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function streamFile(\Illuminate\Http\Request $request, string $filePath, string $contentType = 'video/mp4')
    {
        // 解碼 URL 並防止以 / 開頭導致路徑錯誤
        $filePath = urldecode($filePath);
        $filePath = ltrim($filePath, '/\\');

        // 支援 storage/ 與 uploads/ 前綴
        if (str_starts_with($filePath, 'storage/')) {
            $filePath = substr($filePath, strlen('storage/'));
        }

        if (str_starts_with($filePath, 'uploads/')) {
            $fullPath = public_path($filePath);
        } else {
            $fullPath = storage_path('app/public/' . $filePath);
        }

        if (! file_exists($fullPath)) {
            abort(404);
        }

        $fileSize = filesize($fullPath);
        $start    = 0;
        $end      = $fileSize - 1;
        $status   = 200;

        // 處理 Range 請求
        $rangeHeader = $request->headers->get('Range');
        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            if ($matches[1] !== '') {
                $start = (int) $matches[1];
            }
            if ($matches[2] !== '') {
                $end = (int) $matches[2];
            }

            // 邊界保護
            if ($start > $end || $start > $fileSize - 1) {
                // 無效的 Range
                return response('', 416)->header('Content-Range', "bytes */{$fileSize}");
            }

            $status = 206; // Partial Content
        }

        $length = $end - $start + 1;

        $response = new StreamedResponse(function () use ($fullPath, $start, $length) {
            $chunkSize = 1024 * 1024; // 1MB
            $handle = fopen($fullPath, 'rb');
            if ($handle === false) {
                return;
            }
            try {
                fseek($handle, $start);
                $bytesRemaining = $length;
                while ($bytesRemaining > 0 && !feof($handle)) {
                    $readLength = ($bytesRemaining > $chunkSize) ? $chunkSize : $bytesRemaining;
                    $buffer = fread($handle, $readLength);
                    if ($buffer === false) {
                        break;
                    }
                    echo $buffer;
                    flush();
                    $bytesRemaining -= strlen($buffer);
                }
            } finally {
                fclose($handle);
            }
        }, $status);

        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Content-Length', (string) $length);
        if ($status === 206) {
            $response->headers->set('Content-Range', "bytes {$start}-{$end}/{$fileSize}");
        }

        return $response;
    }

    
    /**
     * 從 YouTube URL 取得指定品質的縮圖
     * 
     * @param string|null $youtubeUrl
     * @param string $quality 縮圖品質 (maxresdefault, sddefault, hqdefault, mqdefault, default)
     * @return string|null
     */
    protected function getYouTubeThumbnail($youtubeUrl, $quality = 'maxresdefault')
    {
        if (empty($youtubeUrl)) {
            return null;
        }
        
        // 解析 YouTube 影片 ID
        $videoId = null;
        
        // 支援多種 YouTube URL 格式
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $youtubeUrl, $matches)) {
            $videoId = $matches[1];
        }
        
        if (!$videoId) {
            return null;
        }
        
        // YouTube 縮圖 URL 格式
        // maxresdefault: 1280x720 (最高解析度，16:9)
        // sddefault: 640x480 (標準解析度，4:3)
        // hqdefault: 480x360 (高品質，4:3)
        // mqdefault: 320x180 (中品質，16:9)
        // default: 120x90 (預設，4:3)
        
        // 使用指定的品質
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }

}
