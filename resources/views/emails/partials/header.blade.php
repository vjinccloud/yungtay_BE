{{-- 郵件模板共用 Header --}}
<div class="header">
    <h1>{{ $siteName ?? config('app.name', '信吉衛視') }}</h1>
    <p>{{ $headerSubtitle ?? '' }}</p>
</div>