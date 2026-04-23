<?php
namespace App\Http\Controllers\Admin;
use Inertia\Inertia;
use App\Services\AdminUserService;
use App\Services\RoleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AdminUser\StoreRequest;
use App\Http\Requests\AdminUser\UpdatedRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
class AdminSettingController extends Controller
{
    public function __construct(
        private AdminUserService $adminUser,
        private RoleService $roleServe,
    )
    {

    }
    public function index(Request $request)
    {

        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'updated_at'; // 默認排序列為 `id`
        $sortDirection = $request->input('sortDirection') ?? 'desc'; // 默認排序方向為升序
        $perPage = $request->input('length') ?? 10; // ✅ 修正：預設值改為數字 10
        if($sortColumn=='username')$sortColumn='email';
        $adminUsers = $this->adminUser->paginate($perPage, $sortColumn, $sortDirection,$filters);
        $roles = $this->roleServe->all();
        return Inertia::render('Admin/AdminSetting/Index',compact('adminUsers', 'roles'));
    }

    public function show($id){
        $adminUser = $this->adminUser->find($id);

        return response()->json([
            'id' => $adminUser->id,
            'name' => $adminUser->name,
            'email' => $adminUser->email,
            'role_id' => $adminUser->role_id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = $this->roleServe->all();
        return Inertia::render('Admin/AdminSetting/Form', compact('roles'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $adminUser = $this->adminUser->find($id);
        // 確保載入 roles 關聯以取得 role_id
        $adminUser->load('roles');

        // 手動設定 role_id 屬性（因為 Inertia 不會自動序列化 accessor）
        $adminUserData = $adminUser->toArray();
        $adminUserData['role_id'] = $adminUser->role_id; // 觸發 getRoleIdAttribute()

        $roles = $this->roleServe->all();
        return Inertia::render('Admin/AdminSetting/Form', [
            'adminUser' => $adminUserData,
            'roles' => $roles
        ]);
    }


    // 處理表單驗證
    public function store(StoreRequest $request)
    {
        $validated = $request->validated();
        $data = collect($validated)->only(['email', 'name', 'password', 'role_id'])->toArray();
        $result = $this->adminUser->save($data);
        return redirect()
        ->back()
        ->with('result', $result);


    }

    public function update(UpdatedRequest $request){
        $validated = $request->validated();
        $id = $request->route()->parameter('id');
        $data = collect($validated)->only(['email', 'name', 'password', 'role_id'])->toArray();
        $result =  $this->adminUser->save($data, $id);
        return redirect()
        ->back()
        ->with('result', $result);
    }

    public function toggleActive(Request $request)
    {
        // 驗證請求
        $validated = $request->validate([
            'id' => 'required|exists:admin_users,id',
        ]);

        $result = $this->adminUser->checkedStatus($validated['id']);
        return redirect()
        ->back()
        ->with('result', $result);
    }



    public function destroy($id)
    {
        $result = $this->adminUser->delete($id);

        return redirect()
        ->back()
        ->with('result', $result);
    }

}
