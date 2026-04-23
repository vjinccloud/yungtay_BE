<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\OperationLogService;
use App\Services\AdminUserService;

class OperationLogController extends Controller
{
      public function __construct(
        private OperationLogService $operationLog,
        private AdminUserService $adminUser
    ){

    }
    public function index(Request $request)
    {
        $filters = $request->only(['search','action_type', 'start_date', 'end_date', 'ip_address','user_id']);
        $sortColumn = $request->input('sortColumn') ?? 'updated_at'; // 默認排序列為 `id`
        $sortDirection = $request->input('sortDirection') ?? 'desc'; // 默認排序方向為升序
        $perPage =$request->input('length') ?? '1';
        $adminUser = $this->adminUser->getAdminUser();
        $operationLogs = $this->operationLog->paginate($perPage, $sortColumn, $sortDirection, $filters);
        return Inertia::render('Admin/OperationLogs/Index',compact('operationLogs','adminUser'));
    }
}