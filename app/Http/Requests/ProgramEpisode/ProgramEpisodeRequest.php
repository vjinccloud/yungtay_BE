<?php

namespace App\Http\Requests\ProgramEpisode;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\YouTubeHelper;

class ProgramEpisodeRequest extends FormRequest
{
   /**
    * Determine if the user is authorized to make this request.
    */
   public function authorize(): bool
   {
       return true;
   }

   /**
    * Get the validation rules that apply to the request.
    *
    * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
    */
   public function rules(): array
   {
       $isEditing = $this->route('id') !== null;

       return [
           // 基本欄位 - program_id 允許為0（新增節目時的暫存狀態）
           'program_id' => 'nullable|integer|min:0',
           'season' => 'nullable|integer|min:1',
           'seq' => 'nullable|integer|min:1',

           // 影片類型和來源
           'video_type' => 'required|in:youtube,upload',
           'youtube_url' => 'required_if:video_type,youtube|nullable|url',

           // 上傳檔案相關
           'video_file' => $this->getVideoFileRule($isEditing),
           'video_file_path' => 'required_if:video_type,upload|nullable|string',
           'original_filename' => 'nullable|string|max:255',
           'file_size' => 'nullable|numeric|min:0|max:1024', // 限制1GB
           'video_format' => 'nullable|string|in:mp4,avi,mov,wmv,mkv',

           // 多語系欄位 - 時長
           'duration_text' => 'required|array',
           'duration_text.zh_TW' => 'required|string|max:50',
           'duration_text.en' => 'required|string|max:50',

           // 多語系欄位 - 描述
           'description' => 'required|array',
           'description.zh_TW' => 'required|string|max:1000',
           'description.en' => 'required|string|max:1000',
       ];
   }

   /**
    * 取得影片檔案驗證規則
    */
   protected function getVideoFileRule($isEditing)
   {
       // 如果是編輯模式且選擇上傳，但沒有新檔案，則不驗證
       if ($isEditing && $this->input('video_type') === 'upload') {
           return 'nullable|string';
       }

       // 新增模式且選擇上傳，必須有檔案
       if ($this->input('video_type') === 'upload') {
           return 'required|string';
       }

       return 'nullable|string';
   }

   /**
    * Get custom messages for validator errors.
    */
   public function messages(): array
   {
       return [
           // 基本欄位錯誤訊息
           'program_id.required' => '節目ID為必填欄位',
           'program_id.integer' => '節目ID必須為數字',
           'program_id.min' => '節目ID不能為負數',
           'season.min' => '季數必須大於0',
           'seq.min' => '集數必須大於0',

           // 影片類型錯誤訊息
           'video_type.required' => '請選擇影片來源類型',
           'video_type.in' => '影片來源類型只能是 YouTube 或本機上傳',

           // YouTube URL 錯誤訊息
           'youtube_url.required_if' => '選擇 YouTube 時必須輸入影片網址',
           'youtube_url.url' => '請輸入有效的網址格式',
           'youtube_url.regex' => '請輸入有效的 YouTube 網址',

           // 上傳檔案錯誤訊息
           'video_file.required' => '請選擇要上傳的影片檔案',
           'video_file_path.required_if' => '選擇本機上傳時必須有檔案路徑',
           'original_filename.max' => '檔案名稱不能超過255個字元',
           'file_size.max' => '檔案大小不能超過 1GB',
           'file_size.min' => '檔案大小不能為負數',
           'video_format.in' => '不支援的影片格式，僅支援 mp4, avi, mov, wmv, mkv',

           // 時長錯誤訊息
           'duration_text.required' => '影片時長為必填欄位',
           'duration_text.zh_TW.required' => '請輸入中文影片時長',
           'duration_text.zh_TW.max' => '中文影片時長不能超過50個字元',
           'duration_text.en.required' => '請輸入英文影片時長',
           'duration_text.en.max' => '英文影片時長不能超過50個字元',

           // 描述錯誤訊息
           'description.required' => '影片描述為必填欄位',
           'description.zh_TW.required' => '請輸入中文影片描述',
           'description.zh_TW.max' => '中文影片描述不能超過1000個字元',
           'description.en.required' => '請輸入英文影片描述',
           'description.en.max' => '英文影片描述不能超過1000個字元',
       ];
   }

   /**
    * Get custom attributes for validator errors.
    */
   public function attributes(): array
   {
       return [
           'program_id' => '節目',
           'season' => '季數',
           'seq' => '集數',
           'video_type' => '影片類型',
           'youtube_url' => 'YouTube網址',
           'video_file' => '影片檔案',
           'video_file_path' => '檔案路徑',
           'original_filename' => '原始檔名',
           'file_size' => '檔案大小',
           'video_format' => '影片格式',
           'duration_text.zh_TW' => '影片時長(中文)',
           'duration_text.en' => '影片時長(英文)',
           'description.zh_TW' => '影片描述(中文)',
           'description.en' => '影片描述(英文)',
       ];
   }

    /**
    * Configure the validator instance.
    */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 自訂驗證：如果 program_id 不為 null 且大於 0，則檢查節目是否存在
            $programId = $this->input('program_id');
            if ($programId !== null && $programId > 0) {
                $programExists = \App\Models\Program::where('id', $programId)->exists();
                if (!$programExists) {
                    $validator->errors()->add('program_id', '指定的節目不存在');
                }
            }

            // 自訂驗證：確保 YouTube 或上傳至少選一個有內容
            if ($this->input('video_type') === 'youtube' && empty($this->input('youtube_url'))) {
                $validator->errors()->add('youtube_url', '選擇 YouTube 時必須輸入影片網址');
            }

            if ($this->input('video_type') === 'upload' && empty($this->input('video_file_path'))) {
                $validator->errors()->add('video_file', '選擇本機上傳時必須選擇影片檔案');
            }

            // 自訂驗證：YouTube URL 格式更嚴格檢查
            if ($this->input('video_type') === 'youtube' && $this->input('youtube_url')) {
                $url = $this->input('youtube_url');
                
                if (!YouTubeHelper::isValidYouTubeUrl($url)) {
                    $validator->errors()->add('youtube_url', '請輸入有效的 YouTube 影片網址（支援格式：youtube.com/watch?v=xxx 或 youtu.be/xxx）');
                }
            }
        });
    }

   /**
    * 準備資料進行驗證（在驗證前自動轉換 YouTube URL）
    */
   protected function prepareForValidation()
   {
       // 如果是 YouTube URL，自動轉換為標準格式
       if ($this->input('video_type') === 'youtube' && $this->input('youtube_url')) {
           $url = $this->input('youtube_url');
           $standardUrl = YouTubeHelper::convertToStandardUrl($url);
           
           if ($standardUrl) {
               // 統一轉換為標準格式
               $this->merge([
                   'youtube_url' => $standardUrl
               ]);
           }
       }
   }
}