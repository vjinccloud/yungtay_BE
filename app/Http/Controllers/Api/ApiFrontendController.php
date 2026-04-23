<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\ProductService;
use App\Models\Region;
use App\Services\BasicWebsiteSettingService;
use Modules\HomeVideoSetting\Model\HomeVideoSetting;
use Modules\HomeImageSetting\Model\HomeImageSetting;
use Modules\IntroVideo\Model\IntroVideo;
use Modules\SalesLocationImage\Model\SalesLocationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 前台整合 API Controller
 * 整合首頁系統、產品服務系統的資料給前端使用
 */
class ApiFrontendController extends Controller
{
    /**
     * 取得語系
     */
    protected function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language', 'zh_TW');
        return in_array($locale, ['zh_TW', 'en']) ? $locale : 'zh_TW';
    }

    /**
     * 取得所有整合資料（一次取得全部，中英文都包含）
     * GET /api/v1/frontend/all
     */
    public function all(Request $request)
    {
        // 取得據點與工廠資料（含產品服務關聯）
        $regions = Region::with(['factories' => function ($query) {
            $query->where('is_enabled', true)
                ->with('productServices')
                ->ordered();
        }])
        ->where('is_enabled', true)
        ->ordered()
        ->get()
        ->filter(fn($region) => $region->factories->isNotEmpty())
        ->values()
        ->map(function ($region) {
            return [
                'id' => $region->id,
                'name' => [
                    'zh' => $region->getTranslation('name', 'zh_TW'),
                    'en' => $region->getTranslation('name', 'en'),
                ],
                'factories' => $region->factories->map(function ($factory) {
                    return $this->formatFactoryWithServicesBilingual($factory);
                })->values(),
            ];
        });

        // 取得產品服務
        $productServices = ProductService::enabled()
            ->ordered()
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => [
                        'zh' => $service->getTranslation('name', 'zh_TW'),
                        'en' => $service->getTranslation('name', 'en'),
                    ],
                ];
            });

        // 取得網站基本設定
        $websiteSettings = app(BasicWebsiteSettingService::class)->getSettings();
        
        // 確保 website_icon 有完整網址
        if ($websiteSettings && isset($websiteSettings['website_icon']) && $websiteSettings['website_icon']) {
            $websiteSettings['website_icon'] = $this->getFullUrl($websiteSettings['website_icon']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                // 網站基本設定
                'website_settings' => $websiteSettings,
                // 首頁資料
                'home' => [
                    'videos' => $this->getHomeVideosBilingual(),
                    'images' => $this->getHomeImagesBilingual(),
                ],
                // 片頭動畫
                'intro_video' => $this->getIntroVideo(),
                // 銷售據點圖片
                'sales_locations' => $this->getSalesLocationsBilingual(),
                // 產品服務矩陣
                'product_services' => $productServices,
                // 據點與工廠（每個工廠包含對應的產品服務）
                'regions' => $regions,
            ],
        ]);
    }

    /**
     * 取得完整的首頁資料
     * GET /api/v1/frontend/home
     */
    public function home(Request $request)
    {
        $locale = $this->getLocale($request);
        
        return response()->json([
            'success' => true,
            'data' => [
                'videos' => $this->getHomeVideos($locale),
                'images' => $this->getHomeImages($locale),
            ],
        ]);
    }

    /**
     * 取得首頁影片
     * GET /api/v1/frontend/home/videos
     */
    public function homeVideos(Request $request)
    {
        $locale = $this->getLocale($request);
        
        return response()->json([
            'success' => true,
            'data' => $this->getHomeVideos($locale),
        ]);
    }

    /**
     * 取得首頁圖片
     * GET /api/v1/frontend/home/images
     */
    public function homeImages(Request $request)
    {
        $locale = $this->getLocale($request);
        
        return response()->json([
            'success' => true,
            'data' => $this->getHomeImages($locale),
        ]);
    }

    /**
     * 取得產品服務完整資料（含工廠矩陣）
     * GET /api/v1/frontend/product-services
     */
    public function productServices(Request $request)
    {
        $locale = $this->getLocale($request);
        
        // 取得所有產品服務
        $services = ProductService::enabled()
            ->ordered()
            ->with('factories')
            ->get()
            ->map(function ($service) use ($locale) {
                return [
                    'id' => $service->id,
                    'name' => $service->getTranslation('name', $locale),
                    'factory_ids' => $service->factories->pluck('id')->toArray(),
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * 取得產品服務與工廠矩陣資料
     * GET /api/v1/frontend/product-services/matrix
     */
    public function productServicesMatrix(Request $request)
    {
        $locale = $this->getLocale($request);
        
        // 取得所有據點（含工廠）
        $regions = Region::with(['factories' => function ($query) {
            $query->where('is_enabled', true)->ordered();
        }])
        ->where('is_enabled', true)
        ->ordered()
        ->get()
        ->filter(fn($region) => $region->factories->isNotEmpty())
        ->values()
        ->map(function ($region) use ($locale) {
            return [
                'id' => $region->id,
                'name' => $region->getTranslation('name', $locale),
                'factories' => $region->factories->map(function ($factory) use ($locale) {
                    return [
                        'id' => $factory->id,
                        'name' => $factory->getTranslation('name', $locale),
                    ];
                })->values(),
            ];
        });

        // 取得所有產品服務
        $productServices = ProductService::enabled()
            ->ordered()
            ->get()
            ->map(function ($service) use ($locale) {
                return [
                    'id' => $service->id,
                    'name' => $service->getTranslation('name', $locale),
                ];
            });

        // 取得所有關聯
        $relations = DB::table('factory_product_service')
            ->get()
            ->map(function ($item) {
                return [
                    'factory_id' => $item->factory_id,
                    'product_service_id' => $item->product_service_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'regions' => $regions,
                'product_services' => $productServices,
                'relations' => $relations,
            ],
        ]);
    }

    /**
     * 取得工廠列表（依據點分組）
     * GET /api/v1/frontend/factories
     */
    public function factories(Request $request)
    {
        $locale = $this->getLocale($request);
        
        $regions = Region::with(['factories' => function ($query) {
            $query->where('is_enabled', true)->ordered();
        }])
        ->where('is_enabled', true)
        ->ordered()
        ->get()
        ->filter(fn($region) => $region->factories->isNotEmpty())
        ->values()
        ->map(function ($region) use ($locale) {
            return [
                'id' => $region->id,
                'name' => $region->getTranslation('name', $locale),
                'factories' => $region->factories->map(function ($factory) use ($locale) {
                    return $this->formatFactory($factory, $locale);
                })->values(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * 取得單一工廠詳情
     * GET /api/v1/frontend/factories/{id}
     */
    public function factoryDetail(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        
        $factory = Factory::with(['region', 'productServices'])
            ->where('is_enabled', true)
            ->find($id);
        
        if (!$factory) {
            return response()->json([
                'success' => false,
                'message' => $locale === 'en' ? 'Factory not found' : '找不到該工廠資料',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $this->formatFactoryDetail($factory, $locale),
        ]);
    }

    /**
     * 取得所有據點列表
     * GET /api/v1/frontend/regions
     */
    public function regions(Request $request)
    {
        $locale = $this->getLocale($request);
        
        $regions = Region::withCount(['factories' => function ($query) {
            $query->where('is_enabled', true);
        }])
        ->where('is_enabled', true)
        ->ordered()
        ->get()
        ->map(function ($region) use ($locale) {
            return [
                'id' => $region->id,
                'name' => $region->getTranslation('name', $locale),
                'factory_count' => $region->factories_count,
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $regions,
        ]);
    }

    /**
     * 取得片頭動畫資料
     * GET /api/v1/frontend/intro-video
     */
    public function introVideo(Request $request)
    {
        $introVideo = IntroVideo::where('is_active', true)->first();
        
        if (!$introVideo) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $introVideo->id,
                'video_url' => $introVideo->video_url,
                'video_name' => $introVideo->video_original_name,
                'video_size' => $introVideo->video_size_formatted,
            ],
        ]);
    }

    /**
     * 取得銷售據點圖片列表
     * GET /api/v1/frontend/sales-locations
     */
    public function salesLocations(Request $request)
    {
        $locale = $this->getLocale($request);
        
        $salesLocations = SalesLocationImage::enabled()
            ->ordered()
            ->get()
            ->map(function ($item) use ($locale) {
                $isEn = $locale === 'en';
                $image = $isEn ? $item->imageEn : $item->imageZh;
                
                return [
                    'id' => $item->id,
                    'title' => $item->getTranslation('title', $locale),
                    'image_url' => $image ? $this->getFullUrl($image->path) : null,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $salesLocations,
        ]);
    }

    /**
     * 取得銷售據點圖片列表（雙語）
     * GET /api/v1/frontend/sales-locations/bilingual
     */
    public function salesLocationsBilingual(Request $request)
    {
        $salesLocations = SalesLocationImage::enabled()
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => [
                        'zh' => $item->getTranslation('title', 'zh_TW'),
                        'en' => $item->getTranslation('title', 'en'),
                    ],
                    'image' => [
                        'zh' => $item->imageZh ? $this->getFullUrl($item->imageZh->path) : null,
                        'en' => $item->imageEn ? $this->getFullUrl($item->imageEn->path) : null,
                    ],
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $salesLocations,
        ]);
    }

    // ====================================
    // Private Methods
    // ====================================

    /**
     * 取得片頭動畫資料
     */
    protected function getIntroVideo(): ?array
    {
        $introVideo = IntroVideo::where('is_active', true)->first();
        
        if (!$introVideo) {
            return null;
        }
        
        return [
            'id' => $introVideo->id,
            'video_url' => $introVideo->video_url,
            'video_name' => $introVideo->video_original_name,
            'video_size' => $introVideo->video_size_formatted,
        ];
    }

    /**
     * 取得銷售據點圖片資料（雙語）
     */
    protected function getSalesLocationsBilingual(): array
    {
        return SalesLocationImage::enabled()
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => [
                        'zh' => $item->getTranslation('title', 'zh_TW'),
                        'en' => $item->getTranslation('title', 'en'),
                    ],
                    'image' => [
                        'zh' => $item->imageZh ? $this->getFullUrl($item->imageZh->path) : null,
                        'en' => $item->imageEn ? $this->getFullUrl($item->imageEn->path) : null,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * 取得首頁影片資料
     */
    protected function getHomeVideos(string $locale): array
    {
        return HomeVideoSetting::where('is_enabled', true)
            ->ordered()
            ->get()
            ->map(function ($item) use ($locale) {
                $isEn = $locale === 'en';
                $videoPath = $isEn ? $item->video_en_path : $item->video_zh_path;
                $videoName = $isEn ? $item->video_en_name : $item->video_zh_name;
                
                return [
                    'id' => $item->id,
                    'title' => $item->getTranslation('title', $locale),
                    'video_url' => $videoPath ? $this->getFullUrl($videoPath) : null,
                    'video_name' => $videoName,
                ];
            })
            ->toArray();
    }

    /**
     * 取得首頁圖片資料
     */
    protected function getHomeImages(string $locale): array
    {
        return HomeImageSetting::query()
            ->get()
            ->map(function ($item) use ($locale) {
                $isEn = $locale === 'en';
                $image = $isEn ? $item->imageEn : $item->imageZh;
                
                return [
                    'id' => $item->id,
                    'title' => $item->getTranslation('title', $locale),
                    'image_url' => $image ? $this->getFullUrl($image->path) : null,
                ];
            })
            ->toArray();
    }

    /**
     * 格式化工廠基本資料
     */
    protected function formatFactory(Factory $factory, string $locale): array
    {
        $isEn = $locale === 'en';
        
        return [
            'id' => $factory->id,
            'name' => $factory->getTranslation('name', $locale),
            'title' => $factory->getTranslation('title', $locale),
            'address' => $factory->getTranslation('address', $locale),
            'image' => $this->getMediaUrl($isEn ? $factory->image_en : $factory->image_zh),
            'logo' => $this->getMediaUrl($isEn ? $factory->logo_en : $factory->logo_zh),
            'contact_person' => $factory->contact_person,
            'region' => $factory->region ? [
                'id' => $factory->region->id,
                'name' => $factory->region->getTranslation('name', $locale),
            ] : null,
        ];
    }

    /**
     * 格式化工廠基本資料（含產品服務）
     */
    protected function formatFactoryWithServices(Factory $factory, string $locale): array
    {
        $data = $this->formatFactory($factory, $locale);
        
        // 加入產品服務
        $data['product_services'] = $factory->productServices->map(function ($service) use ($locale) {
            return [
                'id' => $service->id,
                'name' => $service->getTranslation('name', $locale),
            ];
        })->values()->toArray();
        
        return $data;
    }

    /**
     * 格式化工廠詳細資料
     */
    protected function formatFactoryDetail(Factory $factory, string $locale): array
    {
        $isEn = $locale === 'en';
        $images = $isEn ? $factory->images_en : $factory->images_zh;
        $visitVideo = $isEn ? $factory->visit_video_en : $factory->visit_video_zh;
        $video360 = $isEn ? $factory->video_360_en : $factory->video_360_zh;
        
        $data = $this->formatFactory($factory, $locale);
        
        // 附加詳細資料
        $data['images'] = $this->formatImages($images);
        $data['visit_video'] = $this->getMediaUrl($visitVideo);
        $data['video_360'] = $this->getMediaUrl($video360);
        
        // 產品服務
        if ($factory->relationLoaded('productServices')) {
            $data['product_services'] = $factory->productServices->map(function ($service) use ($locale) {
                return [
                    'id' => $service->id,
                    'name' => $service->getTranslation('name', $locale),
                ];
            })->toArray();
        }
        
        return $data;
    }

    /**
     * 格式化圖片陣列
     */
    protected function formatImages($images): array
    {
        if (empty($images)) {
            return [];
        }
        
        return collect($images)->map(function ($image) {
            return $this->getMediaUrl($image);
        })->filter()->values()->toArray();
    }

    /**
     * 取得媒體完整 URL
     */
    protected function getMediaUrl($path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // 已經是完整 URL
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        // 處理相對路徑
        $path = ltrim($path, '/');
        return url($path);
    }

    /**
     * 取得完整 URL（通用）
     */
    protected function getFullUrl($path): ?string
    {
        return $this->getMediaUrl($path);
    }

    // ====================================
    // 雙語版 Methods（用於 /all API）
    // ====================================

    /**
     * 取得首頁影片資料（雙語）
     */
    protected function getHomeVideosBilingual(): array
    {
        return HomeVideoSetting::where('is_enabled', true)
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => [
                        'zh' => $item->getTranslation('title', 'zh_TW'),
                        'en' => $item->getTranslation('title', 'en'),
                    ],
                    'video' => [
                        'zh' => [
                            'url' => $item->video_zh_path ? $this->getFullUrl($item->video_zh_path) : null,
                            'name' => $item->video_zh_name,
                        ],
                        'en' => [
                            'url' => $item->video_en_path ? $this->getFullUrl($item->video_en_path) : null,
                            'name' => $item->video_en_name,
                        ],
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * 取得首頁圖片資料（雙語）
     */
    protected function getHomeImagesBilingual(): array
    {
        return HomeImageSetting::query()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => [
                        'zh' => $item->getTranslation('title', 'zh_TW'),
                        'en' => $item->getTranslation('title', 'en'),
                    ],
                    'image' => [
                        'zh' => $item->imageZh ? $this->getFullUrl($item->imageZh->path) : null,
                        'en' => $item->imageEn ? $this->getFullUrl($item->imageEn->path) : null,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * 格式化工廠基本資料（雙語，含產品服務）
     */
    protected function formatFactoryWithServicesBilingual(Factory $factory): array
    {
        return [
            'id' => $factory->id,
            'name' => [
                'zh' => $factory->getTranslation('name', 'zh_TW'),
                'en' => $factory->getTranslation('name', 'en'),
            ],
            'title' => [
                'zh' => $factory->getTranslation('title', 'zh_TW'),
                'en' => $factory->getTranslation('title', 'en'),
            ],
            'address' => [
                'zh' => $factory->getTranslation('address', 'zh_TW'),
                'en' => $factory->getTranslation('address', 'en'),
            ],
            'image' => [
                'zh' => $this->getMediaUrl($factory->image_zh),
                'en' => $this->getMediaUrl($factory->image_en),
            ],
            'logo' => [
                'zh' => $this->getMediaUrl($factory->logo_zh),
                'en' => $this->getMediaUrl($factory->logo_en),
            ],
            'images' => [
                'zh' => $this->formatImages($factory->images_zh),
                'en' => $this->formatImages($factory->images_en),
            ],
            'visit_video' => [
                'zh' => $this->getMediaUrl($factory->visit_video_zh),
                'en' => $this->getMediaUrl($factory->visit_video_en),
            ],
            'video_360' => [
                'zh' => $this->getMediaUrl($factory->video_360_zh),
                'en' => $this->getMediaUrl($factory->video_360_en),
            ],
            'established_date'=>$factory->established_date,
            'contact_person' => $factory->contact_person,
            'product_services' => $factory->productServices->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => [
                        'zh' => $service->getTranslation('name', 'zh_TW'),
                        'en' => $service->getTranslation('name', 'en'),
                    ],
                ];
            })->values()->toArray(),
        ];
    }
}
