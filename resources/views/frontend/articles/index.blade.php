@extends('frontend.layouts.app')

@section('title', __('frontend.nav.news') . ' - ' . $siteInfo['title'])

{{-- JSON-LD 結構化資料 --}}
@push('head')
    @if(isset($firstPageArticles) && $firstPageArticles->count() > 0)
        {!! \App\Facades\JsonLd::toJsonLd(
            \App\Facades\JsonLd::generateNewsCollectionPage(
                $firstPageArticles->items(),
                ['name' => __('frontend.nav.news'), 'url' => url()->current()],
                $metaOverride ?? []
            )
        ) !!}
    @endif
@endpush

@section('content')
<section class="section-hot-news-list">
    <div class="breadcrumb-div">
        <i></i>
        <span></span>
        {{ __('frontend.nav.news') }}
    </div>
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.hot_news') }}</h2>
                </div>
                <div class="more">

                </div>
            </div>
            <div class="hot-news-div">
                <div class="two-cols">
                    @if(count($latestArticles) > 0)
                    <div class="col01">
                        <div class="news">
                            <a href="{{ route('articles.show', $latestArticles[0]['id']) }}">
                                <div class="img" style="background-image: url({{ $latestArticles[0]['image_original'] ?: asset('frontend/images/hot_news_big_01.png') }});">
                                    <img
                                        src="{{ $latestArticles[0]['image_original'] ?: asset('frontend/images/default.webp') }}"
                                        alt="{{ $latestArticles[0]['title'] }}"
                                        loading="eager"
                                        fetchpriority="high"
                                        decoding="async"
                                        onload="this.parentElement.classList.add('images-loaded')">
                                </div>
                                <div class="info">
                                    <div class="datetime">{{ $latestArticles[0]['category_name'] ?? '' }}・{{ $latestArticles[0]['publish_date'] }}</div>
                                    <div class="desc">
                                        <h3>{{ $latestArticles[0]['title'] }}</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    @endif

                    @if(count($latestArticles) > 1)
                    <div class="col02">
                        @foreach($latestArticles->slice(1, 3) as $article)
                        <div class="news">
                            <a href="{{ route('articles.show', $article['id']) }}">
                                <div class="img">
                                    <img
                                        src="{{ $article['image'] ?: asset('frontend/images/default.webp') }}"
                                        alt="{{ $article['title'] }}"
                                        loading="lazy"
                                        decoding="async"
                                        onload="this.parentElement.classList.add('images-loaded')">
                                </div>
                                <div class="info">
                                    <div class="datetime">{{ $article['category_name'] ?? '' }}・{{ $article['publish_date'] }}</div>
                                    <div class="desc">
                                        <h3>{{ $article['title'] }}</h3>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            <!-- 新聞列表篩選器 + 列表 -->
            <div class="block-title">
                <div class="inline-flex">
                    <div class="big-title">
                        <h1>{{ __('frontend.nav.news') }}</h1>
                    </div>
                    <div class="toggle-drama-filter" data-open-id="filterNews">
                        <button type="button">
                            <span><b>
                                @if($selectedCategoryId == 0)
                                    {{ __('frontend.filter.all_news') }}
                                @else
                                    {{ $categories->firstWhere('id', $selectedCategoryId)['name'] ?? __('frontend.filter.all_news') }}
                                @endif
                            </b></span>
                            <i></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 下拉篩選器 -->
            <div class="filter-type-year" id="filterNews">
                <div class="boxer">
                    <div class="tab-content">
                        <form class="content active" id="filterForm" action="{{ route('articles.index') }}" method="GET">
                            <div class="item">
                                <div class="label">{{ __('frontend.filter.category') }}</div>
                                <div class="filter-list">
                                    <!-- 全部分類 -->
                                    <div class="checkbox-box {{ $selectedCategoryId == 0 ? 'active' : '' }}">
                                        <label class="checkbox-container">{{ __('frontend.filter.all_news') }}
                                            <input type="radio" name="category_id" value="0"
                                                {{ $selectedCategoryId == 0 ? 'checked' : '' }}>

                                        </label>
                                    </div>

                                    <!-- 所有分類 -->
                                    @foreach($categories as $category)
                                    <div class="checkbox-box {{ $selectedCategoryId == $category['id'] ? 'active' : '' }}">
                                        <label class="checkbox-container">{{ $category['name'] }}
                                            <input type="radio" name="category_id" value="{{ $category['id'] }}"
                                                {{ $selectedCategoryId == $category['id'] ? 'checked' : '' }}>

                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="item action">
                                <button class="btn-reset" type="button">{{ __('frontend.btn.clear_filter') }}</button>
                                <button class="btn-search" type="submit">{{ __('frontend.btn.search') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 新聞列表（使用 Vue 組件） -->
            <div class="hot-news-filter-list">
                <div class="tab-content">
                    <article-list-page
                        :initial-category="'{{ $selectedCategoryId }}'"
                        :categories='@json($categories)'
                        :initial-data="{{ isset($articles) && $articles ? json_encode($articles) : 'null' }}"
                        :texts="{
                            noData: '{{ __('frontend.status.no_articles') }}',
                            loading: '{{ __('frontend.status.loading') }}'
                        }">
                    </article-list-page>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* 只處理左邊大圖避免載入跳動 */

    /* 左邊大圖 - 強制優先級設定 */
    .section-hot-news-list .hot-news-div .two-cols .col01 .news  {
        position: relative !important;
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 1068 / 554 !important; /* 設計稿正確比例 ≈ 1.93:1 */
        overflow: hidden !important;
    }

    .section-hot-news-list .hot-news-div .two-cols .col01 .news .img img {
        position: static !important;
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .hot-news-div .two-cols .col01 .news .img.images-loaded img {
        opacity: 1;
    }

    /* 移除造成陰影的偽元素 - 保留此設定因為是全域影響 */
    .news-list .item:before {
        display: none !important;
    }

    /* RWD 響應式調整 - 只針對大圖 */
    @media (max-width: 768px) {
        .section-hot-news-list .hot-news-div .two-cols .col01 .news {
            position: relative !important;
            aspect-ratio: 1068 / 554 !important; /* 設計稿正確比例 ≈ 1.93:1 */
        }
    }

    /* 新聞列表圖片淡入淡出效果 */
    .hot-news-all-list .news-list .item .img {
        aspect-ratio: 442 / 250; /* 設定圖片容器比例 */
        overflow: hidden;
        position: relative;
    }

    .hot-news-all-list .news-list .item .img img {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .hot-news-all-list .news-list .item .img.images-loaded img {
        opacity: 1;
    }

    /* 修正篩選器載入時的跳動問題 */
    .section-hot-news-list .filter-type-year .boxer .tab-content .content .item .filter-list .checkbox-box {
        min-width: 48px;
        min-height: 34px;
        transition: none !important;
    }

    .section-hot-news-list .filter-type-year .boxer .tab-content .content .item .filter-list .checkbox-box.active {
        background-color: #2CC0E2 !important;
        border: 1px solid #2CC0E2 !important;
    }

    /* 修正按鈕文字載入時的跳動 */
    .section-hot-news-list .block-title .toggle-drama-filter button {
        min-width: 120px;
        white-space: nowrap;
    }

    .section-hot-news-list .block-title .toggle-drama-filter button span {
        display: inline-block;
        min-width: 100px;
        text-align: left;
    }

</style>
@endpush

@push('scripts')
<script>
    jQuery(document).ready(function($){
        // AOS 初始化
        $(function() { AOS.init(); });

        // Fancybox 初始化
        $('[data-fancybox="gallery01"],[data-fancybox="gallery02"]').fancybox({});

        // 處理分類單選的 active 狀態切換
        $('#filterForm input[type="radio"][name="category_id"]').on('change', function() {
            // 移除所有 checkbox-box 的 active
            $('#filterForm .checkbox-box').removeClass('active');

            // 為選中的項目加上 active
            $(this).closest('.checkbox-box').addClass('active');

            // 動態更新外層按鈕的文字
            var selectedText = $(this).closest('.checkbox-container').text().trim();
            $('.toggle-drama-filter[data-open-id="filterNews"] button span b').text(selectedText);
        });

        // ========================================
        // 新聞頁面 SPA 動線：攔截表單送出邏輯
        // ========================================
        // 攔截表單送出
        $('#filterForm').on('submit', function(e) {
            e.preventDefault(); // 阻止預設表單送出

            var categoryId = $('input[name="category_id"]:checked').val();

            console.log('[Article Filter] 表單送出攔截成功', { categoryId });

            // 發送事件通知 Vue 組件更新
            if (window.EventBus) {
                window.EventBus.emit('article-filter-changed', { categoryId });
            }

            // 更新 URL（不跳頁）
            updateURL(categoryId);
        });

        // 更新 URL（History API）
        function updateURL(categoryId) {
            if (!categoryId || categoryId == '0') {
                // 所有新聞：清除參數
                const cleanURL = window.location.pathname;
                window.history.pushState({ categoryId: '0' }, '', cleanURL);
                console.log('[Article Filter] URL 已清除');
            } else {
                // 特定分類：帶參數
                const newURL = window.location.pathname + '?category_id=' + categoryId;
                window.history.pushState({ categoryId }, '', newURL);
                console.log('[Article Filter] URL 已更新', newURL);
            }
        }

        // 監聽瀏覽器前進/後退按鈕
        window.addEventListener('popstate', function(e) {
            console.log('[Article Filter] 瀏覽器導航', e.state);

            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category_id') || '0';
            const page = parseInt(urlParams.get('page')) || 1;

            // 發送事件通知 Vue 組件更新（包含分頁參數）
            if (window.EventBus) {
                window.EventBus.emit('article-filter-changed', { categoryId, page });
            }
        });

        // 清除篩選按鈕
        $('.btn-reset').on('click', function(e) {
            e.preventDefault();

            console.log('[Article Filter] 清除篩選');

            // 重置 radio 為「所有新聞」
            $('#filterForm input[name="category_id"][value="0"]').prop('checked', true).trigger('change');

            // 發送事件通知 Vue 載入所有新聞
            if (window.EventBus) {
                window.EventBus.emit('article-filter-changed', { categoryId: '0' });
            }

            // 清除 URL 參數
            updateURL('0');
        });

        // 監聽 Vue 的分頁切換事件
        if (window.EventBus) {
            window.EventBus.on('article-page-changed', function(data) {
                console.log('[Article Filter] Vue 通知分頁切換', data);

                // 取得當前的分類參數
                const urlParams = new URLSearchParams(window.location.search);
                const categoryId = urlParams.get('category_id') || '0';

                // 更新 URL（帶分頁參數）
                let newURL = window.location.pathname;
                if (categoryId !== '0') {
                    newURL += '?category_id=' + categoryId + '&page=' + data.page;
                } else {
                    newURL += '?page=' + data.page;
                }
                window.history.pushState({ categoryId, page: data.page }, '', newURL);

                // 通知 Vue 載入新分頁
                window.EventBus.emit('article-filter-changed', { categoryId, page: data.page });
            });
        }
    });
</script>
@endpush
