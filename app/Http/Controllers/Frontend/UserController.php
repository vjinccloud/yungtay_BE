<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ApiRegisterRequest;
use App\Services\LocationService;
use App\Services\UserService;
use App\Services\EmailVerificationService;
use App\Services\ViewService;
use App\Services\CollectionService;
use App\Services\CustomerServiceService;
use App\Services\MemberNotificationService;
use App\Http\Requests\User\CompleteProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;
    protected $locationService;
    protected $emailVerificationService;
    protected $viewService;
    protected $collectionService;
    protected $customerServiceService;
    protected $memberNotificationService;

    public function __construct(
        UserService $userService,
        LocationService $locationService,
        EmailVerificationService $emailVerificationService,
        ViewService $viewService,
        CollectionService $collectionService,
        CustomerServiceService $customerServiceService,
        MemberNotificationService $memberNotificationService
    ) {
        $this->userService = $userService;
        $this->locationService = $locationService;
        $this->emailVerificationService = $emailVerificationService;
        $this->viewService = $viewService;
        $this->collectionService = $collectionService;
        $this->customerServiceService = $customerServiceService;
        $this->memberNotificationService = $memberNotificationService;
    }

    /**
     * 顯示登入頁面
     */
    public function showLogin()
    {
        $cities = $this->locationService->getCities();
        
        return view('frontend.member.auth', [
            'pageTitle' => __('messages.page_title.member_login'),
            'currentTab' => 'login',
            'cities' => $cities
        ]);
    }

    /**
     * 處理登入
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');
        $result = $this->userService->login($credentials, $remember);

        // AJAX 請求回傳統一格式
        if ($request->expectsJson()) {
            if ($result['status']) {
                return response()->json(['result' => $result], 200);
            } else {
                // 登入失敗時回傳 Laravel 驗證錯誤格式，讓前端可以在欄位顯示錯誤
                return response()->json([
                    'message' => $result['msg'],
                    'errors' => [
                        'email' => [$result['msg']], // 在帳號欄位顯示錯誤
                        'password' => [$result['msg']] // 在密碼欄位顯示錯誤
                    ]
                ], 422);
            }
        }

        // 傳統表單請求
        return redirect()->back()->with('result', $result);
    }

    /**
     * 顯示註冊頁面
     */
    public function showRegister()
    {
        $cities = $this->locationService->getCities();
        
        return view('frontend.member.auth', [
            'pageTitle' => __('messages.page_title.member_register'),
            'currentTab' => 'register',
            'cities' => $cities
        ]);
    }

    /**
     * 處理註冊
     */
    public function register(ApiRegisterRequest $request)
    {
        $result = $this->userService->register($request->validated());
        
        // AJAX 請求回傳統一格式
        if ($request->expectsJson()) {
            return response()->json([
                'result' => $result
            ], $result['status'] ? 201 : 422);
        }

        // 傳統表單請求
        return redirect()->back()->with('result', $result);
    }

    /**
     * 登出
     */
    public function logout()
    {
        $result = $this->userService->logout();

        return redirect()->route('home')->with('result', $result);
    }


    /**
     * 顯示 Email 驗證等待頁面
     */
    public function showEmailVerification()
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('member.auth');
        }

        // 透過 Service 檢查 Email 驗證狀態
        if ($this->userService->hasVerifiedEmail($user->id)) {
            return redirect()->route('member.account');
        }

        return view('frontend.member.email-verification', [
            'pageTitle' => 'Email 驗證'
        ]);
    }

    /**
     * 重新發送驗證信
     */
    public function resendVerification(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'result' => [
                    'status' => false,
                    'msg' => __('messages.member.please_login')
                ]
            ], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'result' => [
                    'status' => false,
                    'msg' => 'Email 已經驗證過了'
                ]
            ], 422);
        }

        $result = $this->emailVerificationService->resendVerificationEmail($user);
        
        return response()->json([
            'result' => $result
        ], $result['status'] ? 200 : 422);
    }

    /**
     * 處理 Email 驗證
     */
    public function verifyEmail(Request $request)
    {
        $token = $request->get('token');
        
        if (!$token) {
            return redirect()->route('member.email-verification')
                ->with('error', __('messages.member.email_verify_invalid'));
        }

        $result = $this->emailVerificationService->verifyEmail($token);
        
        if ($result['status']) {
            return redirect()->route('member.verification-complete')
                ->with('success', $result['msg']);
        }

        return redirect()->route('member.email-verification')
            ->with('error', $result['msg']);
    }

    /**
     * 顯示驗證完成頁面
     */
    public function verificationComplete()
    {
        return view('frontend.member.verification-complete', [
            'pageTitle' => 'Email 驗證完成'
        ]);
    }

    /**
     * 顯示會員帳戶頁面
     */
    public function account()
    {
        $user = $this->userService->getFormattedCurrentUserData();
        $cities = $this->locationService->getCities();
        $areas = $this->locationService->getAllAreas();
        
        return view('frontend.member.account', [
            'pageTitle' => __('messages.page_title.member_center'),
            'user' => $user,
            'cities' => $cities,
            'areas' => $areas
        ]);
    }

    /**
     * 更新會員資料
     */
    public function updateProfile(Request $request)
    {
        $result = $this->userService->updateCurrentUserProfile($request->all());

        // AJAX 請求直接回傳 Service 的 $result
        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 422);
        }
        
        // 傳統表單請求使用 session
        return redirect()->back()->with('result', $result);
    }

    /**
     * 記錄會員觀看數
     */
    public function recordView(Request $request)
    {
        $validated = $request->validate([
            'content_type' => 'required|in:drama,program,article,live,radio',
            'content_id' => 'required|integer|min:1',
            'episode_id' => 'nullable|integer|min:1'
        ]);

        $result = $this->viewService->recordView(
            $validated['content_type'],
            $validated['content_id'],
            $validated['episode_id'] ?? null,
            auth()->id() // 會員 ID，支援年齡性別統計
        );

        // AJAX 請求直接回傳 Service 的 $result
        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 422);
        }
        
        return redirect()->back()->with('result', $result);
    }

    /**
     * 取得會員觀看歷史
     */
    public function getViewHistory(Request $request)
    {
        // 處理篩選參數
        $filters = [
            'content_type' => $request->get('content_type'),
            'time_range' => $request->get('time_range', 'all'),
        ];
        
        $perPage = $request->get('per_page', 16);
        
        $paginatedData = $this->viewService->getUserViewHistory(auth()->id(), $filters, $perPage);

        if ($request->expectsJson()) {
            // 直接回傳符合前端期望的格式
            return response()->json([
                'status' => true,
                'msg' => '載入成功',
                'data' => $paginatedData
            ]);
        }
        
        return view('frontend.member.view-history', [
            'pageTitle' => __('messages.page_title.watch_history'),
            'viewHistory' => $paginatedData
        ]);
    }

    /**
     * 取得會員觀看統計
     */
    public function getViewStats(Request $request)
    {
        $result = $this->viewService->getUserViewStats(auth()->id());

        if ($request->expectsJson()) {
            return response()->json($result);
        }
        
        return view('frontend.member.view-stats', [
            'pageTitle' => __('messages.page_title.view_statistics'),
            'viewStats' => $result
        ]);
    }

    /**
     * 顯示補完資料頁面
     */
    public function showCompleteProfile()
    {
        // 檢查用戶是否需要補完資料
        $user = Auth::user();
        if ($user->profile_completed) {
            return redirect(route('member.account'))
                ->with('info', '您的資料已完整，無需再次填寫');
        }

        $cities = $this->locationService->getCities();
        $areas = $this->locationService->getAllAreas();
        
        return view('frontend.member.complete-profile', [
            'pageTitle' => __('messages.page_title.complete_profile'),
            'userEmail' => $user->email,
            'cities' => $cities,
            'areas' => $areas
        ]);
    }

    /**
     * 處理補完資料
     */
    public function completeProfile(CompleteProfileRequest $request)
    {
        $validatedData = $request->validated();

        $result = $this->userService->completeProfile(auth()->id(), $validatedData);
        
        // AJAX 請求直接回傳 Service 的 $result
        if ($request->expectsJson()) {
            // 根據錯誤類型決定 HTTP 狀態碼
            $statusCode = $result['status'] ? 200 : 500; // 系統錯誤用 500
            return response()->json($result, $statusCode);
        }
        
        return redirect()->back()->with('result', $result);
    }

    /**
     * 顯示我的收藏頁面
     */
    public function collection()
    {
        // 取得各類型的收藏計數
        $countsResult = $this->collectionService->getUserCollectionCounts(auth()->id());
        $typeCounts = $countsResult['status'] ? $countsResult['data'] : [];
        
        return view('frontend.member.collection', [
            'pageTitle' => __('frontend.member.collection'),
            'typeCounts' => $typeCounts,
        ]);
    }

    /**
     * 顯示觀看紀錄頁面
     */
    public function history()
    {
        return view('frontend.member.history', [
            'pageTitle' => __('frontend.member.history'),
        ]);
    }

    /**
     * 顯示客服紀錄頁面
     */
    public function customerServiceRecords(Request $request)
    {
        // AJAX 請求才去撈資料
        if ($request->expectsJson() || $request->get('ajax')) {
            // 取得分頁參數
            $perPage = $request->get('per_page', 10);

            // 通過 Service 取得會員的客服紀錄
            $result = $this->customerServiceService->getUserCustomerServiceRecords(auth()->id(), $perPage);

            return response()->json($result, $result['status'] ? 200 : 422);
        }

        // 一般頁面請求只回傳 Blade 模板
        return view('frontend.member.customer-service-records', [
            'pageTitle' => __('frontend.member.customer_service_records'),
        ]);
    }

    /**
     * 顯示通知頁面
     */
    public function notifications(Request $request)
    {
        // AJAX 請求才去撈資料
        if ($request->expectsJson() || $request->get('ajax')) {
            // 取得分頁參數
            $perPage = $request->get('per_page', 10);
            // 通過 Service 取得會員的通知紀錄
            $userId = auth()->user()->id;
            $result = $this->memberNotificationService->getUserNotifications($userId, [], $perPage);

            return response()->json($result, $result['status'] ? 200 : 422);
        }

        // 一般頁面請求只回傳 Blade 模板
        return view('frontend.member.notifications', [
            'pageTitle' => __('frontend.member.notice'),
        ]);
    }

    /**
     * 標記通知為已讀（AJAX）
     */
    public function markNotificationAsRead(Request $request, $id)
    {
        try {
            $result = $this->memberNotificationService->markAsRead($id);

            if ($request->expectsJson()) {
                return response()->json($result, $result['status'] ? 200 : 422);
            }

            return redirect()->back()->with('result', $result);

        } catch (\Exception $e) {
            $result = [
                'status' => false,
                'msg' => '標記已讀失敗：' . $e->getMessage()
            ];

            if ($request->expectsJson()) {
                return response()->json($result, 500);
            }

            return redirect()->back()->with('result', $result);
        }
    }
}