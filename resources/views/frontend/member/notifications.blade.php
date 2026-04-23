@extends('frontend.layouts.app')

@section('title', $pageTitle . ' - ' . $siteInfo['title'])

@section('meta')
    <meta name="description" content="{{ $siteInfo['description'] }}">
    <meta property="og:title" content="{{ $pageTitle }} - {{ $siteInfo['title'] }}">
    <meta property="og:description" content="{{ $siteInfo['description'] }}">
    <meta property="og:image" content="{{ asset('frontend/images/favicon.png') }}">
@endsection

@section('content')
<section class="section-member section-member-detail">
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="member-div">
                <div class="member-center">
                    @include('frontend.member.partials.sidebar', ['currentPage' => 'notifications'])

                    <!-- 主要內容區域 -->
                    <div class="member-content member-navi-use-sticky">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ $pageTitle }}</h1>
                            </div>
                        </div>

                        <!-- Vue 組件 -->
                        <member-notification-list
                            :texts="{
                                no_notifications: '{{ __('frontend.member.no_notifications') }}'
                            }">
                        </member-notification-list>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('popups')
@include('frontend.member.partials.popups')
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($) {
        // AOS 動畫初始化
        AOS.init();
    });
</script>
@endpush