<?php
namespace App\Http\Controllers\Admin;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\BasicWebsiteSettingService;
use App\Http\Requests\BasicWebsiteSettings\UpdateRequest;
use App\Services\UploadFileService as File;
class BasicWebsiteSettingController extends Controller
{
    public function __construct(
        private BasicWebsiteSettingService $setting,
        private File $uploadFileService
    )
    {

    }
    public function index()
    {
        $defaultSettings = $this->setting->getSettings(1);
        $fileUpLoaderData =  $this->uploadFileService->getJsonData($defaultSettings['favicon']) ?? [];
        return Inertia::render('Admin/BasicWebsiteSetting/Index', [
            'defaultSettings' => $defaultSettings,
            'fileUpLoaderData' => $fileUpLoaderData
        ]);
    }

    public function update(UpdateRequest $request){
        $data = $request->validated();
        $result =  $this->setting->save($data,1);
        $defaultSettings = $this->setting->getSettings(1);
        $fileUpLoaderData =  $this->uploadFileService->getJsonData($defaultSettings['favicon']) ?? [];
        $result['redirect'] = route('admin.basic-website-settings');
        
        return Inertia::render('Admin/BasicWebsiteSetting/Index', [
            'result' => $result,
            'defaultSettings' => $defaultSettings,
            'fileUpLoaderData' => $fileUpLoaderData
        ])->with([
            'preserveState' => true,
            'preserveScroll' => true
        ]);
    }
}
