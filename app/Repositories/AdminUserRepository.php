<?php

namespace App\Repositories;

use App\Models\AdminUser;
use App\Services\UploadFileService;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\CommonTrait;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Cache;

class AdminUserRepository extends BaseRepository
{
    protected $uploadFileService;
    public function __construct(AdminUser $adminUser, private ImageRepository $imgRepository)
    {
        parent::__construct($adminUser);
    }

    public function save(array $attributes = [], $id = null)
    {
        return DB::transaction(function () use ($attributes, $id) {
            // 1. 密碼雜湊
            if (isset($attributes['password'])) {
                $attributes['password'] = Hash::make($attributes['password']);
            }

            // 2. 圖片處理（Slim 圖片上传）
            if (array_key_exists('slim', $attributes)) {
                if (is_null($attributes['slim'])) {
                    unset($attributes['slim']);
                } else {
                    // 假設 imgRepository 與父類都已注入
                    $data['image'] = $attributes['slim'];
                    $model    = parent::findOrNew($id);
                    $oldImage = $model->image[0] ?? null;
                    $path     = $this->imgRepository->saveSlimFile($data, $model, $oldImage);
                    unset($attributes['slim']);
                }
            }

            $roleId = $attributes['role_id'] ?? null;
            unset($attributes['role_id']);
          
            // 3. 寫入或更新資料
            /** @var \App\Models\User $admin */
            $admin = parent::save($attributes, $id);

            // 4. 角色同步
            if (isset($roleId)) {
                $role = Role::find($roleId);
                if (! $role) {
                    // 交易中拋錯，就會自動回滾
                    throw new \Exception('指定的角色不存在');
                }
                $admin->syncRoles([]);       // 先清空舊角色
                $admin->assignRole($role);   // 再分配新角色
                if($admin->roles[0]->id == $role->id){
                   Cache::forget('admin_menu0' . $admin->id);
                }
                Cache::forget('role' . $role->id);
            }

            return $admin;
        });
    }


    public function  paginate($perPage, $sortColumn = 'updated_at', $sortDirection = 'desc',$filters = []){
        return $this->model->orderBy($sortColumn, $sortDirection)
        ->filter($filters)
        ->paginate($perPage)
        ->withQueryString()
        ->through(fn ($adminUser) => [
            'id' => $adminUser->id,
            'username' => $adminUser->email,
            'name' => $adminUser->name,
            'role' => $adminUser->getRoleNames()[0] ?? null,
            'is_active' => $adminUser->is_active,
            'created_at' => $adminUser->created_at->toDateTimeString(),
            'updated_at' => $adminUser->updated_at->toDateTimeString(),
        ]);
    }


    public function getAdminUser($activeOnly = true)
    {
        $query = $this->model->newQuery();
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->get()->transform(fn ($adminUser) => [
            'id' => $adminUser->id,
            'name' => $adminUser->name,
        ]);
    }

}
