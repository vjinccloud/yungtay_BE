<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Services\LocationService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberController extends Controller
{
    public function __construct(
        private UserService $userService,
        private LocationService $locationService
    ) {
    }

    /**
     * 顯示會員列表頁面
     * 支援 DataTable AJAX 請求
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'verification_status', 'register_start_date', 'register_end_date', 'city_id', 'age_min', 'age_max', 'is_active']);
        $sortColumn = $request->input('sortColumn') ?? 'created_at'; // 默認排序列
        $sortDirection = $request->input('sortDirection') ?? 'desc'; // 默認排序方向
        $perPage = $request->input('length') ?? '10';

        $members = $this->userService->paginate($perPage, $sortColumn, $sortDirection, $filters);

        // 載入縣市資料
        $cities = $this->locationService->getCities();

        return Inertia::render('Admin/Members/Index', compact('members', 'cities'));
    }

    /**
     * 顯示會員詳情
     */
    public function show($id)
    {
        $memberData = $this->userService->getFormData($id);

        return Inertia::render('Admin/Members/Show', [
            'member' => $memberData
        ]);
    }

    /**
     * 切換會員啟用/停用狀態
     */
    public function toggleStatus($id)
    {
        // 驗證請求
        $validated = request()->validate([
            'id' => 'sometimes|exists:users,id',
        ]);

        $result = $this->userService->updateStatus($id);
        return redirect()->back()->with('result', $result);
    }

    /**
     * 刪除會員
     */
    public function destroy($id)
    {
        $result = $this->userService->delete($id);
        return redirect()->back()->with('result', $result);
    }

}