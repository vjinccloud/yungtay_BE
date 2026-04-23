<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\HomeService;
use App\Services\CustomerServiceService;
use App\Services\UserService;
use App\Http\Requests\CustomerServiceRequest;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private HomeService $homeService,
        private CustomerServiceService $customerServiceService,
        private UserService $userService
    ) {
    }

    /**
     * 首頁
     */
    public function index()
    {
        // 取得首頁所有資料
        $data = $this->homeService->getHomePageData();

        // 解構資料供視圖使用
        $banners = $data['banners'];        // 輪播圖
        $latestFocus = $data['latestFocus']; // 最新焦點（最新消息）
        $articles = $data['articles'];       // 新聞（三天內熱門，不足用發布時間補齊）
        $dramas = $data['dramas'];           // 影音
        $programs = $data['programs'];       // 節目
        $lives = $data['lives'];             // 直播
        $radios = $data['radios'];           // 廣播
        return view('frontend.home', compact(
            'banners',
            'latestFocus',
            'articles',
            'dramas',
            'programs',
            'lives',
            'radios'
        ));
    }

    /**
     * 隱私權保護政策頁面
     */
    public function privacy()
    {
        return view('frontend.privacy');
    }

    /**
     * 客服中心頁面
     */
    public function customerService()
    {
        // 取得當前登入會員資料
        $userData = [];
        if (auth()->check()) {
            $user = auth()->user();
            $userData = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'address' => $user->full_address, // 使用 Model 的屬性存取器
            ];
        }

        return view('frontend.customer-service.index', compact('userData'));
    }

    /**
     * 處理客服中心表單送出
     */
    public function sendCustomerService(CustomerServiceRequest $request)
    {
        // 取得驗證後的資料
        $validated = $request->validated();

        // 如果是登入會員，記錄會員ID
        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        // 儲存訊息（會自動發送郵件通知）
        $result = $this->customerServiceService->saveFromFrontend($validated);

        // 如果是 AJAX 請求，回傳 JSON
        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 422);
        }

        // 傳統表單提交處理
        if ($result['status']) {
            return redirect()->route('customer-service')
                ->with('success', $result['msg']);
        } else {
            return redirect()->route('customer-service')
                ->withInput()
                ->with('error', $result['msg']);
        }
    }
}