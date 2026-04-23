<!DOCTYPE html>
<html lang="{{ $lang ?? 'zh-TW' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', '信吉衛視'))</title>
    @include('emails.partials.styles')
    @yield('additional_styles')
</head>
<body>
    <div class="container">
        {{-- Header --}}
        @include('emails.partials.header', [
            'siteName' => $siteName ?? config('app.name', '信吉衛視'),
            'headerSubtitle' => $headerSubtitle ?? ''
        ])

        {{-- Content --}}
        <div class="content">
            @yield('content')
        </div>

        {{-- Footer --}}
        @include('emails.partials.footer', [
            'siteName' => $siteName ?? config('app.name', '信吉衛視'),
            'footerContact' => $footerContact ?? null,
            'footerLinks' => $footerLinks ?? null,
            'footerNote' => $footerNote ?? null
        ])
    </div>
</body>
</html>