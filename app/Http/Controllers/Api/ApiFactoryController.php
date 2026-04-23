<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * 工廠資料 API Controller
 * 提供前端取得工廠相關資料
 */
class ApiFactoryController extends Controller
{
    /**
     * 取得所有啟用的工廠列表
     * GET /api/v1/factories
     */
    public function index(Request $request)
    {
        $locale = $request->header('Accept-Language', 'zh_TW');
        $locale = in_array($locale, ['zh_TW', 'en']) ? $locale : 'zh_TW';
        
        $query = Factory::with('region')
            ->where('is_enabled', true)
            ->orderBy('sort', 'asc')
            ->orderBy('id', 'desc');
        
        // 據點篩選
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }
        
        $factories = $query->get()->map(function ($item) use ($locale) {
            return $this->formatFactory($item, $locale);
        });
        
        return response()->json([
            'success' => true,
            'data' => $factories,
        ]);
    }

    /**
     * 取得單一工廠詳情
     * GET /api/v1/factories/{id}
     */
    public function show(Request $request, $id)
    {
        $locale = $request->header('Accept-Language', 'zh_TW');
        $locale = in_array($locale, ['zh_TW', 'en']) ? $locale : 'zh_TW';
        
        $factory = Factory::with('region')
            ->where('is_enabled', true)
            ->find($id);
        
        if (!$factory) {
            return response()->json([
                'success' => false,
                'message' => '找不到該工廠資料',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $this->formatFactoryDetail($factory, $locale),
        ]);
    }

    /**
     * 取得所有據點列表
     * GET /api/v1/factories/regions
     */
    public function regions(Request $request)
    {
        $locale = $request->header('Accept-Language', 'zh_TW');
        $locale = in_array($locale, ['zh_TW', 'en']) ? $locale : 'zh_TW';
        
        $regions = Region::where('is_enabled', true)
            ->orderBy('sort', 'asc')
            ->get()
            ->map(function ($region) use ($locale) {
                return [
                    'id' => $region->id,
                    'name' => $region->getTranslation('name', $locale),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * 依據點分組取得工廠
     * GET /api/v1/factories/by-region
     */
    public function byRegion(Request $request)
    {
        $locale = $request->header('Accept-Language', 'zh_TW');
        $locale = in_array($locale, ['zh_TW', 'en']) ? $locale : 'zh_TW';
        
        $regions = Region::with(['factories' => function ($query) {
            $query->where('is_enabled', true)
                ->orderBy('sort', 'asc');
        }])
            ->where('is_enabled', true)
            ->orderBy('sort', 'asc')
            ->get()
            ->map(function ($region) use ($locale) {
                return [
                    'id' => $region->id,
                    'name' => $region->getTranslation('name', $locale),
                    'factories' => $region->factories->map(function ($factory) use ($locale) {
                        return $this->formatFactory($factory, $locale);
                    }),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * 格式化工廠資料（列表用）
     */
    private function formatFactory($factory, $locale)
    {
        $suffix = $locale === 'en' ? 'en' : 'zh';
        
        return [
            'id' => $factory->id,
            'name' => $factory->getTranslation('name', $locale),
            'title' => $factory->getTranslation('title', $locale),
            'address' => $factory->getTranslation('address', $locale),
            'image' => $this->formatUrl($factory->{"image_{$suffix}"}),
            'logo' => $this->formatUrl($factory->{"logo_{$suffix}"}),
            'region' => $factory->region ? [
                'id' => $factory->region->id,
                'name' => $factory->region->getTranslation('name', $locale),
            ] : null,
            'contact_person' => $factory->contact_person,
        ];
    }

    /**
     * 格式化工廠詳細資料
     */
    private function formatFactoryDetail($factory, $locale)
    {
        $suffix = $locale === 'en' ? 'en' : 'zh';
        
        return [
            'id' => $factory->id,
            'name' => $factory->getTranslation('name', $locale),
            'title' => $factory->getTranslation('title', $locale),
            'address' => $factory->getTranslation('address', $locale),
            'image' => $this->formatUrl($factory->{"image_{$suffix}"}),
            'logo' => $this->formatUrl($factory->{"logo_{$suffix}"}),
            'images' => $this->formatUrls($factory->{"images_{$suffix}"}),
            'visit_video' => $this->formatUrl($factory->{"visit_video_{$suffix}"}),
            'video_360' => $this->formatUrl($factory->{"video_360_{$suffix}"}),
            'region' => $factory->region ? [
                'id' => $factory->region->id,
                'name' => $factory->region->getTranslation('name', $locale),
            ] : null,
            'contact_person' => $factory->contact_person,
        ];
    }

    /**
     * 格式化單一 URL
     */
    private function formatUrl(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }
        
        // 如果已經是完整 URL，直接返回
        if (str_starts_with($url, 'http')) {
            return $url;
        }
        
        // 加上前綴斜線
        $path = str_starts_with($url, '/') ? $url : '/' . $url;
        
        return url($path);
    }

    /**
     * 格式化多個 URL
     */
    private function formatUrls($urls): array
    {
        if (empty($urls) || !is_array($urls)) {
            return [];
        }
        
        return array_map(fn($url) => $this->formatUrl($url), $urls);
    }
}
