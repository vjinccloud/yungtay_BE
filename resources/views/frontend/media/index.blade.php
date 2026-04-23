{{-- resources/views/frontend/media/index.blade.php --}}
@extends('frontend.layouts.app')

@section('title', $typeName . ' - '. ($siteInfo['title'] ?? '信吉衛視'))

{{-- JSON-LD 結構化資料 --}}
@push('head')
    @if($type === 'drama')
        {!! \App\Facades\JsonLd::toJsonLd(
            \App\Facades\JsonLd::generateDramaCollectionPage(
                $themes,
                ['name' => $typeName, 'url' => url()->current()],
                $metaOverride ?? []
            )
        ) !!}
    @elseif($type === 'program')
        {!! \App\Facades\JsonLd::toJsonLd(
            \App\Facades\JsonLd::generateProgramCollectionPage(
                $themes,
                ['name' => $typeName, 'url' => url()->current()],
                $metaOverride ?? []
            )
        ) !!}
    @elseif($type === 'radio')
        {!! \App\Facades\JsonLd::toJsonLd(
            \App\Facades\JsonLd::generateRadioCollectionPage(
                $themes,
                ['name' => $typeName, 'url' => url()->current()],
                $metaOverride ?? []
            )
        ) !!}
    @endif
@endpush

@section('content')
    {{-- 節目和影音共用相同的 CSS class，廣播需要額外的 section-radio-list --}}
    <section class="section-drama-list{{ $type === 'radio' ? ' section-radio-list' : '' }}">
        <div class="breadcrumb-div">
            <i></i>
            <span></span>
            {{ $typeName }}
        </div>
        <div class="block-div block-01">
            <div class="block-outer">
                <div class="block-title">
                    <div class="inline-flex">
                        <div class="big-title">
                            <h1>{{ $typeName }}</h1>
                        </div>
                        <div class="toggle-drama-filter" data-open-id="filterTypeYear">
                            <button type="button"><span><b>@if($type === 'drama'){{ __('frontend.filter.all_drama') }}@elseif($type === 'program'){{ __('frontend.filter.all_program') }}@else{{ __('frontend.filter.all_radio') }}@endif</b></span><i></i></button>
                        </div>
                    </div>
                </div>
                <div class="filter-type-year" id="filterTypeYear">
                    <div class="boxer">
                        <div class="tab-links-outer">
                            <div class="links">
                                @foreach($categories['main'] as $index => $mainCategory)
                                    <a href="#filter{{ $mainCategory['id'] }}" class="{{ $index === 0 ? 'active' : '' }}">
                                        {{ $mainCategory['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="tab-content">
                            @foreach($categories['main'] as $index => $mainCategory)
                            {{-- 每個主分類的篩選表單 --}}
                            @php
                                // 取得這個主分類下的子分類
                                $subcategories = collect($categories['sub'])->where('parent_id', $mainCategory['id']);
                            @endphp
                            <form class="content {{ $index === 0 ? 'active' : '' }}" id="filter{{ $mainCategory['id'] }}">
                                <input type="hidden" name="category_id" value="{{ $mainCategory['id'] }}">
                                @if($subcategories->count() > 0)
                                <div class="item">
                                    <div class="label">
                                        {{ __('frontend.filter.type') }}
                                    </div>
                                    <div class="filter-list">
                                        @foreach($subcategories as $subcategory)
                                        <div class="checkbox-box">
                                            <label class="checkbox-container">{{ $subcategory['name'] }}
                                                <input type="checkbox" name="subcategories[]" value="{{ $subcategory['id'] }}"
                                                    {{ in_array($subcategory['id'], request()->get('subcategories', [])) ? 'checked' : '' }}>
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                <div class="item">
                                    <div class="label">
                                        {{ __('frontend.filter.year') }}
                                    </div>
                                    <div class="filter-list">
                                        @php
                                            $currentYear = date('Y');
                                            $years = range($currentYear, 2015);
                                        @endphp
                                        @foreach($years as $year)
                                        <div class="checkbox-box">
                                            <label class="checkbox-container">{{ $year }}
                                                <input type="checkbox" name="years[]" value="{{ $year }}"
                                                    {{ in_array($year, request()->get('years', [])) ? 'checked' : '' }}>
                                            </label>
                                        </div>
                                        @endforeach
                                        <div class="checkbox-box">
                                            <label class="checkbox-container">{{ __('frontend.filter.year_before_2015') }}
                                                <input type="checkbox" name="years[]" value="before_2015"
                                                    {{ in_array('before_2015', request()->get('years', [])) ? 'checked' : '' }}>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="item action">
                                    <button class="btn-reset" type="button" onclick="clearFilters('filter{{ $mainCategory['id'] }}')">{{ __('frontend.btn.clear_filter') }}</button>
                                    <button class="btn-search" type="submit">{{ __('frontend.btn.search') }}</button>
                                </div>
                            </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 主題輪播區塊 -->
        <div class="block-div block-02" id="themesSection" style="{{ request()->has('category_id') || request()->has('subcategories') || request()->has('years') ? 'display: none;' : '' }}">
            <div class="block-outer">
                <div class="swiper-list">
                    @forelse($themes as $index => $theme)
                    <div class="item">
                        <div class="block-title">
                            <div class="sub-title">
                                <h2>{{ $theme['name'] }}</h2>
                            </div>
                            <div class="more">

                            </div>
                        </div>
                        <div class="swiper-list-div">
                            <div class="swiper swiperList" id="swiperHot{{ ucfirst($type) }}{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}">
                                <div class="swiper-wrapper">
                                    @foreach($theme['items'] as $item)
                                    <div class="swiper-slide">
                                        @if($type === 'radio')
                                        <a href="{{ route('radio.show', $item['id']) }}">
                                            <div class="img">
                                                <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy">
                                            </div>
                                            <div class="info">
                                                <div class="program">
                                                    <h3>{{ $item['title'] }}</h3>
                                                    @if(!empty($item['media_name']))
                                                    <p>{{ $item['media_name'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                        @else
                                        <a href="{{ route($type . '.videos.index', [$type . 'Id' => $item['id']]) }}">
                                            <div class="img">
                                                <picture>
                                                    <source media="(max-width: 768px)" srcset="{{ $item['poster_mobile'] }}">
                                                    <img src="{{ $item['poster_desktop'] }}" alt="{{ $item['title'] }}" loading="lazy">
                                                </picture>
                                            </div>
                                            <div class="info">
                                                <div class="program"><h3>{{ $item['title'] }}</h3></div>
                                            </div>
                                        </a>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <div class="swiper-button-next"></div>
                                <div class="swiper-button-prev"></div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="item">
                        <div class="no-data">
                            <p>@if($type === 'drama'){{ __('frontend.status.no_drama') }}@elseif($type === 'program'){{ __('frontend.status.no_program') }}@else{{ __('frontend.status.no_radio') }}@endif</p>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- 篩選結果區塊（預設隱藏）-->
        <div class="block-div block-02" id="resultsSection" style="{{ request()->has('category_id') || request()->has('subcategories') || request()->has('years') ? 'display: block;' : 'display: none;' }}">
            <div class="block-outer">
                <media-filter-results
                    content-type="{{ $type }}"
                    :texts="{
                        loading: '{{ __('frontend.status.loading') }}',
                        no_data: '{{ __('frontend.status.no_' . $type) }}',
                        error: '{{ __('frontend.status.error') }}'
                    }">
                </media-filter-results>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    /* 在 Swiper 初始化前隱藏內容，避免圖片跳動 */
    .swiperList {
        visibility: hidden;
    }

    .swiperList.swiper-initialized {
        visibility: visible;
    }

    /* 為圖片設定載入時的背景色 */
    .swiperList .swiper-slide .img {
        background-color: #f5f5f5;
    }

    /* 響應式圖片優化 - 確保 picture 元素和 img 完全填充容器 */
    .swiperList .swiper-slide .img picture {
        width: 100%;
        height: 100%;
        display: block;
    }

    .swiperList .swiper-slide .img picture img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* 篩選結果列表 - 圖片容器預載入高度，避免載入時版面跳動 */
    .section-drama-list .filter-list-result .item .img {
        aspect-ratio: 860 / 485;  /* 桌機版海報比例 (860px × 485px) */
    }

    /* 手機版使用不同的寬高比 */
    @media (max-width: 768px) {
        .section-drama-list .filter-list-result .item .img {
            aspect-ratio: 200 / 240;  /* 手機版海報比例 (200px × 240px) */
        }
    }

    /* 廣播篩選結果使用正方形圖片 (350×350) */
    .section-radio-list .filter-list-result .item .img {
        aspect-ratio: 1 / 1;  /* 正方形比例 */
    }

    /* 廣播手機版也保持正方形 */
    @media (max-width: 768px) {
        .section-radio-list .filter-list-result .item .img {
            aspect-ratio: 1 / 1;  /* 正方形比例 */
        }
    }

    /* 列表容器預設最小高度，避免載入時跳動 */
    #resultsSection .block-outer {
        min-height: 600px;
    }

    /* 空狀態樣式 - 與 RadioList.vue 統一 */
    .no-data {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 400px;
        text-align: center;
        color: #fff;
    }

    .no-data p {
        font-size: 18px;
        margin: 0;
    }
</style>
@endpush

@push('scripts')
<script>
    jQuery(document).ready(function($){
        $(function() {
            AOS.init();
        });
    });

    $(function(){
        // 初始化 checkbox 的 active 樣式
        $('.filter-type-year input[type="checkbox"]').each(function() {
            if ($(this).is(':checked')) {
                $(this).closest('.checkbox-box').addClass('active');
            }
        });

        // 監聽 checkbox 改變事件，更新 active 樣式
        $('.filter-type-year').on('change', 'input[type="checkbox"]', function() {
            $(this).closest('.checkbox-box').toggleClass('active', $(this).is(':checked'));
        });

        // 處理圖片載入完成事件
        $('.swiperList .img picture img').on('load', function() {
            $(this).closest('.img').addClass('loaded');
        });

        // 動態初始化所有 Swiper
        // 根據頁面類型選擇不同的 breakpoints 設定
        var pageType = '{{ $type }}';

        // 廣播頁面使用設計稿的 breakpoints（較多 slidesPerView，圖片較小 350px）
        var radioBreakpoints = {
            350: {
                slidesPerView: 2,
                spaceBetween: 5,
            },
            640: {
                slidesPerView: 3,
                spaceBetween: 10,
            },
            768: {
                slidesPerView: 3,
            },
            1024: {
                slidesPerView: 4,
            },
            1480: {
                slidesPerView: 5,
            },
            1680: {
                slidesPerView: 5,
            },
        };

        // 影音/節目頁面使用原有的 breakpoints（較少 slidesPerView，圖片較大）
        var defaultBreakpoints = {
            350: {
                slidesPerView: 3.5,
                spaceBetween: 5,
            },
            640: {
                slidesPerView: 2,
                spaceBetween: 10,
            },
            768: {
                slidesPerView: 2,
            },
            1024: {
                slidesPerView: 3,
            },
            1480: {
                slidesPerView: 3,
            },
            1680: {
                slidesPerView: 4,
            },
        };

        // 根據頁面類型選擇 breakpoints
        var swiperBreakpoints = (pageType === 'radio') ? radioBreakpoints : defaultBreakpoints;

        $('.swiperList').each(function(index) {
            var $swiperElement = $(this);
            var swiperId = $swiperElement.attr('id');
            if(swiperId) {
                var swiper = new Swiper("#" + swiperId, {
                    slidesPerView: 4,
                    spaceBetween: 20,
                    navigation: {
                        nextEl: "#" + swiperId + " .swiper-button-next",
                        prevEl: "#" + swiperId + " .swiper-button-prev",
                    },
                    autoplayDisableOnInteraction: true,
                    // 初始化完成後顯示內容
                    on: {
                        init: function() {
                            setTimeout(function() {
                                $swiperElement.addClass('swiper-initialized');
                            }, 100);
                        }
                    },
                    breakpoints: swiperBreakpoints,
                });
            }
        });

        // Tab 切換功能
        $('.tab-links-outer .links').on('click', 'a', function(e) {
            e.preventDefault();

            const targetSelector = $(this).attr('href');       // "#filter3"
            const formId         = targetSelector.slice(1);    //  "filter3"
            const $boxer         = $(this).closest('.boxer');
            const $links         = $(this).closest('.links');
            const $contents      = $boxer.find('.tab-content .content');

            // 先收集當前選中的年份（在清除之前）
            const selectedYears = [];
            $('.filter-type-year input[name="years[]"]:checked').each(function() {
                const yearValue = $(this).val();
                if (!selectedYears.includes(yearValue)) {
                    selectedYears.push(yearValue);
                }
            });


            // 切換 tab 樣式
            $links.find('a').removeClass('active');
            $(this).addClass('active');

            // 顯示對應的 form，隱藏其他
            $contents.removeClass('active');
            $contents.filter(targetSelector).addClass('active');

            // 清除其他 tab 的子分類（不清除年份）
            $contents
                .not(targetSelector)
                .find('.item:first .filter-list input[type="checkbox"]')
                .prop('checked', false);

            // 清除當前 tab 的子分類
            const $currentForm = $(targetSelector);
            $currentForm.find('.item:first .filter-list input[type="checkbox"]').prop('checked', false);

            // 將選中的年份應用到所有表單
            $('.filter-type-year .content').each(function() {
                $(this).find('input[name="years[]"]').each(function() {
                    const shouldCheck = selectedYears.includes($(this).val());
                    $(this).prop('checked', shouldCheck);
                    // 同步更新 checkbox-box 的 active 樣式
                    $(this).closest('.checkbox-box').toggleClass('active', shouldCheck);
                });
            });
        });
    });


    // 清除篩選功能
    function clearFilters(formId) {
        const form = document.getElementById(formId);
        if (!form) return;

        // 清除所有 checkbox 的勾選狀態
        form.querySelectorAll('input[type="checkbox"]').forEach(chk => {
            chk.checked = false;

            // 如果使用了 jQuery 或其他 UI 框架，可能需要觸發 change 事件
            $(chk).prop('checked', false).trigger('change');

            // 確保父元素的樣式也被更新（如果有自定義樣式）
            const parent = $(chk).closest('.checkbox-box, .checkbox-container');
            parent.removeClass('checked active selected');
        });
    }

    // ========================================
    // 影音/節目頁面整合：攔截表單送出邏輯
    // ========================================
    $(function() {
        let isFiltered = false;  // 追蹤當前狀態
        const contentType = '{{ $type }}';  // drama 或 program

        // 1. 攔截所有篩選表單的送出
        $('.filter-type-year form').on('submit', function(e) {
            e.preventDefault();  // 阻止預設表單送出

            const $form = $(this);
            const formData = $form.serialize();

            console.log('[Filter] 表單送出攔截成功', formData);

            // 先切換到結果顯示（帶 loading 狀態）
            showFilterResults(function() {
                // 顯示完成後，發送 AJAX
                fetchFilterResults(formData);
            });

            // 更新 URL
            updateURL(formData);
        });

        // 2. 監聽「清除篩選」按鈕（修改現有的 clearFilters 函數）
        window.originalClearFilters = window.clearFilters || function() {};
        window.clearFilters = function(formId) {
            // 執行原有的清除邏輯
            const form = document.getElementById(formId);
            if (!form) return;

            form.querySelectorAll('input[type="checkbox"]').forEach(chk => {
                chk.checked = false;
                $(chk).prop('checked', false).trigger('change');
                const parent = $(chk).closest('.checkbox-box, .checkbox-container');
                parent.removeClass('checked active selected');
            });

            // 切換回主題顯示
            showThemes();

            // 清除 URL 參數
            updateURL('');
        };

        // 3. 切換顯示：顯示篩選結果
        function showFilterResults(callback) {
            console.log('[Filter] 切換到篩選結果顯示');

            // 先通知 Vue 進入 loading 狀態
            window.EventBus.emit('filter-show-loading');

            $('#themesSection').fadeOut(300, function() {
                $('#resultsSection').fadeIn(300, function() {
                    // 切換完成後執行 callback
                    if (typeof callback === 'function') {
                        callback();
                    }
                });
            });
            isFiltered = true;
        }

        // 4. 切換顯示：顯示主題
        function showThemes() {
            console.log('[Filter] 切換到主題顯示');

            // 通知 Vue 重置狀態
            window.EventBus.emit('filter-reset');

            $('#resultsSection').fadeOut(300, function() {
                $('#themesSection').fadeIn(300);
            });
            isFiltered = false;
        }

        // 5. AJAX 獲取篩選結果
        function fetchFilterResults(formData, page = 1) {
            console.log('[Filter] 發送 AJAX 請求', { formData, page });

            // 組合完整的 URL（加上分頁參數）
            const params = formData ? formData + '&page=' + page : 'page=' + page;
            const fullUrl = '/api/v1/' + contentType + '/filter?' + params;

            $.ajax({
                url: fullUrl,
                method: 'GET',
                success: function(response) {
                    console.log('[Filter] AJAX 成功', response);

                    // 通知 Vue 更新結果
                    window.EventBus.emit('filter-results-updated', {
                        results: response[contentType + 's'] || response.dramas || response.programs || response.radios || [],
                        total: response.total || 0,
                        current_page: response.current_page || 1,
                        per_page: response.per_page || 20
                    });
                },
                error: function(xhr, status, error) {
                    console.error('[Filter] AJAX 失敗', { xhr, status, error });

                    // 通知 Vue 顯示錯誤
                    window.EventBus.emit('filter-error', {
                        message: '載入失敗，請稍後再試'
                    });
                }
            });
        }

        // 6. 更新 URL（History API）
        function updateURL(formData) {
            if (!formData) {
                // 清除參數，回到乾淨的 URL
                const cleanURL = window.location.pathname;
                window.history.pushState({ filtered: false }, '', cleanURL);
                console.log('[Filter] URL 已清除');
            } else {
                // 加上篩選參數
                const newURL = window.location.pathname + '?' + formData;
                window.history.pushState({ filtered: true, formData: formData }, '', newURL);
                console.log('[Filter] URL 已更新', newURL);
            }
        }

        // 7. 監聽瀏覽器前進/後退按鈕
        window.addEventListener('popstate', function(e) {
            console.log('[Filter] 瀏覽器導航', e.state);

            const urlParams = new URLSearchParams(window.location.search);
            const hasParams = urlParams.toString().length > 0;

            if (hasParams) {
                // URL 有參數 → 顯示篩選結果
                showFilterResults(function() {
                    fetchFilterResults(urlParams.toString());
                });
            } else {
                // URL 無參數 → 顯示主題
                showThemes();
            }
        });

        // 8. 監聽 Vue 的分頁切換事件
        window.EventBus.on('filter-page-changed', function(data) {
            console.log('[Filter] Vue 通知分頁切換', data);

            // 取得當前的表單參數
            const urlParams = new URLSearchParams(window.location.search);
            let formData = urlParams.toString();

            // 移除舊的 page 參數
            urlParams.delete('page');
            formData = urlParams.toString();

            // 重新獲取結果（帶新的 page）
            fetchFilterResults(formData, data.page);

            // 更新 URL
            const newFormData = formData ? formData + '&page=' + data.page : 'page=' + data.page;
            updateURL(newFormData);
        });

        // 9. 頁面載入時檢查 URL 參數
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasParams = urlParams.toString().length > 0;

            if (hasParams) {
                console.log('[Filter] 頁面載入時偵測到 URL 參數，載入篩選結果');

                // 有搜尋參數時，展開篩選器
                const $filterContainer = $('#filterTypeYear');
                const $toggleBtn = $('.toggle-drama-filter');

                $filterContainer.addClass('show'); // 展開篩選表單
                $toggleBtn.addClass('active'); // 更新按鈕狀態

                // 顯示篩選結果
                showFilterResults(function() {
                    fetchFilterResults(urlParams.toString());
                });
            }
        });
    });
</script>
@endpush
