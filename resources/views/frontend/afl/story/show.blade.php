@extends('frontend.layouts.afl')

@section('title', $article->title . ' - ' . $expert->name)

@section('main-class', 'main-story-detail')

@section('content')
<section class="section-news-detail section-story-detail">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a><i>/</i>
                <a href="{{ route('afl.expert.index') }}">生命故事</a><i>/</i>
                <a href="{{ route('afl.story.index', $expert->id) }}">{{ $expert->name }}</a><i>/</i>
                <span>{{ Str::limit($article->title, 30) }}</span>
            </div>
        </div>
    </div>

    {{-- 標題區塊 --}}
    <div class="block-div block-news-title">
        <div class="block-outer" data-aos="fade-up" data-aos-duration="1000">
            @if($article->tags)
            <div class="tag">
                @foreach(explode(',', $article->tags) as $tag)
                    <span class="pink01">{{ trim($tag) }}</span>
                @endforeach
            </div>
            @endif
            <div class="headline" data-aos="fade-up" data-aos-duration="1000">
                <h1>{{ $article->title }}</h1>
            </div>
            <div class="datetime-society" data-aos="fade-up" data-aos-duration="1000">
                <div class="datetime">{{ $article->published_date?->format('Y/m/d') }} ({{ ['日','一','二','三','四','五','六'][$article->published_date?->dayOfWeek ?? 0] }})</div>
                <div class="society">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank">
                        <img src="{{ asset('frontend/images/icon_society_pink_01.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_01_hover.svg') }}">
                    </a>
                    <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode(request()->url()) }}" target="_blank">
                        <img src="{{ asset('frontend/images/icon_society_pink_02.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_02_hover.svg') }}">
                    </a>
                    <a href="mailto:?subject={{ urlencode($article->title) }}&body={{ urlencode(request()->url()) }}" target="_blank">
                        <img src="{{ asset('frontend/images/icon_society_pink_03.svg') }}">
                        <img src="{{ asset('frontend/images/icon_society_pink_03_hover.svg') }}">
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- 內容區塊 --}}
    <div class="block-div block-news-detail block-story-detail">
        <div class="block-outer">
            <div class="details-articles">
                {{-- 文章內容 --}}
                <div class="details">
                    <div class="detail-top" data-aos="fade-up" data-aos-duration="1000">
                        @if($article->image && $article->image->path)
                        <div class="img"><img src="{{ asset($article->image->path) }}" alt="{{ $article->title }}"></div>
                        @endif
                        @if($article->description)
                        <div class="remark">
                            <p>{{ $article->description }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="detail-bottom" data-aos="fade-up" data-aos-duration="1000">
                        {!! $article->content !!}
                    </div>

                    {{-- SDGs 標籤 --}}
                    @if($article->sdgs && count($article->sdgs) > 0)
                    <div class="sdgs-tags" data-aos="fade-up" data-aos-duration="1000" style="margin-top: 30px;">
                        <div class="card">
                            @foreach($article->sdgs as $sdg)
                                <img src="{{ asset('frontend/images/icon_sdg_' . str_pad($sdg, 2, '0', STR_PAD_LEFT) . '.png') }}" alt="SDG {{ $sdg }}" style="width: 60px; height: 60px; margin-right: 10px;">
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                {{-- 側邊欄 --}}
                <div class="articles">
                    <div class="sticky">
                        {{-- 專家資訊 --}}
                        <div class="expert-list" data-aos="fade-up" data-aos-duration="1000" data-aos-delay="300">
                            <div class="expert">
                                <div class="img">
                                    @if($expert->image && $expert->image->path)
                                        <b><img src="{{ asset($expert->image->path) }}" alt="{{ $expert->name }}"></b>
                                    @else
                                        <b><img src="{{ asset('frontend/images/expert_img_01.jpg') }}" alt="{{ $expert->name }}"></b>
                                    @endif
                                </div>
                                <div class="info">
                                    <div class="name"><h3>{{ $expert->getTranslation('name', 'zh_TW') }}</h3></div>
                                    <div class="field">
                                        <b>專家領域</b> {{ str_replace(',', '、', $expert->tags) }}
                                    </div>
                                    <div class="describe">
                                        <p>{{ Str::limit(strip_tags($expert->getTranslation('bio', 'zh_TW')), 100) }}</p>
                                    </div>
                                </div>
                                <div class="more">
                                    <a href="{{ route('afl.story.index', $expert->id) }}">瞭解更多<i></i></a>
                                </div>
                            </div>
                        </div>

                        {{-- 專家專欄（其他文章） --}}
                        @if($relatedArticles->count() > 0)
                        <div class="lastest-articles expert-articles" data-aos="fade-up" data-aos-duration="1000">
                            <div class="headline">
                                <h3>專家專欄</h3>
                            </div>
                            <div class="list">
                                <ul>
                                    @foreach($relatedArticles as $related)
                                    <li>
                                        <a href="{{ route('afl.story.show', ['expertId' => $expert->id, 'articleId' => $related->id]) }}">
                                            {{ $related->title }}
                                        </a>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 上下篇文章 --}}
            <div class="two-articles">
                @if($nextArticle)
                <div class="item">
                    <a href="{{ route('afl.story.show', ['expertId' => $expert->id, 'articleId' => $nextArticle->id]) }}">
                        <div class="img">
                            <span>
                                @if($nextArticle->image && $nextArticle->image->path)
                                    <img src="{{ asset($nextArticle->image->path) }}" alt="{{ $nextArticle->title }}">
                                @else
                                    <img src="{{ asset('frontend/images/article_img_01.jpg') }}" alt="{{ $nextArticle->title }}">
                                @endif
                            </span>
                        </div>
                        <div class="info">
                            <div class="sub-info-01">下篇文章</div>
                            <div class="sub-info-02">
                                <h4>{{ $nextArticle->title }}</h4>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
                @if($prevArticle)
                <div class="item">
                    <a href="{{ route('afl.story.show', ['expertId' => $expert->id, 'articleId' => $prevArticle->id]) }}">
                        <div class="img">
                            <span>
                                @if($prevArticle->image && $prevArticle->image->path)
                                    <img src="{{ asset($prevArticle->image->path) }}" alt="{{ $prevArticle->title }}">
                                @else
                                    <img src="{{ asset('frontend/images/article_img_01.jpg') }}" alt="{{ $prevArticle->title }}">
                                @endif
                            </span>
                        </div>
                        <div class="info">
                            <div class="sub-info-01">上篇文章</div>
                            <div class="sub-info-02">
                                <h4>{{ $prevArticle->title }}</h4>
                            </div>
                        </div>
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- 返回列表 --}}
        <div class="back-list" data-aos="fade-up" data-aos-duration="1000">
            <a href="{{ route('afl.story.index', $expert->id) }}">返回列表</a>
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
