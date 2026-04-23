{{-- 最新消息列表頁 --}}
@extends('frontend.layouts.afl')

@section('title', '最新消息 - ' . ($siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會'))
@section('main-class', 'main-news-list')

@section('content')
<section class="section-news-list">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a>
                <i>/</i>
                最新消息
            </div>
        </div>
    </div>

    {{-- 輪播區塊（至頂文章） --}}
    @if($pinnedNews->count() > 0)
    <div class="block-div block-swiper" data-aos="fade-up" data-aos-duration="1000">
        <div class="block-outer">
            <div class="swiper swiperNewsList">
                <div class="swiper-wrapper">
                    @foreach($pinnedNews as $pinned)
                    <div class="swiper-slide">
                        <a href="{{ route('afl.news.show', $pinned->id) }}">
                            <div class="img">
                                @if($pinned->image)
                                <img src="{{ asset($pinned->image->path) }}" class="web">
                                <img src="{{ asset($pinned->image->path) }}" class="mobile">
                                @else
                                <img src="{{ asset('frontend/images/news_banner_01.jpg') }}" class="web">
                                <img src="{{ asset('frontend/images/news_banner_01_mobile.jpg') }}" class="mobile">
                                @endif
                            </div>
                            <div class="info">
                                <div class="box">
                                    <div class="tag">
                                        @if($pinned->tags)
                                            @foreach(explode(',', $pinned->tags) as $tag)
                                            <span class="pink01">{{ trim($tag) }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="headline">
                                        <h2>{{ $pinned->getTranslation('title', 'zh_TW') }}</h2>
                                    </div>
                                    <div class="describe">
                                        <p>{{ Str::limit($pinned->description, 80) }}</p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- 分類區塊 --}}
    <div class="block-div block-news-category">
        <div class="block-outer">
            @if(isset($categories) && count($categories) > 0)
            <div class="category-scroller">
                <div class="category-links" data-aos="fade-up" data-aos-duration="1000">
                    <div class="link">
                        <a href="{{ route('afl.news.index') }}" class="{{ !request('category') ? 'active' : '' }}">全部</a>
                    </div>
                    @foreach($categories as $category)
                    <div class="link">
                        <a href="{{ route('afl.news.index', ['category' => $category->id]) }}" 
                           class="{{ request('category') == $category->id ? 'active' : '' }}">
                            {{ $category->name }}
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- 新聞列表 --}}
            <div class="news-category-list">
                <div class="list-items" data-aos="fade-up" data-aos-duration="1000">
                    @forelse($newsList as $news)
                    <div class="item">
                        <div class="news">
                            <a href="{{ route('afl.news.show', ['id' => $news->id, 'category' => request('category')]) }}">
                                <div class="img">
                                    @if($news->image && $news->image->path)
                                        <img src="{{ asset($news->image->path) }}">
                                    @else
                                        <img src="{{ asset('frontend/images/news_img_0' . (($loop->index % 4) + 1) . '.jpg') }}">
                                    @endif
                                </div>
                                <div class="info">
                                    <div class="datetime">{{ $news->published_date ? $news->published_date->format('Y-m-d') : $news->created_at->format('Y-m-d') }}</div>
                                    @if($news->tags)
                                    <div class="tag">
                                        @foreach(explode(',', $news->tags) as $tag)
                                        <span class="pink01">{{ trim($tag) }}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div class="headline">
                                        <h3>{{ $news->title }}</h3>
                                    </div>
                                    <div class="describe">
                                        <p>{{ Str::limit(strip_tags($news->content), 60) }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="item">
                        <p>目前沒有最新消息</p>
                    </div>
                    @endforelse
                </div>

                {{-- 分頁 --}}
                @if(isset($newsList) && $newsList->total() > 0)
                <div class="pagination-div" data-aos="fade-up" data-aos-duration="1000">
                    @if($newsList->currentPage() > 1)
                    <a href="{{ $newsList->previousPageUrl() }}" class="prev"></a>
                    @endif

                    @for($i = 1; $i <= max($newsList->lastPage(), 1); $i++)
                        <a href="{{ $newsList->url($i) }}" class="{{ $newsList->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
                    @endfor

                    @if($newsList->hasMorePages())
                    <a href="{{ $newsList->nextPageUrl() }}" class="next"></a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 支持我們區塊 --}}
    <div class="block-div block-love" data-aos="fade-up" data-aos-duration="1000">
        <div class="img" style="background-image: url({{ asset('frontend/images/love001.jpg') }});">
            <img src="{{ asset('frontend/images/love001.jpg') }}">
        </div>
        <div class="info">
            <h2>用愛，預約我們的圓滿人生</h2>
            <a href="#">支持我們<i></i></a>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* 新聞列表容器最小高度 */
    .news-category-list {
        min-height: 400px;
    }
</style>
@endpush

@push('scripts')
<script>
jQuery(document).ready(function($) {
    // 初始化 Swiper
    if ($('.swiperNewsList').length) {
        new Swiper('.swiperNewsList', {
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiperNewsList .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiperNewsList .swiper-button-next',
                prevEl: '.swiperNewsList .swiper-button-prev',
            },
        });
    }
});
</script>
@endpush
