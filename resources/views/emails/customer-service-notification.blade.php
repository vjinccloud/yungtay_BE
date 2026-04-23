@extends('emails.layouts.base')

@section('title', '客服中心新訊息通知 - ' . $siteName)

@php
    $headerSubtitle = '客服中心新訊息通知';
    $footerLinks = [
        ['url' => url('/'), 'text' => '官方網站'],
        ['url' => url('/admin/customer-services'), 'text' => '後台管理']
    ];
@endphp

@section('content')
    <div class="greeting">
        您好，管理員
    </div>
    
    <div class="message">
        <p>客服中心收到一則新的訊息，請盡快處理。</p>
        <p>以下是訊息的詳細資訊：</p>
    </div>
    
    <div class="info-box">
        <div class="info-row">
            <div class="info-label">姓名：</div>
            <div class="info-value">{{ $customerService->name }}</div>
        </div>
        
        <div class="info-row">
            <div class="info-label">Email：</div>
            <div class="info-value">
                <a href="mailto:{{ $customerService->email }}" style="color: #667eea; text-decoration: none;">
                    {{ $customerService->email }}
                </a>
            </div>
        </div>
        
        @if($customerService->phone)
        <div class="info-row">
            <div class="info-label">電話：</div>
            <div class="info-value">{{ $customerService->phone }}</div>
        </div>
        @endif
        
        @if($customerService->address)
        <div class="info-row">
            <div class="info-label">地址：</div>
            <div class="info-value">{{ $customerService->address }}</div>
        </div>
        @endif
        
        <div class="info-row">
            <div class="info-label">主旨：</div>
            <div class="info-value"><strong>{{ $customerService->subject }}</strong></div>
        </div>
    </div>
    
    <div class="message-content">
        <strong>訊息內容：</strong><br>
        {!! nl2br(e($customerService->message)) !!}
    </div>
    
    <div class="timestamp">
        <strong>送出時間：</strong> {{ $customerService->created_at->format('Y年m月d日 H:i:s') }}
    </div>
@endsection