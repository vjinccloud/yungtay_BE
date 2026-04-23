<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Repositories\ImageRepository;
use Illuminate\Support\Facades\Log;

class UploadFileService {
    const FAILURE = 'failure';
    const SUCCESS = 'success';

    protected $imgRepository;

    protected $tempImagePath;
    protected $imaData;
    protected $request;

    public function __construct(
        ImageRepository $imgRepository,
        Request $request
    ) {
        $this->imgRepository = $imgRepository;
        $this->request = $request;

    }

    public function __destruct() {
        if(isset($this->tempImagePath) && file_exists($this->tempImagePath))
           unlink($this->tempImagePath);
    }

    private  function isImage($file) {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/svg+xml']);
    }


    /**
     * Undocumented function
     *
     * @param  $img
     * @return void
     */
    public function checkImage($img){
        $this->imaData = $this->getBase64Data($img);
        $this->tempImagePath = $this->setTempImagePath($this->imaData);
    }

    /**
     * 将解码后的图像数据写入临时文件
     *
     * @param  $data
     * @return void
     */
    private  function getBase64Data($data) {
        return base64_decode(substr($data, strpos($data, ',') + 1));
    }

    /**
     * 解碼base64
     *
     * @param  $data
     * @return void
     */
    private  function setTempImagePath ($data) {
        $tempImagePath = tempnam(sys_get_temp_dir(), 'icon');
        file_put_contents($tempImagePath, $data);
        return  $tempImagePath;
    }

    /**
     * 驗證是否ICO圖片
     *
     * @param  $data
     * @return void
     */
    public  function checkIcon ($img) {
        $imageInfo = getimagesize($img);
        $validSizes = ['16x16', '32x32','48x48','64x64'];
        $sizeString = $imageInfo[0] . 'x' . $imageInfo[1];
        return in_array($sizeString, $validSizes);
    }

    /**
     * 儲存檔案（參考 saveSlimFile 的簡潔模式）
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param model $class
     * @param string $filePath 儲存路徑
     * @param string $fileType 檔案類型
     * @return void
     */
    public  function saveFile($file,$class,$filePath,$fileType=null){
        if($file instanceof \Illuminate\Http\UploadedFile){
            
            // 1. 查找現有的相同類型圖片
            $existingImage = $class->morphMany(\App\Models\ImageManagement::class, 'attachable')
                ->where('image_type', $fileType)
                ->first();
                
            // 2. 如果有舊圖片，先刪除檔案
            if ($existingImage) {
                $this->imgRepository->deleteImgFile($existingImage);
            }
            
            // 3. 儲存新檔案
            $path = $file->store($filePath,'uploads');
            $imgData= [
                'attachable_id'=>$class['id'],
                'path'=>$path,
                'filename'=>$file->getClientOriginalName(),
                'ext'=> $file->getClientOriginalExtension(),
                'attachable_type' =>  get_class($class),
                'image_type'=> $fileType,
                'seq' => 1,
            ];
            
            // 4. 如果有舊圖片就更新，沒有就新增
            if ($existingImage) {
                $this->imgRepository->updateImg($existingImage, $imgData);
            } else {
                $this->imgRepository->addImg($imgData);
            }
        }
    }

    /**
     * getJsonData
     *
     * @param Illuminate\Database\Eloquent\Collection  $img
     * @return json
     */
    public  function getJsonData($img){
        $imagesInfo = $img->map(function ($v) {
            // 處理不同的路徑格式
            if (str_starts_with($v->path, 'uploads/')) {
                // 如果路徑已經包含 uploads/ 前綴，使用 public_path 直接組合
                $fullPath = public_path($v->path);
            } else {
                // 其他情況（如 icon/ 路徑）直接放在 public/uploads 下
                $fullPath = public_path('uploads/' . $v->path);
            }

            if (file_exists($fullPath)) {
                // 對於 .ico 檔案，使用特殊處理
                if (strtolower($v->ext) === 'ico') {
                    $fileSize = filesize($fullPath);

                    // 根據路徑格式決定 asset URL
                    $assetUrl = str_starts_with($v->path, 'uploads/')
                        ? asset($v->path)
                        : asset('uploads/' . $v->path);

                    return [
                        'file' => $assetUrl,
                        'type' => 'image/x-icon',
                        'size' => $fileSize,
                        'name' => $v->filename,
                    ];
                }

                // 一般圖片檔案使用 getimagesize
                $data = getimagesize($fullPath);
                if ($data) {
                    $fileSize = filesize($fullPath);

                    // 根據路徑格式決定 asset URL
                    $assetUrl = str_starts_with($v->path, 'uploads/')
                        ? asset($v->path)
                        : asset('uploads/' . $v->path);

                    return [
                        'file' => $assetUrl,
                        'type' => $data['mime'],
                        'size' => $fileSize ,
                        'name' => $v->filename,
                    ];
                }
            }
            return null;
        })->filter();

        return $imagesInfo;
    }

    /**
     * 刪除檔案
     *
     * @param  $image 圖片物件
     * @return void
     */
    public  function deleteFile($image){
        try {
            $this->imgRepository->deleteImg($image)->deleteImgFile($image);
        } catch (\Exception $e) {
            Log::error('Failed to delete file: ' . $e->getMessage());
        }
    }


}
