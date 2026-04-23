<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1,maximum-scale=1,user-scalable=yes,viewport-fit=cover">

    {{-- 三層 SEO Meta (內容層 > 模組層 > 基礎層) --}}
    <title>@yield('title', $metaOverride['title'] ?? $siteInfo['title'] ?? __('frontend.site.name'))</title>
    <meta name="description" content="@yield('description', $metaOverride['description'] ?? $siteInfo['description'] ?? '')">
    <meta name="keywords" content="@yield('keywords', $metaOverride['keywords'] ?? $siteInfo['keywords'] ?? '')">

    {{-- Open Graph (三層優先順序) --}}
    <meta property="og:title" content="@yield('og_title', $metaOverride['title'] ?? $siteInfo['title'] ?? __('frontend.site.name'))">
    <meta property="og:description" content="@yield('og_description', $metaOverride['description'] ?? $siteInfo['description'] ?? '')">
    <meta property="og:image" content="@yield('og_image', isset($metaOverride['og_image']) ? asset($metaOverride['og_image']) : ($siteInfo['website_icon'] ? asset($siteInfo['website_icon']) : asset('frontend/images/favicon.png')))">
    <meta property="og:type" content="website">
    {{-- Favicon (從後台設定) --}}
    @if(!empty($siteInfo['favicon']))
        <link rel="shortcut icon" href="{{ asset($siteInfo['favicon']) }}">
    @else
        <link rel="shortcut icon" href="{{ asset('frontend/images/favicon.png') }}">
    @endif

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Authentication Status --}}
    @auth
        <meta name="auth-status" content="authenticated">
    @endauth

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/swiper-bundle.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/aos.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/jquery.fancybox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/vendor/jquery-ui.css') }}">

    {{-- Main CSS --}}
    <link rel="stylesheet" href="{{ asset('frontend/css/main.css?ver=' . date('YmdHi')) }}">
    <link rel="stylesheet" href="{{ asset('frontend/css/loading.css') }}">

    {{-- Vite 編譯的前台樣式 --}}
    @vite([
            'resources/sass/frontend.scss',
            'resources/js/frontend.js',
        ])
    @stack('styles')

    {{-- JSON-LD 結構化資料 --}}
    @stack('head')
</head>
<body>
    <div id="frontend-app">
        {{-- Header --}}
        @include('frontend.layouts.partials.header')

        {{-- Main Content --}}
        <main>
            @yield('content')
        </main>

        {{-- Footer --}}
        @include('frontend.layouts.partials.footer')

        {{-- Loading Overlay (Vue Component) --}}
        <loading-overlay v-model="isLoading"></loading-overlay>
    </div>

    {{-- 手機版漢堡選單按鈕 --}}
    <button class="toggle-btn">
        <span></span>
        <span></span>
        <span></span>
    </button>

    {{-- 遮罩層 --}}
    <div class="overlap"></div>

    {{-- Mobile Navigation --}}
    @include('frontend.layouts.partials.mobile-nav')

    {{-- Popups Section --}}
    @yield('popups')

    {{-- Vendor Scripts --}}
    <script src="{{ asset('frontend/js/jquery.min.js') }}"></script>
    <script src="{{ asset('frontend/js/swiper-bundle.js') }}"></script>
    <script src="{{ asset('frontend/js/aos.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.fancybox.min.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"></script>
    @if(app()->getLocale() === 'zh_TW')
    <script src="https://cdn.jsdelivr.net/npm/jquery-ui-i18n@1.0.0/jquery.ui.datepicker-zh-TW.min.js"></script>
    @endif

    {{-- Main Script --}}
    <script src="{{ asset('frontend/js/script.js?ver=' . date('YmdHi')) }}"></script>

    {{-- Google Analytics (放在 </body> 前) --}}
    @if(!empty($siteInfo['ga_code']))
        {!! $siteInfo['ga_code'] !!}
    @endif

    @stack('scripts')
</body>
</html>
