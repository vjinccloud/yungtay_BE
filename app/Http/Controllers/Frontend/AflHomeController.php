<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Banner;
use Illuminate\Http\Request;

/**
 * AFL 前台 - 首頁 Controller
 */
class AflHomeController extends Controller
{
    /**
     * 首頁
     */
    public function index()
    {
        // 取得輪播圖（啟用且按排序）
        $banners = Banner::frontend()->get();

        // 取得設為首頁曝光的最新消息（is_homepage_featured = true）
        $homepageNews = News::where('is_active', true)
            ->where('is_homepage_featured', true)
            ->orderBy('published_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('frontend.afl.home', compact('banners', 'homepageNews'));
    }
}
