<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\TargetType;
use App\Enums\SendMode;

class MemberNotificationRequest extends FormRequest
{
    /**
     * 確認用戶是否有權限發出這個請求
     */
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * 取得應用於請求的驗證規則
     */
    public function rules(): array
    {
        return [
            'title' => 'required|array',
            'title.zh_TW' => 'required|string|max:255',
            'title.en' => 'required|string|max:255',
            'message' => 'required|array',
            'message.zh_TW' => 'required|string|max:2000',
            'message.en' => 'required|string|max:2000',
            'target_type' => ['required', Rule::enum(TargetType::class)],
            'target_user_ids' => 'required_if:target_type,' . TargetType::SPECIFIC->value . '|array',
            'target_user_ids.*' => 'exists:users,id',
            'send_type' => 'required|in:now,scheduled',
            'scheduled_at' => 'nullable|required_if:send_type,scheduled|date|after:now',
        ];
    }

    /**
     * 取得驗證錯誤的自訂訊息
     */
    public function messages(): array
    {
        return [
            'title.required' => '標題為必填項目',
            'title.*.required' => '標題為必填項目',
            'title.*.max' => '標題長度不得超過 255 個字元',
            'message.required' => '訊息內容為必填項目',
            'message.*.required' => '訊息內容為必填項目',
            'message.*.max' => '訊息內容長度不得超過 2000 個字元',
            'target_type.required' => '發送對象為必填項目',
            'target_type.in' => '發送對象必須是全部會員或指定會員',
            'target_user_ids.required_if' => '指定會員時必須選擇至少一位會員',
            'target_user_ids.*.exists' => '選擇的會員不存在',
            'send_type.required' => '發送方式為必填項目',
            'send_type.in' => '發送方式必須是立即發送或排程發送',
            'scheduled_at.required_if' => '排程發送時必須設定發送時間',
            'scheduled_at.date' => '發送時間格式不正確',
            'scheduled_at.after' => '發送時間必須是未來時間',
        ];
    }

    /**
     * 取得自訂屬性名稱
     */
    public function attributes(): array
    {
        return [
            'title.zh_TW' => '中文標題',
            'title.en' => '英文標題',
            'message.zh_TW' => '中文訊息',
            'message.en' => '英文訊息',
            'target_type' => '發送對象',
            'target_user_ids' => '指定會員',
            'send_type' => '發送方式',
            'scheduled_at' => '排程時間',
        ];
    }

    /**
     * 準備驗證資料
     */
    protected function prepareForValidation(): void
    {
        // 確保 target_user_ids 是陣列
        if ($this->target_type === TargetType::ALL->value) {
            $this->merge(['target_user_ids' => []]);
        }

        // 確保 scheduled_at 在非排程模式時為 null
        if ($this->send_type === 'now') {
            $this->merge(['scheduled_at' => null]);
        }
    }
}