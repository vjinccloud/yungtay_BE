<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\DramaService;
use App\Services\DramaEpisodeService;
use App\Services\ProgramService;
use App\Services\ProgramEpisodeService;
use App\Traits\HasModuleDescription;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    use HasModuleDescription;
    public function __construct(
        private DramaService $dramaService,
        private DramaEpisodeService $dramaEpisodeService,
        private ProgramService $programService,
        private ProgramEpisodeService $programEpisodeService
    ) {}

    /**
     * 影片列表頁面（支援影音和節目）
     */
    public function index(Request $request, $contentId)
    {
        try {
            // 從路由名稱判斷是影音還是節目
            $routeName = $request->route()->getName();
            $isProgram = str_contains($routeName, 'program.');
            
            if ($isProgram) {
                // 處理節目
                $contentData = $this->programService->getProgramDetailForFrontend($contentId);
                $recommendations = $this->programService->getRecommendations($contentId);
                $type = 'program';
                $moduleKey = 'program';
            } else {
                // 處理影音
                $contentData = $this->dramaService->getDramaDetailForFrontend($contentId);
                $recommendations = $this->dramaService->getRecommendations($contentId);
                $type = 'drama';
                $moduleKey = 'drama';
            }

            $content = $contentData[$type] ?? $contentData['drama'] ?? $contentData['program'];
            
            // 使用 Service 的 SEO 方法處理陣列資料
            if ($isProgram) {
                $metaOverride = $this->programService->getDetailSEO($content);
            } else {
                $metaOverride = $this->dramaService->getDetailSEO($content);
            }

            return view('frontend.media.videos.index', [
                'content' => $content,
                'drama' => $content, // 保留向後相容
                'episodes' => $contentData['episodes'],
                'seasonInfo' => $contentData['seasonInfo'],
                'recommendations' => $recommendations,
                'type' => $type,
                'metaOverride' => $metaOverride
            ]);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * 影片播放頁面（支援影音和節目）
     */
    public function show(Request $request, $contentId, $episodeId)
    {
        try {
            // 從路由名稱判斷是影音還是節目
            $routeName = $request->route()->getName();
            $isProgram = str_contains($routeName, 'program.');
            
            if ($isProgram) {
                // 處理節目
                $data = $this->programService->getProgramWithEpisode($contentId, $episodeId);
                $type = 'program';
            } else {
                // 處理影音
                $data = $this->dramaService->getDramaWithEpisode($contentId, $episodeId);
                $type = 'drama';
            }
            
            $contentKey = $type === 'program' ? 'program' : 'drama';

            // 統一使用 Service 的 getDetailSEO 方法
            $service = $isProgram ? $this->programService : $this->dramaService;
            $metaOverride = $service->getDetailSEO($data);

            return view('frontend.media.videos.show', [
                'content' => $data[$contentKey],
                'drama' => $data[$contentKey], // 保留向後相容
                'currentEpisode' => $data['currentEpisode'],
                'episodes' => $data['episodes'],
                'prevEpisode' => $data['prevEpisode'],
                'nextEpisode' => $data['nextEpisode'],
                'type' => $type,
                'metaOverride' => $metaOverride
            ]);
        } catch (\Exception $e) {
            abort(404);
        }
    }

    public function streamEpisode(Request $request, string $filePath)
    {
        // 使用共用的 streamFile 方法
        return $this->streamFile($request, $filePath, 'video/mp4');
    }

}
