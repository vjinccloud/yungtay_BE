<?php
return [
    'required' => '此欄位為必填欄位。',
    'email' => '請輸入有效的電子郵件地址。',
    'confirmed' => '確認密碼欄位不符。',
    'min.string' => '不可低於:min個字元。',
    "unique"=> '此Email已有帳號,請重新選擇。',
    'captcha' => '驗證碼錯誤',
    'url' => '請輸入網址',
    'password.confirmed' => '密碼確認不匹配',
    'password.min' => '密碼長度必須至少為 8 個字符',
    'password.regex' => '密碼必須包含由數字和至少 1 個大寫及 1 個小寫字母組成 8 個以上的字符',
    'password_confirmation.required' => '請再次確認密碼',
    'password_confirmation.min' => '密碼確認的長度必須至少為 8 個字符',
    'password_confirmation.regex' => '密碼確認必須包含由數字和至少 1 個大寫及 1 個小寫字母組成 8 個以上的字符',
    
    // 會員系統專用驗證
    'date' => '請選擇有效的日期。',
    'in' => '選擇的選項無效。',
    'numeric' => '此欄位必須為數字。',
    'image' => '此欄位必須為圖片檔案。',
    'mimes' => '檔案格式不正確，僅接受：:values。',
    'max.file' => '檔案大小不得超過 :max KB。',
    'dimensions' => '圖片尺寸不符合要求。',
    'boolean' => '此欄位必須為是或否。',
    'exists' => '選擇的選項不存在。',
    'integer' => '此欄位必須為整數。',
    'between' => [        
        'numeric' => '數值必須在 :min 到 :max 之間。',
        'string' => '字元長度必須在 :min 到 :max 之間。',
    ],
    'max' => [
        'numeric' => '數值不得大於 :max。',
        'string' => '字元長度不得超過 :max 個。',
    ],
    'phone' => '請輸入有效的電話號碼格式。',
    'taiwan_phone' => '請輸入有效的台灣電話號碼。',
    'after' => '日期必須在 :date 之後。',
    'before' => '日期必須在 :date 之前。',
    'after_or_equal' => '日期必須在 :date 當天或之後。',
    'before_or_equal' => '日期必須在 :date 當天或之前。',
    'password' => [
        'same_as_current' => '新密碼不能與目前密碼相同，請設定不同的密碼',
    ],

];
