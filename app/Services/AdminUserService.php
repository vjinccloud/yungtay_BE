<?php
namespace App\Services;

use App\Repositories\AdminUserRepository;
use App\Exceptions\CustomPermissionException;
use App\Services\EventService;
use Illuminate\Support\Facades\DB;
class AdminUserService extends BaseService
{
    public function __construct(private AdminUserRepository $adminUser ) {
        parent::__construct($adminUser);
        $this->eventService =  app(EventService::class);
    }

    public function getAdminUser()
    {
        return $this->adminUser->getAdminUser();
    }

    public function save(array $attributes, $id = null)
    {
        try {
            DB::beginTransaction();

            unset($attributes['event_type']);
            $model = $this->adminUser->save($attributes,$id);
            DB::commit();
            $mag = $id ?'修改成功':'新增成功';
            $eventType = $attributes['event_type'] ?? '管理員-'.$model->name;
            $model->event_type = $eventType;
            $this->eventService->fireDataUpdated($model,$id);
            $return = $this->ReturnHandle(true, $mag, route('admin.admin-settings'));
        } catch (\Exception $e) {
            DB::rollBack();
            //$return=$this->ReturnHandle(false,$e->getMessage());
            throw $e;
        }
        return $return;
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停權';
        $model->event_status_title = "管理員{$action}-{$model->name}";
    }

}
