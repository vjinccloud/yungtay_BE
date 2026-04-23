<!doctype html>
<html lang="{{ config('app.locale') }}">

<head>
    <meta charset="utf-8">
    <!--
        Available classes for <html> element:

        'dark'                  Enable dark mode - Default dark mode preference can be set in app.js file (always saved and retrieved in localStorage afterwards):
                                window.Codebase = new App({ darkMode: "system" }); // "on" or "off" or "system"
        'dark-custom-defined'   Dark mode is always set based on the preference in app.js file (no localStorage is used)
    -->
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title inertia>{{ $adminSiteTitle ?? config('app.name', '後台管理系統') }} - 後台管理系統</title>

    <meta name="description" content="{{ $adminSiteTitle ?? config('app.name', '後台管理系統') }}">
    <meta name="author" content="{{ $adminSiteTitle ?? config('app.name') }}">
    <meta name="robots" content="index, follow">
    <!-- Open Graph Meta -->
    <meta property="og:title" content="{{ $adminSiteTitle ?? config('app.name', '後台管理系統') }} - 後台管理系統">
    <meta property="og:site_name" content="{{ $adminSiteTitle ?? config('app.name') }}">
    <meta property="og:description" content="{{ $adminSiteTitle ?? config('app.name', '後台管理系統') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $adminOgImage ?? '' }}">

    @if(!empty($adminFavicon))
    <link rel="shortcut icon" href="{{ $adminFavicon }}">
    <link rel="icon" type="image/x-icon" href="{{ $adminFavicon }}">
    @endif

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('admins/css/parsley.css') }}">

    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-buttons-bs5/css/buttons.bootstrap5.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables-responsive-bs5/css/responsive.bootstrap5.min.css') }}">

    <!-- jQuery (required for DataTables plugin) -->
    <script src="{{ asset('js/lib/jquery.min.js') }}"></script>

    <!-- Alternatively, you can also include a specific color theme after the main stylesheet to alter the default color theme of the template -->
    @vite(['resources/js/app.js','resources/sass/main.scss', 'resources/js/codebase/app.js'])

    <!-- Load and set dark mode preference (blocking script to prevent flashing) -->
    <script src="{{ asset('js/setTheme.js') }}"></script>
    @yield('js')
    @routes
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
