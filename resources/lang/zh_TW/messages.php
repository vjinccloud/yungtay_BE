<?php

return [
    // 通用成功訊息
    'success' => [
        'created' => '新增成功',
        'updated' => '更新成功',
        'deleted' => '刪除成功',
        'saved' => '儲存成功',
        'sent' => '發送成功',
        'completed' => '操作完成',
        'processed' => '處理成功',
        'cleared' => '清除成功',
        'reset' => '重設成功',
        'sorted' => '排序成功',
        'imported' => '匯入成功',
        'exported' => '匯出成功',
    ],

    // 通用錯誤訊息
    'error' => [
        'general' => '操作失敗，請稍後再試',
        'not_found' => '找不到指定的資料',
        'unauthorized' => '您沒有權限執行此操作',
        'forbidden' => '存取被拒絕',
        'validation_failed' => '資料驗證失敗',
        'database_error' => '資料庫操作失敗',
        'network_error' => '網路連線失敗，請檢查網路狀態',
        'file_error' => '檔案處理失敗',
        'permission_denied' => '權限不足',
        'rate_limit' => '操作過於頻繁，請稍後再試',
        'system_maintenance' => '系統維護中，請稍後再試',
    ],

    // 會員系統訊息
    'member' => [
        // 註冊相關
        'register_success' => '註冊成功，驗證信已寄到您的信箱，請點擊驗證連結完成註冊。',
        'register_success_no_email' => '註冊成功，但驗證信發送失敗，請稍後重新發送。',
        'register_failed' => '註冊失敗，請稍後再試',
        'username_taken' => '用戶名已經被使用',
        'account_disabled' => '帳號已被停用，請聯繫客服',
        
        // 登入相關
        'login_success' => '登入成功',
        'login_failed' => '登入失敗，請稍後再試',
        'login_invalid_credentials' => 'Email 或密碼錯誤',
        'logout_success' => '登出成功',
        'logout_failed' => '登出失敗，請稍後再試',
        'please_login' => '請先登入',
        
        // 個人資料
        'profile_updated' => '資料更新成功',
        'profile_update_failed' => '更新失敗，請稍後再試',
        'profile_completed' => '註冊完成！歡迎加入 SJTV',
        'profile_complete_required' => '您的資料已完整，無需再次填寫',
        'user_not_found' => '用戶不存在',
        
        // 密碼重設
        'reset_email_sent' => '密碼重設信已發送，請檢查您的信箱',
        'reset_email_failed' => '郵件發送失敗，請稍後再試',
        'reset_success' => '密碼重設成功，請使用新密碼登入',
        'reset_failed' => '重設失敗，請稍後再試',
        'reset_link_invalid' => '重設連結無效或已過期，請重新操作忘記密碼',
        'reset_link_expired' => '重設連結已過期，請重新操作忘記密碼',
        'reset_not_found' => '找不到此 Email 的用戶',
        'reset_limit_exceeded' => '今日請求次數已達上限，請明天再試',
    ],

    // 密碼重設
    'password_reset' => [
        'link_sent_success' => '密碼重設連結已寄送至您的信箱，請至信箱收取並點擊連結重設密碼。連結有效期限為 1 小時。',
        'link_send_failed' => '密碼重設連結發送失敗，請稍後再試。',
        'reset_success' => '密碼重設成功！請使用新密碼登入',
        
        // Email 驗證
        'email_verification_sent' => '驗證信已重新發送，請檢查您的信箱',
        'email_verification_failed' => '發送驗證信失敗',
        'email_verify_success' => 'Email 驗證成功',
        'email_verify_failed' => '驗證失敗，請稍後再試',
        'email_verify_invalid' => '驗證連結無效或已過期',
        'email_verify_expired' => '驗證連結已過期，請重新申請驗證信',
        
        // 第三方登入
        'social_login_success' => '登入成功！',
        'social_bind_success' => '成功綁定並登入！',
        'social_register_success' => '註冊成功！請完善個人資料以使用會員功能',
        'social_login_failed' => '登入過程中發生錯誤，請稍後再試',
        'social_unbind_success' => '解綁成功',
        'social_unbind_failed' => '解綁失敗，請稍後再試',
        'social_account_not_found' => '找不到要解綁的帳號',
        'social_auth_cancelled' => '用戶取消授權或授權失敗',
    ],

    // 內容管理訊息
    'content' => [
        'not_found' => '找不到該',
        'video_not_found' => '找不到該影片',
        'episode_delete_success' => '刪除成功',
        'episode_delete_failed' => '刪除失敗',
        'sort_success' => '排序更新成功',
        'sort_failed' => '排序失敗',
        'batch_delete_success' => '成功刪除 :count 筆資料',
        'batch_delete_failed' => '批量刪除失敗',
        'subcategory_delete_success' => '子分類刪除成功',
        'module_delete_success' => '刪除成功',
    ],

    // 觀看數統計訊息
    'view' => [
        'record_success' => '觀看記錄成功',
        'record_failed' => '記錄觀看失敗',
        'cleanup_success' => '清理完成',
        'cleanup_failed' => '清理失敗',
        'reset_success' => '日觀看數重置完成',
        'reset_failed' => '重置失敗',
        'ranking_update_success' => '排行榜更新完成',
        'ranking_update_failed' => '排行榜更新失敗',
        'ranking_cleanup_success' => '舊排行榜清理完成',
        'ranking_cleanup_failed' => '清理失敗',
    ],

    // 系統管理訊息
    'system' => [
        'dashboard_update_success' => '數據更新完成（含快速同步）',
        'dashboard_update_failed' => '更新數據失敗',
        'dashboard_recalculate_success' => '重新計算完成！已清理過期數據並同步 Redis',
        'dashboard_recalculate_failed' => '重新計算失敗',
        'role_delete_failed' => '刪除失敗,該角色權限已被使用',
        'role_delete_success' => '刪除成功',
        'news_type_save_success' => '送出成功',
        'news_type_delete_success' => '刪除成功',
        'cache_cleared' => '快取已清除',
        'maintenance_mode_on' => '維護模式已開啟',
        'maintenance_mode_off' => '維護模式已關閉',
    ],

    // 頁面標題
    'page_title' => [
        'member_login' => '會員登入',
        'member_register' => '會員註冊', 
        'member_center' => '會員中心',
        'watch_history' => '觀看歷史',
        'view_statistics' => '觀看統計',
        'complete_profile' => '完成註冊',
        'email_verification' => 'Email 驗證',
        'forgot_password' => '忘記密碼',
        'reset_password' => '重設密碼',
        'drama_type' => '影音',
        'program_type' => '節目',
    ],

    // 狀態訊息
    'status' => [
        'active' => '啟用',
        'inactive' => '停用',
        'pending' => '待處理',
        'approved' => '已核准',
        'rejected' => '已拒絕',
        'processing' => '處理中',
        'completed' => '已完成',
        'cancelled' => '已取消',
        'expired' => '已過期',
        'verified' => '已驗證',
        'unverified' => '未驗證',
    ],
];