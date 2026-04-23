<!DOCTYPE html>
<html lang="zh-TW" class="remember-theme">
  <head>
    <meta charset="utf-8">
    <!--
      Available classes for <html> element:

      'dark'                  Enable dark mode - Default dark mode preference can be set in app.js file (always saved and retrieved in localStorage afterwards):
                                window.Codebase = new App({ darkMode: "system" }); // "on" or "off" or "system"
      'dark-custom-defined'   Dark mode is always set based on the preference in app.js file (no localStorage is used)
      'remember-theme'        Remembers active color theme between pages using localStorage when set through
                                - Theme helper buttons [data-toggle="theme"]
    -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>{{ config('app.name', '信吉衛視') }} - 後台管理系統</title>

    <meta name="description" content="{{ config('app.name', '信吉衛視') }} 後台管理系統">
    <meta name="author" content="{{ config('app.name', '信吉衛視') }}">
    <meta name="robots" content="index, follow">

    <!-- Open Graph Meta -->
    <meta property="og:title" content="{{ config('app.name', '信吉衛視') }} - 後台管理系統">
    <meta property="og:site_name" content="{{ config('app.name', '信吉衛視') }}">
    <meta property="og:description" content="{{ config('app.name', '信吉衛視') }} 後台管理系統">
    <meta property="og:type" content="website">
    <meta property="og:url" content="">
    <meta property="og:image" content="">

    <!-- Icons -->
    <!-- The following icons can be replaced with your own, they are used by desktop and mobile browsers -->
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href=" {{ asset('media/favicons/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">
    <!-- END Icons -->

    <!-- Stylesheets -->
    <!-- Codebase framework -->
    <link rel="stylesheet" href="{{ asset('admins/css/parsley.css') }}">
    <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
     @vite(['resources/js/app.js','resources/sass/main.scss', 'resources/js/codebase/app.js'])
  </head>

  <body>
    <style>
      html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
      #page-container.hitachi-login-wrapper {
        display: flex !important;
        flex-direction: row !important;
        height: 100vh !important;
        width: 100vw !important;
        max-width: 100vw !important;
        min-width: 0 !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #2d2d2d !important;
        overflow: hidden !important;
      }
      .hitachi-login-left {
        flex: 1 1 0%;
        background: url('{{ asset('media/photos/login-bg.png') }}') center center / cover no-repeat;
        min-height: 100vh;
      }
      .hitachi-login-right {
        flex: 0 0 420px;
        max-width: 420px;
        width: 420px;
        background: #2d2d2d !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center;
        align-items: center;
        padding: 48px 36px;
        min-height: 100vh;
        box-sizing: border-box;
      }
      .hitachi-login-right .login-title {
        color: #fff;
        font-size: 20px;
        font-weight: 600;
        letter-spacing: 2px;
        margin-bottom: 40px;
        text-align: center;
        width: 100%;
      }
      .hitachi-login-right .login-subtitle {
        color: #ccc;
        font-size: 16px;
        font-weight: 500;
        margin-bottom: 24px;
        text-align: center;
        width: 100%;
      }
      .hitachi-login-right .login-form-area {
        width: 100%;
      }
      /* RWD */
      @media (max-width: 992px) {
        .hitachi-login-right {
          flex: 0 0 360px !important;
          max-width: 360px !important;
          width: 360px !important;
          padding: 40px 28px;
        }
      }
      @media (max-width: 768px) {
        #page-container.hitachi-login-wrapper {
          flex-direction: row !important;
          height: 100vh !important;
          min-height: 100vh !important;
          overflow: hidden !important;
        }
        .hitachi-login-left {
          display: none !important;
        }
        .hitachi-login-right {
          flex: 1 1 100% !important;
          max-width: 100% !important;
          width: 100% !important;
          min-height: 100vh !important;
          padding: 48px 32px !important;
        }
      }
      @media (max-width: 480px) {
        .hitachi-login-right {
          padding: 32px 20px !important;
        }
        .hitachi-login-right .login-title {
          font-size: 17px;
          margin-bottom: 28px;
        }
      }
    </style>

    <div id="page-container" class="hitachi-login-wrapper">
      <!-- 左側背景區 -->
      <div class="hitachi-login-left"></div>
      <!-- 右側登入區 -->
      <div class="hitachi-login-right">
        <div class="login-title">日立永大後台管理系統</div>
        <div class="login-form-area">
          <div class="login-subtitle">登入</div>
          @inertia
        </div>
      </div>
    </div>
  </body>
</html>
