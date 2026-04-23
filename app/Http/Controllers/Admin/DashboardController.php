<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * 顯示儀表板首頁
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // 取得統計數據 - Service 層已處理所有錯誤
        $todayStats = $this->dashboardService->getTodayStatistics();
        $systemStatus = $this->dashboardService->getSystemStatus();
        $recentActivities = $this->dashboardService->getRecentActivities();
        
        return Inertia::render('Admin/Dashboard/Index', [
            'todayStats' => $todayStats,
            'systemStatus' => $systemStatus,
            'recentActivities' => $recentActivities,
            'pageTitle' => '儀表板'
        ]);
    }

    /**
     * 清除儀表板快取
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clearCache(Request $request)
    {
        $result = $this->dashboardService->clearCache();
        
        return redirect()->back()->with('result', $result);
    }

    /**
     * 重新計算觀看數統計（同時檢查並生成缺少的縮圖）
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function recalculateViewStatistics(Request $request)
    {
        $result = $this->dashboardService->recalculateViewStatistics();

        return redirect()->back()->with('result', $result);
    }
}
