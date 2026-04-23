<?php
namespace App\Services;

use App\Repositories\NewsTypeRepository;
use App\Events\DataUpdated;
use App\Events\DataDeleted;

use Illuminate\Support\Facades\DB;
use App\Services\UploadFileService;
use App\Exceptions\CustomPermissionException;

class NewsTypeService extends BaseService 
{
    public function __construct(NewsTypeRepository $newsTypeRepository) {
        parent::__construct($newsTypeRepository);
    }

    /**
     * 提交並保存 banner 表單。
     * 
     * @param object $data 包含表單數據的物件，應包含表單驗證狀態、表單本身、圖片數據等。
     * @return array 返回一個包含操作結果的數組，此數組包括成功或失敗的狀態和消息。
     * 
     * @throws CustomPermissionException 如果用戶無權執行此操作，拋出自定義權限異常。
     * @throws \Exception 捕捉並重新拋出所有未被前面捕捉的異常。
     * 
     */
    public function submit($data)
    {
        try {
            if(!$data->isValidate)
                throw new CustomPermissionException('送出失敗');
            // 開始交易
            DB::beginTransaction();
            $banner = $data->form->save();
            $path = $this->imgRepository->saveSlimFile($data->imageData,$banner, $data->image,'banner');
            $this->eventService->fireDataUpdated($banner);
            DB::commit();
            $return=$this->ReturnHandle(true,'送出成功'); 
        } catch (CustomPermissionException $e) {
            DB::rollBack();
            $return=$this->ReturnHandle(false,$e->getMessage());
            $return['modelName'] = 'modal-fadein';
        } catch (\Exception $e) {
            DB::rollBack();
            $return=$this->ReturnHandle(false,$e->getMessage());
            throw $e;
        }
        return $return;
    }


    
    /**
     * 删除指定ID的Banner
     * 
     * @param mixed $id Banner主键ID。
     * @return array 回傳成功或失败的訊息。
     * @throws \Exception Error抛出异常。
     */
    public function delete($id){       
        try {
            if(!auth('admin')->user()->can('admin.banner-management.delete'))
                throw new \Exception('無此權限');
            // 開始交易
            DB::beginTransaction();
            $banner = $this->find($id);
            if(!$banner)
                throw new \Exception('查無資料');
            $this->uploadFileService->deleteFile($banner->image->first());
            $this->repository->delete($id);
            $this->eventService->fireDataDeleted($banner);
            DB::commit();
            $return=$this->ReturnHandle(true,'刪除成功');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $return=$this->ReturnHandle(false,$e->getMessage());
            throw $e;
        }

        return $return;
    }



}