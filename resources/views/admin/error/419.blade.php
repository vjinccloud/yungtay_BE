<!doctype html>
<html lang="zh-TW" class="remember-theme">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>419 - 頁面已過期</title>

    <meta name="description" content="419 - 頁面已過期">
    <meta name="robots" content="noindex, nofollow">

    <!-- Icons -->
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="icon" sizes="192x192" type="image/png" href="{{ asset('media/favicons/favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">
    <!-- END Icons -->

    <!-- Stylesheets -->
    @vite(['resources/sass/main.scss', 'resources/js/codebase/app.js'])
    <!-- END Stylesheets -->

    <!-- Load and set color theme + dark mode preference (blocking script to prevent flashing) -->
    <script src="{{ asset('js/setTheme.js') }}"></script>
  </head>

  <body>
    <div id="page-container" class="main-content-boxed">
      <!-- Main Container -->
      <main id="main-container">
        <!-- Page Content -->
        <div class="hero bg-body-extra-light">
          <div class="hero-inner">
            <div class="content content-full">
              <div class="py-4 text-center">
                <div class="display-1 fw-bold text-info">
                  <i class="fa fa-clock opacity-50 me-1"></i> 419
                </div>
                <h1 class="fw-bold mt-5 mb-2">頁面已過期，請重新整理後再試</h1>
                <a class="btn btn-lg btn-alt-secondary" href="{{ route('admin.dashboard') }}">
                  <i class="fa fa-arrow-left opacity-50 me-1"></i> 返回控制台
                </a>
              </div>
            </div>
          </div>
        </div>
        <!-- END Page Content -->
      </main>
      <!-- END Main Container -->
    </div>
  </body>
</html>