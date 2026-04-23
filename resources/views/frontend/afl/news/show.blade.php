{{-- 最新消息詳細頁 --}}
@extends('frontend.layouts.afl')

@section('title', ($news->title ?? '最新消息') . ' - ' . ($siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會'))
@section('main-class', 'main-news-detail')

@section('content')
<section class="section-news-detail">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a>
                <i>/</i>
                <a href="{{ route('afl.news.index') }}">最新消息</a>
                <i>/</i>
                <span>{{ Str::limit($news->title, 40) }}</span>
            </div>
        </div>
    </div>

    {{-- 文章標題區塊 --}}
    <div class="block-div block-news-title">
        <div class="block-outer" data-aos="fade-up" data-aos-duration="1000">
            @if($news->tags)
            <div class="tag">
                @foreach(explode(',', $news->tags) as $tag)
                <span class="pink01">{{ trim($tag) }}</span>
                @endforeach
            </div>
            @endif
            <div class="headline" data-aos="fade-up" data-aos-duration="1000">
                <h1>{{ $news->title }}</h1>
            </div>
            <div class="datetime-society" data-aos="fade-up" data-aos-duration="1000">
                <div class="datetime">
                    {{ $news->published_date ? $news->published_date->format('Y/m/d (D)') : $news->created_at->format('Y/m/d (D)') }}
                </div>
                <div class="society">
                    {{-- Facebook 分享 --}}
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank">
                        <img src="{{ asset('frontend/images/icon_society_pink_01.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_01_hover.svg') }}">
                    </a>
                    {{-- LINE 分享 --}}
                    <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode(request()->url()) }}" target="_blank">
                        <img src="{{ asset('frontend/images/icon_society_pink_02.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_02_hover.svg') }}">
                    </a>
                    {{-- 複製連結 --}}
                    <a href="javascript:;" class="copy-link" data-url="{{ request()->url() }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_03.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_03_hover.svg') }}">
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 文章內容區塊 --}}
    <div class="block-div block-news-detail">
        <div class="block-outer">
            <div class="details-articles">
                <div class="details">
                    @if($news->image)
                    <div class="detail-top" data-aos="fade-up" data-aos-duration="1000">
                        <div class="img">
                            <img src="{{ asset($news->image->path) }}" alt="{{ $news->title }}">
                        </div>
                        @if($news->description)
                        <div class="remark">
                            <p>{{ $news->description }}</p>
                        </div>
                        @endif
                    </div>
                    @endif
                    <div class="detail-bottom" data-aos="fade-up" data-aos-duration="1000">
                        {!! $news->content !!}
                    </div>
                </div>

                {{-- 側邊欄：最新文章 --}}
                <div class="articles">
                    <div class="sticky">
                        <div class="lastest-articles" data-aos="fade-up" data-aos-duration="1000">
                            <div class="headline">
                                <h3>最新文章</h3>
                            </div>
                            <div class="list">
                                <ul>
                                    @if(isset($latestNews) && count($latestNews) > 0)
                                        @foreach($latestNews as $latest)
                                        <li>
                                            <a href="{{ route('afl.news.show', $latest->id) }}">
                                                {{ $latest->title }}
                                            </a>
                                        </li>
                                        @endforeach
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 上下篇文章 --}}
            <div class="two-articles">
                @if(isset($prevNews) && $prevNews)
                <div class="item">
                    <a href="{{ route('afl.news.show', ['id' => $prevNews->id, 'category' => $categoryId ?? null]) }}">
                        <div class="img">
                            <span>
                                <img src="{{ $prevNews->image ? asset($prevNews->image->path) : asset('frontend/images/article_img_01.jpg') }}">
                            </span>
                        </div>
                        <div class="info">
                            <div class="sub-info-01">上篇文章</div>
                            <div class="sub-info-02">
                                <h4>{{ $prevNews->title }}</h4>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                @if(isset($nextNews) && $nextNews)
                <div class="item">
                    <a href="{{ route('afl.news.show', ['id' => $nextNews->id, 'category' => $categoryId ?? null]) }}">
                        <div class="img">
                            <span>
                                <img src="{{ $nextNews->image ? asset($nextNews->image->path) : asset('frontend/images/article_img_01.jpg') }}">
                            </span>
                        </div>
                        <div class="info">
                            <div class="sub-info-01">下篇文章</div>
                            <div class="sub-info-02">
                                <h4>{{ $nextNews->title }}</h4>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- 返回列表 --}}
        <div class="back-list" data-aos="fade-up" data-aos-duration="1000">
            <a href="{{ route('afl.news.index', ['category' => $categoryId ?? null]) }}">返回列表</a>
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
    </div>
</section>
@endsection

@push('scripts')
<script>
jQuery(document).ready(function($) {
    // 複製連結功能
    $('.copy-link').on('click', function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            alert('連結已複製！');
        });
    });
});
</script>
@endpush
