<?php
namespace App\Services;

use App\Repositories\RadioRepository;

class RadioService extends BaseService
{
    public function __construct(
        private RadioRepository $radio,
        private RadioThemeService $radioThemeService,
    ) {
        parent::__construct($radio);
    }

    // 覆寫此方法，表示這個模型有圖片
    protected function hasImages($model)
    {
        return true;
    }

    // 自訂圖片刪除邏輯
    protected function deleteRelatedImages($model)
    {
        // Radio 使用 image() 關聯（封面圖）
        if ($model->image) {
            $this->imageRepository->deleteImgFile($model->image);
            $this->imageRepository->deleteImg($model->image);
        }

        // Banner 桌機版圖片
        if ($model->bannerDesktop) {
            $this->imageRepository->deleteImgFile($model->bannerDesktop);
            $this->imageRepository->deleteImg($model->bannerDesktop);
        }

        // Banner 手機版圖片
        if ($model->bannerMobile) {
            $this->imageRepository->deleteImgFile($model->bannerMobile);
            $this->imageRepository->deleteImg($model->bannerMobile);
        }
    }

    protected function setStatusChangeEventType($model)
    {
        $action = $model->is_active == 1 ? '啟用' : '停用';
        $title = $model->getTranslation('title', 'zh_TW') ?? '';
        $model->event_status_title = "廣播{$action}-{$title}";
    }

    /**
     * 取得格式化的資料（給編輯表單用）
     */
    public function getFormData($id)
    {
        return $this->radio->getDetail($id);
    }

    /**
     * 驗證是否有集數資料
     * - radioId 為 null：檢查當前管理員的暫存集數
     * - radioId 不為 null：檢查指定廣播的集數
     *
     * @param int|null $radioId
     * @throws \Exception
     */
    protected function validateHasEpisodes($radioId = null)
    {
        $radioEpisodeRepo = app(\App\Repositories\RadioEpisodeRepository::class);

        if (!$radioEpisodeRepo->hasEpisodes($radioId)) {
            throw new \Exception('無集數資料！請切換到「集數管理」標籤新增集數。');
        }
    }

    /**
     * 覆寫 save 方法，新增成功後跳轉到列表頁
     */
    public function save(array $attributes, $id = null)
    {
        try {
            \DB::beginTransaction();

            // 驗證是否有集數（新增時檢查暫存集數，編輯時檢查該廣播的集數）
            $this->validateHasEpisodes($id);

            // 儲存廣播主資料
            $radio = $this->repository->save($attributes, $id);

            // 如果是新增，更新暫存集數的 radio_id
            if (is_null($id)) {
                $radioEpisodeRepo = app(\App\Repositories\RadioEpisodeRepository::class);
                $updatedCount = $radioEpisodeRepo->updateTempEpisodesRadioId($radio->id);
                \Log::info("廣播新增成功，更新了 {$updatedCount} 筆暫存集數的 radio_id", [
                    'radio_id' => $radio->id
                ]);
            }

            \DB::commit();

            // 觸發對應的事件
            if (is_null($id)) {
                $this->eventService->fireDataCreated($radio);
                $message = '廣播新增成功';
            } else {
                $this->eventService->fireDataUpdated($radio);
                $message = '廣播更新成功';
            }

            // 清除首頁快取和主題快取
            $this->clearHomePageCache();
            $this->radioThemeService->clearFrontendCache();

            $result = $this->ReturnHandle(true, $message);

            // 如果是新增（$id 為 null），設定重定向到列表頁
            if (is_null($id)) {
                $result['redirect'] = route('admin.radios');
            }

            return $result;

        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->ReturnHandle(false, $e->getMessage());
        }
    }

    /**
     * 覆寫 delete 方法，刪除後清除快取
     */
    public function delete($id)
    {
        $result = parent::delete($id);

        // 清除首頁快取和主題快取
        $this->clearHomePageCache();
        $this->radioThemeService->clearFrontendCache();

        return $result;
    }

    /**
     * 覆寫 checkedStatus 方法，狀態變更後清除快取
     */
    public function checkedStatus($id, $statusField = null)
    {
        $result = parent::checkedStatus($id, $statusField);

        // 清除首頁快取和主題快取
        $this->clearHomePageCache();
        $this->radioThemeService->clearFrontendCache();

        return $result;
    }

    /**
     * 前台廣播列表（分頁）
     */
    public function getFrontendList($perPage = 20, $categoryId = null)
    {
        $filters = [];
        if ($categoryId) {
            $filters['category_id'] = $categoryId;
        }

        return $this->radio->getFrontendList($filters, $perPage);
    }

    /**
     * 取得廣播詳情（前台用）
     */
    public function getRadioDetail($id)
    {
        return $this->radio->getDetail($id);
    }

    /**
     * 取得前台廣播詳情（根據語系處理好所有資料）
     */
    public function getFrontendRadioDetail($id)
    {
        return $this->radio->getFrontendDetail($id);
    }

    /**
     * 覆寫模組標題
     */
    protected function getModuleTitle()
    {
        return '廣播 - 信吉衛視';
    }

    /**
     * 取得廣播詳情頁 SEO（覆寫父類方法）
     */
    public function getDetailSEO($radioData)
    {

        // 處理廣播資料結構
        $radio = $radioData;

        return [
            'title' => $radio['title'].' - '.$this->getModuleTitle(),
            'description' =>  strip_tags($radio['media_name']) ?? null,
            'og_image' => isset($radio['image']) ? $this->resolveImageUrl($radio['image']) : null,
        ];
    }

    /**
     * 取得精選廣播（用於 JSON-LD CollectionPage）
     */
    public function getFeaturedRadios(int $limit = 3): array
    {
        return $this->radio->getFrontendList([], $limit)->items();
    }

    /**
     * 根據主分類 ID 取得子分類列表
     *
     * @param int $categoryId 主分類 ID
     * @return \Illuminate\Support\Collection
     */
    public function getSubcategoriesByCategoryId(int $categoryId)
    {
        return $this->radio->getSubcategoriesByCategoryId($categoryId);
    }

    /**
     * 取得所有廣播選項（供下拉選單使用）
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllRadioOptions()
    {
        return $this->radio->getAllForSelect();
    }

    /**
     * 取得廣播集數的最大季數
     * 用於計算季數下拉選單的最低限制
     *
     * @param int|null $radioId 廣播 ID（null 表示新增模式，檢查暫存集數）
     * @return int 有集數的最大季數，若無集數則返回 0
     */
    public function getMaxSeasonWithEpisodes($radioId = null)
    {
        $radioEpisodeRepo = app(\App\Repositories\RadioEpisodeRepository::class);
        return $radioEpisodeRepo->getMaxSeasonWithEpisodes($radioId);
    }

    /**
     * 前台篩選廣播（供 API 使用）
     *
     * @param array $filters 篩選條件（category_id, subcategories, years）
     * @param int $perPage 每頁數量
     * @return array
     */
    public function getFilteredRadios(array $filters = [], $perPage = 18)
    {
        try {
            $paginator = $this->radio->getFilteredRadios($filters, $perPage);

            return [
                'success' => true,
                'radios' => $paginator->items(),
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'filters' => $filters
            ];
        } catch (\Exception $e) {
            \Log::error('廣播篩選失敗: ' . $e->getMessage());

            return [
                'success' => false,
                'radios' => [],
                'total' => 0,
                'message' => '篩選失敗，請稍後再試'
            ];
        }
    }
}
