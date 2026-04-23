@extends('emails.layouts.base')

@section('title', '客服回覆通知 - ' . $siteName)

@php
    $headerSubtitle = '客服回覆通知';
    $footerLinks = [
        ['url' => url('/'), 'text' => '官方網站'],
        ['url' => url('/customer-service'), 'text' => '客服中心']
    ];
@endphp

@section('content')
    <div class="greeting">
        親愛的 {{ $customerService->name }}，您好
    </div>

    <div class="message">
        <p>感謝您聯絡我們，我們已經回覆您的詢問。</p>
        <p>以下是我們的回覆內容：</p>
    </div>

    <div class="info-box">
        <div class="info-row">
            <div class="info-label">回覆主旨：</div>
            <div class="info-value"><strong>{{ $customerService->reply_subject }}</strong></div>
        </div>

        <div style="font-size: 14px; color: #666;">
            <strong>您的原始詢問：</strong><br>
            {!! nl2br(e($customerService->message)) !!}
        </div>
    </div>

    <div class="message-content">
        <strong>我們的回覆：</strong><br>
        {!! nl2br(e($customerService->reply_content)) !!}
    </div>

    <div class="message">
        <p>如果您還有其他問題，歡迎隨時透過我們的聯絡表單或客服專線與我們聯繫。</p>
        <p>謝謝您選擇我們的服務！</p>
    </div>
@endsection