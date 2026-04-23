<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\LiveService;
use Illuminate\Http\Request;

class LiveController extends Controller
{
    protected $liveService;

    public function __construct(LiveService $liveService)
    {
        $this->liveService = $liveService;
    }

    /**
     * 顯示直播頁面
     * 
     * @param int|null $id 指定的直播 ID
     */
    public function index($id = null)
    {
        // 取得頁面資料
        $data = $this->liveService->getPageData($id);
        
        // 取得模組 SEO
        $moduleSEO = $this->liveService->getModuleSEO('live');
        $metaOverride = $moduleSEO ?? [];
        
        $data['metaOverride'] = $metaOverride;
        
        return view('frontend.live.index', $data);
    }
}