<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\Cache;

class RoleRepository extends BaseRepository
{
    protected $uploadFileService;
    public function __construct(Role $role)
    {
        parent::__construct($role);
    }


    public function all()
    {
        return $this->model->all() ->transform(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ]);
    }

    public function  paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc',$filters = []){
        return $this->model->orderBy($sortColumn, $sortDirection)
        ->filter($filters)
        ->paginate($perPage)
        ->withQueryString()
        ->through(fn ($role) => [
            'id' => $role->id,
            'description' => $role->description,
            'name' => $role->name,
            'users_count' => $role->users()->count(),
            'created_at' => $role->created_at->toDateTimeString(),
            'updated_at' => $role->updated_at->toDateTimeString(),
        ]);
    }

    public function getPermissions(){
        return  $this->model->permissions->pluck('name')->toArray() ?? [];
    }


    public function save(array $attributes = [], $id = null)
    {
        $selectedIds =  $attributes['selectedIds'];
        unset($attributes['selectedIds']);
        $role = parent::save($attributes, $id);

        Cache::forget('role'.$role->id);

        // 確保所有要同步的權限都存在，不存在就自動建立
        foreach ($selectedIds as $permName) {
            if (!empty($permName)) {
                \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'admin']
                );
            }
        }

        // 過濾掉空值
        $selectedIds = array_filter($selectedIds, fn($name) => !empty($name));

        $role->syncPermissions($selectedIds);

        // ✅ 清除所有使用此角色的管理員選單快取
        $usersWithThisRole = $role->users;
        foreach ($usersWithThisRole as $user) {
            Cache::forget('admin_menu0'.$user->id);
        }

        return $role; // 返回创建的用户实例
    }

}
