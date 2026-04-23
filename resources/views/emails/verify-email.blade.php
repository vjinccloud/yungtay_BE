<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email 驗證 - {{ $siteName }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .message {
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 30px;
            color: #555;
        }
        .verify-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .verify-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: #ffffff !important;
            text-decoration: none !important;
        }
        /* 確保按鈕不被其他 CSS 規則影響 */
        a.verify-button,
        a.verify-button:link,
        a.verify-button:visited,
        a.verify-button:hover,
        a.verify-button:active {
            color: #ffffff !important;
            text-decoration: none !important;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .alternative {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #667eea;
        }
        .alternative h4 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 16px;
        }
        .alternative p {
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .alternative .url {
            word-break: break-all;
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid #ddd;
        }
        .footer {
            background: #34495e;
            color: white;
            padding: 25px 30px;
            text-align: center;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
            opacity: 0.8;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ffeaa7;
            margin-top: 20px;
            font-size: 14px;
        }
        .expires-info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #2196f3;
            margin-top: 20px;
            font-size: 14px;
        }
        
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 6px;
            }
            .header, .content, .footer {
                padding: 25px 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .verify-button {
                padding: 12px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $siteName }}</h1>
            <p>Email 驗證確認</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                親愛的 {{ $user->name }}，您好！
            </div>

            <div class="message">
                <p>感謝您註冊 <strong>{{ $siteName }}</strong> 會員！</p>
                <p>為了確保您的帳戶安全，請點擊下方按鈕來驗證您的 Email 地址：</p>
            </div>

            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button" style="color: #ffffff !important; text-decoration: none;">
                    驗證 Email 地址
                </a>
            </div>

            <div class="expires-info">
                <strong>重要提醒：</strong>此驗證連結將在 24 小時後失效，請儘快完成驗證。
            </div>

            <!-- Alternative method -->
            <div class="alternative">
                <h4>無法點擊按鈕？</h4>
                <p>如果上方按鈕無法正常使用，請複製以下連結到瀏覽器網址列：</p>
                <div class="url">{{ $verificationUrl }}</div>
            </div>

            <div class="warning">
                <strong>安全提醒：</strong>
                <br>• 如果您沒有註冊此帳戶，請忽略此信件
                <br>• 請勿將此驗證連結分享給他人
                <br>• 此驗證連結僅能使用一次
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>{{ $siteName }}</strong></p>
            <p>如有任何問題，請聯繫我們的客服團隊</p>
            <p>
                <a href="{{ $siteUrl }}">回到首頁</a> | 
                <a href="mailto:service@sjtv.com.tw">客服信箱</a>
            </p>
            <p style="margin-top: 15px; font-size: 12px; opacity: 0.7;">
                此信件為系統自動發送，請勿回覆
            </p>
        </div>
    </div>
</body>
</html>