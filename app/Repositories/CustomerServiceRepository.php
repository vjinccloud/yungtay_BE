<?php

namespace App\Repositories;

use App\Models\CustomerService;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

class CustomerServiceRepository extends BaseRepository
{
    /**
     * 建構子
     */
    public function __construct(CustomerService $model)
    {
        parent::__construct($model);
    }

    /**
     * 儲存客服訊息
     */
    public function save(array $attributes = [], $id = null)
    {
        return DB::transaction(function () use ($attributes, $id) {
            // 寫入或更新資料
            $customerService = parent::save($attributes, $id);

            return $customerService;
        });
    }

    /**
     * 分頁查詢客服訊息
     */
    public function paginate($perPage = 15, $sortColumn = 'created_at', $sortDirection = 'desc', $filters = [])
    {
        $query = $this->model
            ->with(['repliedBy'])
            ->filter($filters);

        // 總是優先排序：待處理優先
        $query->orderBy('is_replied', 'asc'); // 待處理(0)在前

        // 然後按用戶選擇的欄位排序
        if ($sortColumn && $sortColumn !== 'is_replied') {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            // 預設按提交時間排序
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'phone' => $item->phone,
                'subject' => $item->subject,
                'message' => $item->message,
                'is_replied' => $item->is_replied,
                'replied_by_name' => $item->repliedBy?->name,
                'replied_at' => $item->replied_at?->toDateTimeString(),
                'created_at' => $item->created_at?->toDateTimeString(),
                'updated_at' => $item->updated_at?->toDateTimeString(),
            ]);
    }

    /**
     * 取得特定會員的客服紀錄（分頁）
     */
    public function getUserRecords($userId, $perPage = 10)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'subject' => $item->subject,
                'message' => $item->message,
                'is_replied' => $item->is_replied,
                'reply_subject' => $item->reply_subject,
                'reply_content' => $item->reply_content,
                'replied_at' => $item->replied_at?->format('Y-m-d H:i:s'),
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 取得未回覆的訊息數量
     */
    public function getUnrepliedCount()
    {
        return $this->model->unreplied()->count();
    }

    /**
     * 更新回覆資訊
     */
    public function updateReply($id, array $replyData)
    {
        $customerService = $this->find($id, true);

        $customerService->update([
            'reply_subject' => $replyData['reply_subject'],
            'reply_content' => $replyData['reply_content'],
            'replied_at' => now(),
            'replied_by' => auth('admin')->id(),
            'is_replied' => true,
            // updated_by 由 BaseModelTrait 自動處理
        ]);

        return $customerService;
    }

    /**
     * 更新管理員備註
     */
    public function updateAdminNote($id, $note)
    {
        $customerService = $this->find($id, true);

        $customerService->update([
            'admin_note' => $note,
            // updated_by 由 BaseModelTrait 自動處理
        ]);

        return $customerService;
    }

    /**
     * 更新客服信件狀態
     */
    public function updateStatus($id, $isReplied)
    {
        $customerService = $this->find($id, true);

        // 準備更新資料
        $updateData = [
            'is_replied' => $isReplied,
            // updated_by 由 BaseModelTrait 自動處理
        ];

        // 如果是標記為已回覆且之前沒有回覆時間，設置回覆時間和回覆者
        if ($isReplied && !$customerService->replied_at) {
            $updateData['replied_at'] = now();
            $updateData['replied_by'] = auth('admin')->id();
        }

        // 如果是標記為未回覆，清空回覆相關欄位
        if (!$isReplied) {
            $updateData['replied_at'] = null;
            $updateData['replied_by'] = null;
            $updateData['reply_subject'] = null;
            $updateData['reply_content'] = null;
        }

        $customerService->update($updateData);

        return $customerService;
    }
}