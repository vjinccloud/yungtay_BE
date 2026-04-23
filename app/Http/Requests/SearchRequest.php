<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // 搜尋功能對所有人開放
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => [
                'required',
                'string',
                'min:1',
                'max:30',
                'regex:/^[a-zA-Z0-9\x{4e00}-\x{9fff}\s\-_\.]+$/u', // 只允許中英文、數字、空格、連字符、底線、點號
            ],
            'page' => 'sometimes|integer|min:1|max:1000',
            'mode' => 'sometimes|string|in:all,single',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keyword.required' => '請輸入搜尋關鍵字',
            'keyword.string' => '搜尋關鍵字必須是文字',
            'keyword.min' => '搜尋關鍵字至少需要1個字元',
            'keyword.max' => '搜尋關鍵字不能超過30個字元',
            'keyword.regex' => '搜尋關鍵字包含不允許的特殊字元',
            'page.integer' => '頁數必須是數字',
            'page.min' => '頁數必須大於0',
            'page.max' => '頁數超出允許範圍',
            'mode.in' => '搜尋模式參數錯誤',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // XSS 防護：清理關鍵字
        if ($this->has('keyword')) {
            $this->merge([
                'keyword' => $this->sanitizeKeyword($this->keyword),
            ]);
        }
    }

    /**
     * 清理搜尋關鍵字，防止 XSS 攻擊
     *
     * @param string $keyword
     * @return string
     */
    private function sanitizeKeyword(string $keyword): string
    {
        // 移除 HTML 標籤
        $keyword = strip_tags($keyword);
        
        // 移除多餘的空白字元
        $keyword = preg_replace('/\s+/', ' ', $keyword);
        
        // 移除前後空白
        $keyword = trim($keyword);
        
        // HTML 實體編碼（防止 XSS）
        $keyword = htmlspecialchars($keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $keyword;
    }

    /**
     * Get validated keyword (已經過 XSS 防護處理)
     *
     * @return string
     */
    public function getKeyword(): string
    {
        return $this->validated()['keyword'];
    }
}
