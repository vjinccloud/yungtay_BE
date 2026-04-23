{{-- 郵件模板共用 CSS 樣式 --}}
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
    .info-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #667eea;
    }
    .info-row {
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
    }
    .info-row:last-child {
        margin-bottom: 0;
    }
    .info-label {
        font-weight: 600;
        color: #2c3e50;
        min-width: 100px;
        margin-right: 15px;
    }
    .info-value {
        color: #555;
        flex: 1;
        word-break: break-word;
    }
    .message-content {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        margin: 20px 0;
        white-space: pre-wrap;
        word-wrap: break-word;
        color: #333;
        line-height: 1.8;
    }
    .action-button, .reset-button {
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
    .action-button:hover, .reset-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: #ffffff !important;
        text-decoration: none !important;
    }
    .button-container {
        text-align: center;
        margin: 30px 0;
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
    .timestamp {
        background: #e3f2fd;
        color: #1565c0;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #2196f3;
        margin-top: 20px;
        font-size: 14px;
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
        .action-button, .reset-button {
            padding: 12px 30px;
            font-size: 15px;
        }
        .info-row {
            flex-direction: column;
        }
        .info-label {
            margin-bottom: 5px;
        }
    }
</style>