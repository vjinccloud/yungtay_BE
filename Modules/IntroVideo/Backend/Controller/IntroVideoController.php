<?php

namespace Modules\IntroVideo\Backend\Controller;

use App\Http\Controllers\Controller;
use Modules\IntroVideo\Backend\Request\IntroVideoRequest;
use Modules\IntroVideo\Backend\Service\IntroVideoService;
use Inertia\Inertia;

class IntroVideoController extends Controller
{
    protected IntroVideoService $service;

    public function __construct(IntroVideoService $service)
    {
        $this->service = $service;
    }

    /**
     * 顯示設定頁面
     */
    public function edit()
    {
        $data = $this->service->getFormData();

        return Inertia::render('Admin/IntroVideo/Form', [
            'data' => $data,
        ]);
    }

    /**
     * 更新設定
     */
    public function update(IntroVideoRequest $request)
    {
        $validated = $request->validated();
        
        // 取得上傳的影片檔案
        $videoFile = $request->file('video');
        
        // 移除 video 欄位，因為要單獨處理
        unset($validated['video']);
        
        $result = $this->service->save($validated, $videoFile);
        $data = $this->service->getFormData();
        $result['redirect'] = route('admin.intro-video');

        return Inertia::render('Admin/IntroVideo/Form', [
            'result' => $result,
            'data' => $data,
        ]);
    }

    /**
     * 刪除影片
     */
    public function deleteVideo()
    {
        $result = $this->service->deleteVideo();
        $data = $this->service->getFormData();

        return Inertia::render('Admin/IntroVideo/Form', [
            'result' => $result,
            'data' => $data,
        ]);
    }
}
