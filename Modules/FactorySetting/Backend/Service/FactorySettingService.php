<?php

namespace Modules\FactorySetting\Backend\Service;

use App\Models\Factory;
use App\Models\Region;
use App\Services\SlimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FactorySettingService
{
    /**
     * 取得列表（支援分頁）
     */
    public function getList(Request $request): array
    {
        $query = Factory::with('region')->ordered();

        // 據點篩選
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // 關鍵字搜尋 (DataTable 的 search 參數)
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('name->zh_TW', 'like', "%{$keyword}%")
                  ->orWhere('name->en', 'like', "%{$keyword}%")
                  ->orWhere('country_name->zh_TW', 'like', "%{$keyword}%")
                  ->orWhere('country_name->en', 'like', "%{$keyword}%");
            });
        }

        // 排序
        $sortColumn = $request->input('sortColumn', 'id');
        $sortDirection = $request->input('sortDirection', 'asc');
        
        // 處理排序欄位對應
        $sortableColumns = ['id', 'sort', 'established_date', 'is_enabled'];
        if (in_array($sortColumn, $sortableColumns)) {
            $query->reorder()->orderBy($sortColumn, $sortDirection);
        }

        // 分頁
        $perPage = $request->input('length', 10);
        $paginated = $query->paginate($perPage);

        // 格式化資料
        $items = $paginated->through(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->getTranslations('name'),
                'title' => $item->getTranslations('title'),
                'address' => $item->getTranslations('address'),
                'country_name' => $item->getTranslations('country_name'),
                'established_date' => $item->established_date,
                'region' => $item->region ? [
                    'id' => $item->region->id,
                    'name' => $item->region->getTranslations('name'),
                ] : null,
                'contact_person' => $item->contact_person,
                'image' => $item->image_zh || $item->image_en,
                'logo' => $item->logo_zh || $item->logo_en,
                'has_images' => !empty($item->images_zh) || !empty($item->images_en),
                'has_visit_video' => !empty($item->visit_video_zh) || !empty($item->visit_video_en),
                'has_video_360' => !empty($item->video_360_zh) || !empty($item->video_360_en),
                'sort' => $item->sort,
                'is_enabled' => $item->is_enabled,
            ];
        });

        // 取得所有據點（用於篩選）
        $regions = Region::ordered()->get()->map(function ($region) {
            return [
                'id' => $region->id,
                'name' => $region->getTranslations('name'),
            ];
        });

        return [
            'items' => $items,
            'regions' => $regions,
        ];
    }

    /**
     * 取得單筆資料
     */
    public function getById($id): ?array
    {
        $item = Factory::with('region')->find($id);

        if (!$item) {
            return null;
        }

        return [
            'id' => $item->id,
            'region_id' => $item->region_id,
            'name' => $item->getTranslations('name'),
            'title' => $item->getTranslations('title'),
            'address' => $item->getTranslations('address'),
            'country_name' => $item->getTranslations('country_name'),
            'established_date' => $item->established_date,
            'image_zh' => $this->formatUrl($item->image_zh),
            'image_en' => $this->formatUrl($item->image_en),
            'logo_zh' => $this->formatUrl($item->logo_zh),
            'logo_en' => $this->formatUrl($item->logo_en),
            'images_zh' => $this->formatUrls($item->images_zh),
            'images_en' => $this->formatUrls($item->images_en),
            'visit_video_zh' => $this->formatUrl($item->visit_video_zh),
            'visit_video_en' => $this->formatUrl($item->visit_video_en),
            'video_360_zh' => $this->formatUrl($item->video_360_zh),
            'video_360_en' => $this->formatUrl($item->video_360_en),
            'contact_person' => $item->contact_person,
            'sort' => $item->sort,
            'is_enabled' => $item->is_enabled,
            'region' => $item->region ? [
                'id' => $item->region->id,
                'name' => $item->region->getTranslations('name'),
            ] : null,
        ];
    }

    /**
     * 更新資料
     */
    public function update($id, array $data): array
    {
        // Debug log
        file_put_contents(storage_path('logs/factory_debug.log'), json_encode([
            'time' => date('Y-m-d H:i:s'),
            'id' => $id,
            'slim_image_zh' => isset($data['slim_image_zh']) ? 'HAS DATA (' . strlen($data['slim_image_zh']) . ' chars)' : 'EMPTY',
            'slim_image_en' => isset($data['slim_image_en']) ? 'HAS DATA (' . strlen($data['slim_image_en']) . ' chars)' : 'EMPTY',
            'slim_logo_zh' => isset($data['slim_logo_zh']) ? 'HAS DATA (' . strlen($data['slim_logo_zh']) . ' chars)' : 'EMPTY',
            'slim_logo_en' => isset($data['slim_logo_en']) ? 'HAS DATA (' . strlen($data['slim_logo_en']) . ' chars)' : 'EMPTY',
            'all_keys' => array_keys($data),
        ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        try {
            DB::beginTransaction();

            $item = Factory::find($id);

            if (!$item) {
                return ['status' => false, 'msg' => '找不到該工廠'];
            }

            // 處理多語言欄位
            $item->setTranslations('name', $data['name'] ?? []);
            $item->setTranslations('title', $data['title'] ?? []);
            $item->setTranslations('address', $data['address'] ?? []);
            $item->setTranslations('country_name', $data['country_name'] ?? []);
            
            // 處理成立日期
            $item->established_date = !empty($data['established_date']) ? $data['established_date'] : null;

            // 處理主圖 - 中文 (Slim)
            if (!empty($data['slim_image_zh'])) {
                $imageResult = $this->processSlimImage($data['slim_image_zh'], 'factories/images/zh/');
                if ($imageResult) {
                    $item->image_zh = $imageResult;
                }
            } elseif (!empty($data['image_zh_cleared'])) {
                $item->image_zh = null;
            }

            // 處理主圖 - 英文 (Slim)
            if (!empty($data['slim_image_en'])) {
                $imageResult = $this->processSlimImage($data['slim_image_en'], 'factories/images/en/');
                if ($imageResult) {
                    $item->image_en = $imageResult;
                }
            } elseif (!empty($data['image_en_cleared'])) {
                $item->image_en = null;
            }

            // 處理 Logo - 中文 (Slim)
            if (!empty($data['slim_logo_zh'])) {
                $logoResult = $this->processSlimImage($data['slim_logo_zh'], 'factories/logos/zh/');
                if ($logoResult) {
                    $item->logo_zh = $logoResult;
                }
            } elseif (!empty($data['logo_zh_cleared'])) {
                $item->logo_zh = null;
            }

            // 處理 Logo - 英文 (Slim)
            if (!empty($data['slim_logo_en'])) {
                $logoResult = $this->processSlimImage($data['slim_logo_en'], 'factories/logos/en/');
                if ($logoResult) {
                    $item->logo_en = $logoResult;
                }
            } elseif (!empty($data['logo_en_cleared'])) {
                $item->logo_en = null;
            }

            // 處理多張圖片 - 將完整 URL 轉成相對路徑
            $item->images_zh = $this->normalizeUrls($data['images_zh'] ?? []);
            $item->images_en = $this->normalizeUrls($data['images_en'] ?? []);

            // 處理影片 - 將完整 URL 轉成相對路徑
            $item->visit_video_zh = $this->normalizeUrl($data['visit_video_zh'] ?? null);
            $item->visit_video_en = $this->normalizeUrl($data['visit_video_en'] ?? null);
            $item->video_360_zh = $this->normalizeUrl($data['video_360_zh'] ?? null);
            $item->video_360_en = $this->normalizeUrl($data['video_360_en'] ?? null);

            // 處理其他欄位
            $item->contact_person = $data['contact_person'] ?? null;
            $item->sort = $data['sort'] ?? 0;
            $item->is_enabled = $data['is_enabled'] ?? true;

            // Debug: 顯示即將儲存的值
            file_put_contents(storage_path('logs/factory_debug.log'), "Before save - image_zh: " . ($item->image_zh ?? 'null') . "\n", FILE_APPEND);

            $item->save();

            // Debug: 確認儲存後的值
            $item->refresh();
            file_put_contents(storage_path('logs/factory_debug.log'), "After save - image_zh: " . ($item->image_zh ?? 'null') . "\n", FILE_APPEND);

            DB::commit();

            return ['status' => true, 'msg' => '更新成功'];
        } catch (\Exception $e) {
            DB::rollBack();
            file_put_contents(storage_path('logs/factory_debug.log'), "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            Log::error('Factory update error: ' . $e->getMessage());
            return ['status' => false, 'msg' => '更新失敗：' . $e->getMessage()];
        }
    }

    /**
     * 處理 Slim 圖片上傳
     */
    private function processSlimImage($slimData, $path): ?string
    {
        if (empty($slimData)) {
            file_put_contents(storage_path('logs/factory_debug.log'), "processSlimImage: slimData is empty\n", FILE_APPEND);
            return null;
        }

        file_put_contents(storage_path('logs/factory_debug.log'), "processSlimImage: slimData length = " . strlen($slimData) . "\n", FILE_APPEND);

        $images = SlimService::getImages($slimData);
        
        file_put_contents(storage_path('logs/factory_debug.log'), "processSlimImage: images = " . json_encode($images ? array_keys($images) : 'null') . "\n", FILE_APPEND);
        
        if (!$images || !isset($images['output']['data'])) {
            file_put_contents(storage_path('logs/factory_debug.log'), "processSlimImage: no output data\n", FILE_APPEND);
            return null;
        }

        $name = $images['output']['name'] ?? ('image_' . time() . '.jpg');
        $result = SlimService::saveFile($images['output']['data'], $name, $path);

        file_put_contents(storage_path('logs/factory_debug.log'), "processSlimImage: saveFile result = " . json_encode($result) . "\n", FILE_APPEND);

        if ($result['success']) {
            return $result['path'];
        }

        return null;
    }

    /**
     * 格式化單一 URL（加上前綴斜線）
     */
    private function formatUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        // 如果已經是完整 URL 或已有前綴斜線，直接返回
        if (str_starts_with($url, 'http') || str_starts_with($url, '/')) {
            return $url;
        }
        
        return '/' . $url;
    }

    /**
     * 格式化多個 URL（陣列）
     */
    private function formatUrls(?array $urls): array
    {
        if (empty($urls)) {
            return [];
        }
        
        return array_map(fn($url) => $this->formatUrl($url), $urls);
    }

    /**
     * 正規化 URL - 將完整 URL 轉成相對路徑（用於儲存到資料庫）
     */
    private function normalizeUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        // 移除 domain 部分，保留相對路徑
        // 例如：http://localhost:8000/storage/tmp/videos/xxx.mp4 -> storage/tmp/videos/xxx.mp4
        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            return $path;
        }
        
        return $url;
    }

    /**
     * 正規化多個 URL（陣列）
     */
    private function normalizeUrls(?array $urls): array
    {
        if (empty($urls)) {
            return [];
        }
        
        return array_map(fn($url) => $this->normalizeUrl($url), $urls);
    }
}
