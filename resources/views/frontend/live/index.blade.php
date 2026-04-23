@extends('frontend.layouts.app')

@section('meta_title', __('frontend.nav.live') . ' = ' . ($siteInfo['title'] ?? '信吉衛視'))

{{-- JSON-LD 結構化資料 --}}
@push('head')
@if(isset($currentLive) && $currentLive)
    {!! \App\Facades\JsonLd::generateByType('live', $currentLive) !!}
@endif
@endpush
@section('content')
<section class="section-live-feed-detail">
        <div class="breadcrumb-div">
            <i></i>
            <span></span>
            {{ __('frontend.nav.live') }}
        </div>
        <div class="block-div block-01">
            <div class="block-outer">
                {{-- Vue 組件容器 --}}
                <live-player
                    :initial-lives='@json($lives)'
                    :initial-current-id="{{ $currentId ?? 'null' }}">

                    {{-- 初始 HTML 結構（SEO 友好，Vue 載入前顯示） --}}
                    @if(!empty($lives) && count($lives) > 0)
                        <div class="live-feed-div">
                            <div class="two-cols">
                                <div class="col01">
                                <div class="video">
                                    <div class="img" style="background-image: url({{ $currentLive['thumbnail'] ?? '/frontend/images/live_feed_img_01.png' }});">
                                        {{-- 只顯示預覽圖，iframe 由 Vue 處理 --}}
                                        <img src="{{ $currentLive['thumbnail'] ?? '/frontend/images/live_feed_img_01.png' }}" alt="{{ $currentLive['title'] ?? '' }}">
                                    </div>
                                    <div class="info">
                                        {{-- 直播中標示由 Vue 根據實際狀態顯示 --}}
                                    </div>
                                </div>
                            </div>
                            @if(!empty($sidebarLives) && count($sidebarLives) > 0)
                                <div class="col02">
                                    <div class="video-list">
                                        @foreach($sidebarLives as $live)
                                            <div class="item">
                                                <a href="{{ route('live.index', $live['id']) }}">
                                                    <div class="img">
                                                        <img src="{{ $live['thumbnail'] ?? '/frontend/images/live_feed_img_01.png' }}" alt="{{ $live['title'] }}">
                                                    </div>
                                                    <div class="info">
                                                        <div class="desc">
                                                            <h3>{{ $live['title'] }}</h3>
                                                            {{-- 直播中標示由 Vue 根據實際狀態顯示 --}}
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @else
                        <div class="no-live-container" style="text-align: center; padding: 80px 20px;">
                            <h3 style="color: rgb(255, 255, 255);">{{ __('frontend.status.no_live_current') }}</h3>
                        </div>
                    @endif
                </live-player>

                <!-- 直播觀看記錄組件 -->
                @if(isset($currentId) && $currentId)
                    <view-recorder
                        content-type="live"
                        :content-id="{{ $currentId }}"
                        :delay="5000">
                    </view-recorder>
                @endif
            </div>
        </div>
</section>
@endsection
