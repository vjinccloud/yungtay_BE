<?php

return [
    // 密碼重設郵件
    'password_reset_subject' => ':site 密碼重設通知',
    
    // 密碼變更通知郵件
    'password_changed_subject' => ':site 密碼已變更通知',
    'password_changed_send_failed' => '發送密碼變更通知失敗',
    'password_reset' => [
        'title' => '密碼重設通知',
        'greeting' => '親愛的 :name，您好！',
        'intro_1' => '我們收到了您的密碼重設請求。',
        'intro_2' => '為了確保您的帳戶安全，請點擊下方按鈕來重設您的密碼：',
        'reset_button' => '重設密碼',
        'expires_info' => '重要提醒：此重設連結將在 1 小時後失效，請儘快完成密碼重設。',
        'alternative_title' => '無法點擊按鈕？',
        'alternative_text' => '如果上方按鈕無法正常使用，請複製以下連結到瀏覽器網址列：',
        'security_title' => '安全提醒：',
        'security_1' => '如果您沒有申請密碼重設，請忽略此信件',
        'security_2' => '請勿將此重設連結分享給他人',
        'security_3' => '此重設連結僅能使用一次',
        'footer_contact' => '如有任何問題，請聯繫我們的客服團隊',
        'footer_home' => '回到首頁',
        'footer_service' => '客服信箱',
        'footer_note' => '此信件為系統自動發送，請勿回覆'
    ]
];