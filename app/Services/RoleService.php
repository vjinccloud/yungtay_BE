<?php
namespace App\Services;

use App\Repositories\RoleRepository;


class RoleService extends BaseService
{
    public function __construct(private RoleRepository $role ) {
        parent::__construct($role);
    }

    public function delete($id)
    {
        $role = $this->role->find($id);

        if($role->users->count() > 0){
            $return=$this->ReturnHandle(false,'刪除失敗,該角色權限已被使用');
            return  $return;
        }
        $role->syncPermissions();
        $role->delete();
        $this->eventService->fireDataDeleted($role);
        $return=$this->ReturnHandle(true,'刪除成功');
        return  $return;
    }

}
