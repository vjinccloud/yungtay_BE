<?php

namespace App\Observers;

use App\Models\MemberNotification;
use App\Services\EventService;

class MemberNotificationObserver
{
    protected $eventService;

    public function __construct()
    {
        $this->eventService = app(EventService::class);
    }

    /**
     * 取得事件標題
     */
    protected function getEventTitle($memberNotification): string
    {
        $title = $memberNotification->getTranslation('title', 'zh_TW') ;

        return "會員通知-{$title}";
    }

    /**
     * Handle the MemberNotification "created" event.
     */
    public function created(MemberNotification $memberNotification): void
    {
        // 設定事件類型
        $memberNotification->event_type = 'Add';

        // 直接設定事件標題，包含主旨
        $memberNotification->event_title = $this->getEventTitle($memberNotification);

        // 觸發 DataCreated 事件
        $this->eventService->fireDataCreated($memberNotification);
    }
}