<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NewsService;

class NewsController extends Controller
{
    
    public function __construct(private NewsService $newsService)
    {
    }

    /**
     * 最新消息列表
     */
    public function index(Request $request)
    {
        // 取得新聞列表資料
        $result = $this->newsService->getFrontendList(6);

        // 預載第一頁的分頁資料（給 JSON-LD 和 Vue 初始資料）
        $firstPageNews = $this->newsService->getFrontendList(20);

        // 取得模組 SEO
        $moduleSEO = $this->newsService->getModuleSEO('news');
        $metaOverride = $moduleSEO ?? [];

        return view('frontend.news.index', compact('result', 'firstPageNews', 'metaOverride'));
    }

    /**
     * 最新消息詳情
     */
    public function show($id)
    {
        $news = $this->newsService->getFrontendDetail($id);
        
        if (!$news) {
            abort(404);
        }
        
        // 取得詳情頁 SEO
        $metaOverride = $this->newsService->getDetailSEO($news);

        return view('frontend.news.show', compact('news', 'metaOverride'));
    }

}