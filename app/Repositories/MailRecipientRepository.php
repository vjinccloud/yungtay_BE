<?php

namespace App\Repositories;

use App\Models\MailRecipient;
use App\Models\MailType;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MailRecipientRepository extends BaseRepository
{
    /**
     * 建構子
     */
    public function __construct(MailRecipient $model)
    {
        parent::__construct($model);
    }

    /**
     * 儲存收件信箱資料
     */
    public function save(array $attributes = [], $id = null)
    {
        return DB::transaction(function () use ($attributes, $id) {
            // 確保 status 為 boolean
            if (isset($attributes['status'])) {
                $attributes['status'] = (bool) $attributes['status'];
            }

            // 寫入或更新資料
            $mailRecipient = parent::save($attributes, $id);

            return $mailRecipient;
        });
    }

    /**
     * 分頁查詢收件信箱
     */
    public function paginate($perPage = 15, $sortColumn = 'updated_at', $sortDirection = 'desc', $filters = [])
    {
        return $this->model->with(['mailType'])
            ->filter($filters)  // 使用 Model 的 scopeFilter
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($mailRecipient) => [
                'id' => $mailRecipient->id,
                'name' => $mailRecipient->name,
                'email' => $mailRecipient->email,
                'mail_type' => $mailRecipient->mailType,  // 保留完整的關聯物件
                'type_id' => $mailRecipient->type_id,
                'status' => $mailRecipient->status,
                'created_at' => $mailRecipient->created_at?->toDateTimeString() ?? '',
                'updated_at' => $mailRecipient->updated_at?->toDateTimeString() ?? '',
            ]);
    }

    /**
     * 取得啟用的收件信箱
     */
    public function getActiveMailRecipients(): Collection
    {
        return $this->model->with(['mailType'])
                          ->where('status', 1)
                          ->orderBy('type_id')
                          ->orderBy('name')
                          ->get();
    }

    /**
     * 取得所有收件類型
     */
    public function getAllMailTypes(): Collection
    {
        return MailType::where('status', 1)
                       ->orderBy('seq')
                       ->get();
    }
}