<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ImageCompressionService
{
    /**
     * 圖片壓縮設定
     */
    private const MAX_WIDTH = 1600;  // 最大寬度 1600px（適合網頁顯示）
    private const MAX_HEIGHT = 900;  // 16:9 比例
    private const JPEG_QUALITY = 92;  // 提高 JPEG 品質到 92%
    private const PNG_COMPRESSION = 3; // PNG 壓縮等級 0-9（降低壓縮，提高品質）

    /**
     * 壓縮圖片並保持 16:9 比例
     *
     * @param string $imageData 原始圖片資料 (binary)
     * @param string $filename 檔案名稱
     * @return array ['data' => compressed_data, 'width' => width, 'height' => height, 'size' => file_size]
     * @throws \Exception
     */
    public static function compressImage($imageData, $filename)
    {
        try {
            // 檢查 GD 擴展
            if (!extension_loaded('gd')) {
                throw new \Exception('GD 擴展未安裝，無法進行圖片壓縮');
            }

            // 從二進制資料建立圖片資源
            $originalImage = imagecreatefromstring($imageData);
            if (!$originalImage) {
                throw new \Exception('無法解析圖片資料');
            }

            // 取得原始圖片尺寸
            $originalWidth = imagesx($originalImage);
            $originalHeight = imagesy($originalImage);


            // 計算新尺寸（保持 16:9 比例，且不超過最大限制）
            $newDimensions = self::calculateNewDimensions($originalWidth, $originalHeight);
            $newWidth = $newDimensions['width'];
            $newHeight = $newDimensions['height'];

            // 建立新圖片資源
            $newImage = imagecreatetruecolor($newWidth, $newHeight);

            // 保持透明度（對於 PNG）
            if (self::isPng($filename)) {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefill($newImage, 0, 0, $transparent);
            }

            // 重新取樣圖片
            imagecopyresampled(
                $newImage, $originalImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $originalWidth, $originalHeight
            );

            // 輸出壓縮後的圖片
            ob_start();
            
            if (self::isPng($filename)) {
                imagepng($newImage, null, self::PNG_COMPRESSION);
            } else {
                // 預設使用 JPEG 格式
                imagejpeg($newImage, null, self::JPEG_QUALITY);
            }
            
            $compressedData = ob_get_contents();
            ob_end_clean();

            // 清理記憶體
            imagedestroy($originalImage);
            imagedestroy($newImage);

            $compressedSize = strlen($compressedData);
            $compressionRatio = round((1 - $compressedSize / strlen($imageData)) * 100, 2);


            return [
                'data' => $compressedData,
                'width' => $newWidth,
                'height' => $newHeight,
                'size' => $compressedSize,
                'compression_ratio' => $compressionRatio
            ];

        } catch (\Exception $e) {
            Log::error('圖片壓縮失敗', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 計算新的圖片尺寸（保持 16:9 比例）
     *
     * @param int $originalWidth
     * @param int $originalHeight
     * @return array ['width' => int, 'height' => int]
     */
    private static function calculateNewDimensions($originalWidth, $originalHeight)
    {
        // 如果圖片已經符合最大尺寸限制，則不需要調整
        if ($originalWidth <= self::MAX_WIDTH && $originalHeight <= self::MAX_HEIGHT) {
            return ['width' => $originalWidth, 'height' => $originalHeight];
        }

        // 計算縮放比例
        $widthRatio = self::MAX_WIDTH / $originalWidth;
        $heightRatio = self::MAX_HEIGHT / $originalHeight;
        
        // 使用較小的縮放比例以確保圖片不會超出限制
        $scale = min($widthRatio, $heightRatio);

        $newWidth = (int) round($originalWidth * $scale);
        $newHeight = (int) round($originalHeight * $scale);

        // 確保符合 16:9 比例（如果需要的話）
        $targetRatio = 16 / 9;
        $currentRatio = $newWidth / $newHeight;

        if (abs($currentRatio - $targetRatio) > 0.1) {
            // 如果比例差異太大，調整為 16:9
            if ($currentRatio > $targetRatio) {
                // 圖片太寬，調整寬度
                $newWidth = (int) round($newHeight * $targetRatio);
            } else {
                // 圖片太高，調整高度
                $newHeight = (int) round($newWidth / $targetRatio);
            }
        }

        return ['width' => $newWidth, 'height' => $newHeight];
    }

    /**
     * 檢查是否為 PNG 格式
     *
     * @param string $filename
     * @return bool
     */
    private static function isPng($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return $extension === 'png';
    }

    /**
     * 驗證圖片格式是否支援壓縮
     *
     * @param string $filename
     * @return bool
     */
    public static function isSupportedFormat($filename)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
    }

    /**
     * 取得檔案大小的可讀格式
     *
     * @param int $size
     * @return string
     */
    public static function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * 生成縮圖
     *
     * @param string $imageData 原始圖片資料 (binary)
     * @param string $filename 檔案名稱
     * @param int $thumbnailWidth 縮圖寬度 (預設 443)
     * @param int $thumbnailHeight 縮圖高度 (預設 250)
     * @return array ['data' => thumbnail_data, 'width' => width, 'height' => height, 'size' => file_size]
     * @throws \Exception
     */
    public static function generateThumbnail($imageData, $filename, $thumbnailWidth = 443, $thumbnailHeight = 250)
    {
        try {
            // 檢查 GD 擴展
            if (!extension_loaded('gd')) {
                throw new \Exception('GD 擴展未安裝，無法生成縮圖');
            }

            // 從二進制資料建立圖片資源
            $originalImage = imagecreatefromstring($imageData);
            if (!$originalImage) {
                throw new \Exception('無法解析圖片資料');
            }

            // 取得原始圖片尺寸
            $originalWidth = imagesx($originalImage);
            $originalHeight = imagesy($originalImage);


            // 建立縮圖資源
            $thumbnail = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);

            // 保持透明度（對於 PNG）
            if (self::isPng($filename)) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }

            // 計算裁切區域以保持比例並填滿縮圖
            $targetRatio = $thumbnailWidth / $thumbnailHeight;
            $originalRatio = $originalWidth / $originalHeight;

            if ($originalRatio > $targetRatio) {
                // 原圖較寬，裁切左右
                $cropWidth = (int) round($originalHeight * $targetRatio);
                $cropHeight = $originalHeight;
                $cropX = (int) round(($originalWidth - $cropWidth) / 2);
                $cropY = 0;
            } else {
                // 原圖較高，裁切上下
                $cropWidth = $originalWidth;
                $cropHeight = (int) round($originalWidth / $targetRatio);
                $cropX = 0;
                $cropY = (int) round(($originalHeight - $cropHeight) / 2);
            }

            // 生成縮圖（智能裁切並縮放）
            imagecopyresampled(
                $thumbnail, $originalImage,
                0, 0, $cropX, $cropY,
                $thumbnailWidth, $thumbnailHeight,
                $cropWidth, $cropHeight
            );

            // 輸出縮圖
            ob_start();
            
            if (self::isPng($filename)) {
                imagepng($thumbnail, null, self::PNG_COMPRESSION);
            } else {
                // 縮圖品質也提高到 90%，避免模糊
                imagejpeg($thumbnail, null, 90);
            }
            
            $thumbnailData = ob_get_contents();
            ob_end_clean();

            // 清理記憶體
            imagedestroy($originalImage);
            imagedestroy($thumbnail);


            return [
                'data' => $thumbnailData,
                'width' => $thumbnailWidth,
                'height' => $thumbnailHeight,
                'size' => strlen($thumbnailData)
            ];

        } catch (\Exception $e) {
            Log::error('縮圖生成失敗', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 同時處理圖片壓縮和生成縮圖
     *
     * @param string $imageData 原始圖片資料
     * @param string $filename 檔案名稱
     * @param bool $generateThumbnail 是否生成縮圖
     * @return array ['compressed' => [...], 'thumbnail' => [...]]
     */
    public static function processImage($imageData, $filename, $generateThumbnail = true)
    {
        $result = [];

        // 壓縮主圖
        $result['compressed'] = self::compressImage($imageData, $filename);

        // 生成縮圖
        if ($generateThumbnail) {
            // 使用壓縮後的圖片生成縮圖，以節省資源
            $result['thumbnail'] = self::generateThumbnail($result['compressed']['data'], $filename);
        }

        return $result;
    }
}