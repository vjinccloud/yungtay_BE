<?php
namespace App\Rules;

use App\Services\SlimService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlimImageRule implements ValidationRule
{
    protected $required;
    protected $maxSizeMB;
    protected $allowedTypes;
    protected $minWidth;
    protected $minHeight;
    protected $maxWidth;
    protected $maxHeight;
    protected $hasOriginalImage;
    public function __construct(
        bool $required = false,
        int $maxSizeMB = 5,
        array $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
        int $minWidth = null,
        int $minHeight = null,
        int $maxWidth = null,
        int $maxHeight = null,
        bool $hasOriginalImage = false  // ✅ 新增
    ) {
        $this->required = $required;
        $this->maxSizeMB = $maxSizeMB;
        $this->allowedTypes = $allowedTypes;
        $this->minWidth = $minWidth;
        $this->minHeight = $minHeight;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->hasOriginalImage = $hasOriginalImage;
    }

   public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            // ✅ 如果沒有上傳圖片，但已經有原圖，那就略過
            if ($this->hasOriginalImage) {
                return;
            }

            if ($this->required) {
                $fail('請上傳圖片');
            }
            return;
        }


        try {
            $imageData = SlimService::getImages($value);

            if (!$imageData || !isset($imageData['output'])) {
                $fail('圖片格式錯誤');
                return;
            }

            $output = $imageData['output'];

            if (!in_array($output['type'], $this->allowedTypes)) {
                $allowedTypesString = implode(', ', array_map(fn($type) => str_replace('image/', '', $type), $this->allowedTypes));
                $fail("只允許上傳 {$allowedTypesString} 格式的圖片");
                return;
            }

            $imageSize = strlen($output['data']);
            $maxSizeBytes = $this->maxSizeMB * 1024 * 1024;

            if ($imageSize > $maxSizeBytes) {
                $fail("圖片大小不能超過 {$this->maxSizeMB}MB");
                return;
            }

            if ($this->minWidth && $output['width'] < $this->minWidth) {
                $fail("圖片寬度不能小於 {$this->minWidth} 像素");
                return;
            }

            if ($this->minHeight && $output['height'] < $this->minHeight) {
                $fail("圖片高度不能小於 {$this->minHeight} 像素");
                return;
            }

            if ($this->maxWidth && $output['width'] > $this->maxWidth) {
                $fail("圖片寬度不能大於 {$this->maxWidth} 像素");
                return;
            }

            if ($this->maxHeight && $output['height'] > $this->maxHeight) {
                $fail("圖片高度不能大於 {$this->maxHeight} 像素");
                return;
            }

        } catch (\Exception $e) {
            $fail('圖片處理失敗：' . $e->getMessage());
        }
    }


    // 便利的靜態方法
    public static function required(int $maxSizeMB = 5): self
    {
        return new self(true, $maxSizeMB);
    }

    public static function optional(int $maxSizeMB = 5): self
    {
        return new self(false, $maxSizeMB);
    }

    public static function avatar(): self
    {
        return new self(
            required: false,
            maxSizeMB: 2,
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png'],
            maxWidth: 1000,
            maxHeight: 1000
        );
    }

    public static function cover(): self
    {
        return new self(
            required: true,
            maxSizeMB: 8,
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
            minWidth: 800,
            minHeight: 400
        );
    }
}
