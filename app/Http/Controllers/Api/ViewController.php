<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ViewService;
use App\Services\ViewAnalyticsService;
use App\Services\ViewRankingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ViewController extends Controller
{
    protected $viewService;
    protected $analyticsService;
    protected $rankingService;

    public function __construct(
        ViewService $viewService,
        ViewAnalyticsService $analyticsService,
        ViewRankingService $rankingService
    ) {
        $this->viewService = $viewService;
        $this->analyticsService = $analyticsService;
        $this->rankingService = $rankingService;
    }

    /**
     * 記錄訪客觀看數（不需登入）
     */
    public function recordGuest(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content_type' => ['required', Rule::in(['drama', 'program', 'article', 'live', 'radio'])],
                'content_id' => 'required|integer|min:1',
                'episode_id' => 'nullable|integer|min:1'
            ]);

            $result = $this->viewService->recordView(
                $validated['content_type'],
                $validated['content_id'],
                $validated['episode_id'] ?? null,
                null // 訪客觀看，無用戶 ID
            );

            return response()->json($result, $result['status'] ? 200 : 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'msg' => '參數驗證失敗',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Record view API error', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '記錄觀看失敗'
            ], 500);
        }
    }

    /**
     * 取得觀看數
     */
    public function getCount(Request $request, string $contentType, int $contentId): JsonResponse
    {
        try {
            // 驗證 content_type
            if (!in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $episodeId = $request->query('episode_id') ? (int) $request->query('episode_id') : null;

            $data = [
                'total_views' => $this->viewService->getViewCount($contentType, $contentId, $episodeId),
                'unique_views' => $this->viewService->getUniqueViewCount($contentType, $contentId, $episodeId),
                'today_views' => $this->viewService->getTodayViewCount($contentType, $contentId, $episodeId),
            ];

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get view count API error', [
                'content_type' => $contentType,
                'content_id' => $contentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得觀看數失敗'
            ], 500);
        }
    }

    /**
     * 批量取得觀看數
     */
    public function getBatchCounts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1|max:50',
                'items.*.content_type' => ['required', Rule::in(['drama', 'program', 'article', 'live', 'radio'])],
                'items.*.content_id' => 'required|integer|min:1',
                'items.*.episode_id' => 'nullable|integer|min:1'
            ]);

            $data = $this->viewService->getBatchViewCounts($validated['items']);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'msg' => '參數驗證失敗',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Batch get view counts API error', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '批量取得觀看數失敗'
            ], 500);
        }
    }

    /**
     * 取得熱門內容
     */
    public function getTrending(Request $request, string $contentType): JsonResponse
    {
        try {
            // 驗證 content_type
            if (!in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $limit = min((int) $request->query('limit', 10), 50); // 最多50筆
            $days = min((int) $request->query('days', 7), 30); // 最多30天

            $data = $this->viewService->getPopularContent($contentType, $limit, $days);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data->map(function($item) {
                    return [
                        'content_id' => $item->content_id,
                        'view_count' => $item->view_count
                    ];
                })
            ]);

        } catch (\Exception $e) {
            \Log::error('Get trending API error', [
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得熱門內容失敗'
            ], 500);
        }
    }

    /**
     * 取得排行榜
     */
    public function getRankings(Request $request, string $contentType): JsonResponse
    {
        try {
            // 驗證 content_type
            if (!in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $period = $request->query('period', 'weekly');
            $limit = min((int) $request->query('limit', 10), 50);

            // 驗證 period
            if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的期間類型'
                ], 400);
            }

            $data = $this->rankingService->getTopContent($contentType, $period, $limit);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get rankings API error', [
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得排行榜失敗'
            ], 500);
        }
    }

    /**
     * 取得跨類型綜合排行榜
     */
    public function getCrossRankings(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'weekly');
            $limit = min((int) $request->query('limit', 20), 50);

            // 驗證 period
            if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的期間類型'
                ], 400);
            }

            $data = $this->rankingService->getCrossTypeRankings($period, $limit);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get cross rankings API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得綜合排行榜失敗'
            ], 500);
        }
    }

    /**
     * 取得上升最快內容
     */
    public function getFastestRising(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'weekly');
            $contentType = $request->query('content_type');
            $limit = min((int) $request->query('limit', 10), 50);

            // 驗證 period
            if (!in_array($period, ['daily', 'weekly', 'monthly'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的期間類型'
                ], 400);
            }

            // 驗證 content_type（如果提供）
            if ($contentType && !in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $data = $this->rankingService->getFastestRising($period, $contentType, $limit);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get fastest rising API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得上升最快內容失敗'
            ], 500);
        }
    }

    /**
     * 取得分析報告
     */
    public function getAnalytics(Request $request, string $contentType): JsonResponse
    {
        try {
            // 驗證 content_type
            if (!in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $period = $request->query('period', 'week');

            // 驗證 period
            if (!in_array($period, ['today', 'week', 'month', 'quarter', 'year'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的期間類型'
                ], 400);
            }

            $data = $this->analyticsService->getAnalyticsReport($contentType, $period);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get analytics API error', [
                'content_type' => $contentType,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得分析報告失敗'
            ], 500);
        }
    }

    /**
     * 取得觀看趨勢
     */
    public function getViewTrend(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content_type' => ['required', Rule::in(['drama', 'program', 'article', 'live', 'radio'])],
                'content_id' => 'required|integer|min:1',
                'episode_id' => 'nullable|integer|min:1',
                'days' => 'nullable|integer|min:1|max:90'
            ]);

            $days = $validated['days'] ?? 30;

            $data = $this->analyticsService->getViewTrend(
                $validated['content_type'],
                $validated['content_id'],
                $days,
                $validated['episode_id'] ?? null
            );

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'msg' => '參數驗證失敗',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Get view trend API error', [
                'request' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得觀看趨勢失敗'
            ], 500);
        }
    }

    /**
     * 取得用戶觀看歷史
     */
    public function getUserHistory(Request $request): JsonResponse
    {
        try {
            if (!auth()->check()) {
                return response()->json([
                    'status' => false,
                    'msg' => '需要登入'
                ], 401);
            }

            $limit = min((int) $request->query('limit', 50), 100);
            $contentTypes = $request->query('content_types', []);

            // 驗證 content_types
            if (!empty($contentTypes)) {
                $validTypes = ['drama', 'program', 'article', 'live', 'radio'];
                $contentTypes = array_intersect($contentTypes, $validTypes);
            }

            $data = $this->viewService->getUserViewHistory(auth()->id(), $limit, $contentTypes);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data->map(function($item) {
                    return [
                        'content_type' => $item->content_type,
                        'content_id' => $item->content_id,
                        'episode_id' => $item->episode_id,
                        'viewed_at' => $item->created_at->format('Y-m-d H:i:s')
                    ];
                })
            ]);

        } catch (\Exception $e) {
            \Log::error('Get user history API error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得觀看歷史失敗'
            ], 500);
        }
    }

    /**
     * 取得熱門時段分析
     */
    public function getPopularTimeSlots(Request $request): JsonResponse
    {
        try {
            $contentType = $request->query('content_type');
            $days = min((int) $request->query('days', 7), 30);

            // 驗證 content_type（如果提供）
            if ($contentType && !in_array($contentType, ['drama', 'program', 'article', 'live', 'radio'])) {
                return response()->json([
                    'status' => false,
                    'msg' => '無效的內容類型'
                ], 400);
            }

            $data = $this->analyticsService->getPopularTimeSlots($contentType, $days);

            return response()->json([
                'status' => true,
                'msg' => '取得成功',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Get popular time slots API error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => false,
                'msg' => '取得熱門時段失敗'
            ], 500);
        }
    }
}