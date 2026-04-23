<!-- resources/views/frontend/news/index.blade.php -->
@extends('frontend.layouts.app')

@section('title', __('frontend.nav.latest_news') . ' - ' . $siteInfo['title'])

{{-- JSON-LD 結構化資料 --}}
@push('head')
    @if(isset($firstPageNews) && $firstPageNews->count() > 0)
        {!! \App\Facades\JsonLd::toJsonLd(
            \App\Facades\JsonLd::generateLatestNewsCollectionPage(
                $firstPageNews->items(),
                ['name' => __('frontend.nav.latest_news'), 'url' => url()->current()],
                $metaOverride ?? []
            )
        ) !!}
    @endif
@endpush

@section('content')
<section class="section-latest-news-list">
    <div class="breadcrumb-div">
        <i></i>
        <span></span>
        {{ __('frontend.nav.latest_news') }}
    </div>
    <div class="block-div block-01">
        <div class="block-outer">
            {{-- Vue 組件掛載點 --}}
            <div class="news-list-container">
                <news-list
                    api-endpoint="{{ route('api.news.index') }}"
                    :per-page="6"
                    :texts="{
                        title: '{{ __('frontend.nav.latest_news') }}',
                        searchPlaceholder: '{{ __('frontend.search.placeholder') }}',
                        noData: '{{ __('frontend.status.no_news') }}',
                        loading: '{{ __('frontend.status.loading') }}'
                    }">
                </news-list>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* 新聞列表容器固定高度，避免載入時版面跳動 */
    .news-list-container {
        min-height: 400px;
    }
</style>
@endpush

@push('scripts')
<script>
    jQuery(document).ready(function($) {
        // AOS 動畫初始化
       //AOS.init();
    });
</script>
@endpush
