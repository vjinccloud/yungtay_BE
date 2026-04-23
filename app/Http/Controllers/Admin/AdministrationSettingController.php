<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RoleService;
use App\Services\AdminMenuService;
use Inertia\Inertia;
use App\Exceptions\CustomPermissionException;
use App\Http\Requests\Role\RoleRequest;
class AdministrationSettingController extends Controller
{
    public function __construct(
        private RoleService $roleServe,
        private AdminMenuService $adminMenu,
    ){

    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $sortColumn = $request->input('sortColumn') ?? 'id'; // 默認排序列為 `id`
        $sortDirection = $request->input('sortDirection') ?? 'asc'; // 默認排序方向為升序
        $perPage =$request->input('length') ?? '1';
        $roles = $this->roleServe->paginate($perPage, $sortColumn, $sortDirection,$filters);
        return Inertia::render('Admin/AdministrationSetting/Index',compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $menu = $this->adminMenu->getBuildAdminMenuTree();
        $role_permissions = [];
        return Inertia::render('Admin/AdministrationSetting/Form',compact('menu','role_permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        $data = $request->validated();
        $result =  $this->roleServe->save($data);
        $result['redirect'] = route('admin.administration-settings');
        return redirect()
        ->back()
        ->with('result', $result);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $menu = $this->adminMenu->getBuildAdminMenuTree();
        $role = $this->roleServe->find($id);
        $role_permissions = $role->permissions->pluck('name')->toArray() ?? [];

        return Inertia::render('Admin/AdministrationSetting/Form',compact('menu','role_permissions','role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, string $id)
    {

        $validated = $request->validated();
        $result =  $this->roleServe->save($validated,$id);
        $result['redirect'] = route('admin.administration-settings');
        return redirect()
        ->back()
        ->with('result', $result);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->roleServe->delete($id);


        return Inertia::render('Admin/AdministrationSetting/Index',compact('result'));
    }
}
