<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiLiveController extends Controller
{
    protected $liveService;

    public function __construct(LiveService $liveService)
    {
        $this->liveService = $liveService;
    }

    /**
     * 批次檢查多個 YouTube 直播狀態
     */
    public function batchCheckStatus(Request $request)
    {
        $request->validate([
            'lives' => 'required|array',
            'lives.*.id' => 'required|integer',
            'lives.*.youtube_url' => 'required|string'
        ]);

        $results = [];
        $apiKey = env('YOUTUBE_API_KEY');
        
        if (!$apiKey) {
            // 沒有 API Key，全部返回 unknown
            foreach ($request->lives as $live) {
                $results[$live['id']] = [
                    'is_live' => false,
                    'status' => 'no_api_key'
                ];
            }
            return response()->json([
                'success' => true,
                'results' => $results
            ]);
        }

        // 收集所有影片 ID
        $videoIds = [];
        $idMapping = [];
        
        foreach ($request->lives as $live) {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', 
                      $live['youtube_url'], $matches);
            if (isset($matches[1])) {
                $videoIds[] = $matches[1];
                $idMapping[$matches[1]] = $live['id'];
            }
        }

        // 批次查詢 YouTube API（最多 50 個）
        $chunks = array_chunk($videoIds, 50);
        
        foreach ($chunks as $chunk) {
            $cacheKey = 'youtube_status_' . md5(implode(',', $chunk));
            
            // 使用快取（30 秒）
            $data = cache()->remember($cacheKey, 30, function() use ($chunk, $apiKey) {
                $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,liveStreamingDetails',
                    'id' => implode(',', $chunk),
                    'key' => $apiKey
                ]);
                
                return $response->successful() ? $response->json() : null;
            });

            if ($data && !empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $videoId = $item['id'];
                    $liveId = $idMapping[$videoId];
                    
                    $results[$liveId] = [
                        'is_live' => $item['snippet']['liveBroadcastContent'] === 'live',
                        'status' => $item['snippet']['liveBroadcastContent'],
                        'viewers' => $item['liveStreamingDetails']['concurrentViewers'] ?? null
                    ];
                }
            }
        }

        // 沒有結果的設為 false
        foreach ($request->lives as $live) {
            if (!isset($results[$live['id']])) {
                $results[$live['id']] = [
                    'is_live' => false,
                    'status' => 'not_found'
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * 檢查單一 YouTube 直播狀態
     */
    public function checkStatus(Request $request)
    {
        $request->validate([
            'youtube_url' => 'required|string'
        ]);

        try {
            // 從 URL 提取影片 ID
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/', 
                      $request->youtube_url, $matches);
            
            if (!isset($matches[1])) {
                return response()->json([
                    'success' => false,
                    'message' => '無效的 YouTube URL'
                ], 400);
            }

            $videoId = $matches[1];
            
            // 使用 YouTube API 檢查狀態（如果有 API Key）
            $apiKey = env('YOUTUBE_API_KEY');
            
            if ($apiKey) {
                $response = Http::get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,liveStreamingDetails',
                    'id' => $videoId,
                    'key' => $apiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!empty($data['items'])) {
                        $item = $data['items'][0];
                        $isLive = $item['snippet']['liveBroadcastContent'] === 'live';
                        
                        return response()->json([
                            'success' => true,
                            'is_live' => $isLive,
                            'status' => $item['snippet']['liveBroadcastContent'],
                            'viewers' => $item['liveStreamingDetails']['concurrentViewers'] ?? null,
                            'title' => $item['snippet']['title'] ?? null
                        ]);
                    }
                }
            }
            
            // 沒有 API Key 時，返回 false 並說明無法確認狀態
            return response()->json([
                'success' => true,
                'is_live' => false,
                'status' => 'no_api_key',
                'message' => 'YouTube API 未設定，無法確認直播狀態'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '檢查失敗：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 取得所有直播列表
     */
    public function index(Request $request)
    {
        $data = $this->liveService->getPageData();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}