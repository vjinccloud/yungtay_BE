<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>@yield('title', $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會')</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=yes,viewport-fit=cover">
    <meta name="description" content="@yield('description', $siteInfo['description'] ?? '財團法人新北市為愛前行社會福利基金會')">
    <meta name="keywords" content="@yield('keywords', $siteInfo['keywords'] ?? '財團法人新北市為愛前行社會福利基金會')">
    <meta property="og:title" content="@yield('og_title', $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會')">
    <meta property="og:description" content="@yield('og_description', $siteInfo['description'] ?? '財團法人新北市為愛前行社會福利基金會')">
    <meta property="og:image" content="@yield('og_image', asset('frontend/images/favicon.png'))">
    
    {{-- Favicon --}}
    @if(!empty($siteInfo['favicon']))
        <link rel="shortcut icon" href="{{ asset($siteInfo['favicon']) }}">
    @else
        <link rel="shortcut icon" href="{{ asset('frontend/images/favicon.png') }}">
    @endif

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/swiper-bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/aos.css') }}">

    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/main.css?ver=' . date('YmdHi')) }}">

    @stack('styles')
    @stack('head')
</head>

<body>
    {{-- Loading --}}
    <div class="loading" id="loading">
        <div class="love">
            <img src="{{ asset('frontend/images/icon_loading01.svg') }}">
            <img src="{{ asset('frontend/images/icon_loading02.svg') }}">
        </div>
    </div>

    {{-- Header --}}
    @include('frontend.layouts.afl.header')

    {{-- Main Content --}}
    <main class="@yield('main-class', 'main-home')">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('frontend.layouts.afl.footer')

    {{-- 手機版漢堡選單按鈕 --}}
    <button class="toggle-btn">
        <span></span>
        <span></span>
        <span></span>
    </button>

    {{-- 遮罩層 --}}
    <div class="overlap"></div>

    {{-- Mobile Navigation --}}
    @include('frontend.layouts.afl.mobile-nav')

    {{-- Popups Section --}}
    @yield('popups')

    {{-- Vendor Scripts --}}
    <script src="{{ asset('frontend/js/jquery.min.js') }}"></script>
    <script src="{{ asset('frontend/js/swiper-bundle.js') }}"></script>
    <script src="{{ asset('frontend/js/aos.js') }}"></script>

    {{-- Main Script --}}
    <script src="{{ asset('frontend/js/script.js?ver=' . date('YmdHi')) }}"></script>

    {{-- AOS Init --}}
    <script>
        jQuery(document).ready(function($){
            $(function() { AOS.init(); });
        });
    </script>

    {{-- Google Analytics --}}
    @if(!empty($siteInfo['ga_code']))
        {!! $siteInfo['ga_code'] !!}
    @endif

    @stack('scripts')
</body>

</html>
