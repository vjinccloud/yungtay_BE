@extends('frontend.layouts.afl')

@section('title', $expert->name . ' - 生命故事')

@section('main-class', 'main-story-list')

@section('content')
<section class="section-story-list">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a><i>/</i>
                <a href="{{ route('afl.expert.index') }}">生命故事</a><i>/</i>
                {{ $expert->name }}
            </div>
        </div>
    </div>

    {{-- 專家資訊區塊 --}}
    <div class="block-div block-story-expert">
        <div class="block-outer">
            <div class="expert-list">
                <div class="expert" data-aos="fade-up" data-aos-duration="1000">
                    <div class="img-name-field">
                        <div class="img">
                            @if($expert->image && $expert->image->path)
                                <b><img src="{{ asset($expert->image->path) }}" alt="{{ $expert->name }}"></b>
                            @else
                                <b><img src="{{ asset('frontend/images/expert_img_01.jpg') }}" alt="{{ $expert->name }}"></b>
                            @endif
                        </div>
                        <div class="name-field">
                            <div class="name"><h3>{{ $expert->getTranslation('name', 'zh_TW') }}</h3></div>
                            <div class="field">
                                <b>專家領域</b> {{ str_replace(',', '、', $expert->tags) }}
                            </div>
                        </div>
                    </div>
                    <div class="info">
                        <div class="describe">
                            <p>{{ $expert->getTranslation('bio', 'zh_TW') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 文章列表區塊 --}}
    <div class="block-div block-story-category">
        <div class="block-outer">
            <div class="story-title">
                <h3>所有文章</h3>
            </div>
            <div class="story-category-list" data-aos="fade-up" data-aos-duration="1000">
                <div class="list-items">
                    @forelse($articles as $article)
                    <div class="item">
                        <div class="story">
                            <a href="{{ route('afl.story.show', ['expertId' => $expert->id, 'articleId' => $article->id]) }}">
                                <div class="img">
                                    @if($article->image && $article->image->path)
                                        <img src="{{ asset($article->image->path) }}" alt="{{ $article->title }}">
                                    @else
                                        <img src="{{ asset('frontend/images/story_img_01.jpg') }}" alt="{{ $article->title }}">
                                    @endif
                                </div>
                                <div class="info">
                                    <div class="datetime">{{ $article->published_date?->format('Y-m-d') }}</div>
                                    @if($article->tags)
                                    <div class="tag">
                                        @foreach(explode(',', $article->tags) as $tag)
                                            <span class="pink01">{{ trim($tag) }}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                    <div class="headline">
                                        <h3>{{ $article->title }}</h3>
                                    </div>
                                    <div class="describe">
                                        <p>{{ Str::limit(strip_tags($article->description ?? $article->content), 80) }}</p>
                                    </div>
                                    @if($article->sdgs && count($article->sdgs) > 0)
                                    <div class="card">
                                        @foreach($article->sdgs as $sdg)
                                            <img src="{{ asset('frontend/images/icon_sdg_' . str_pad($sdg, 2, '0', STR_PAD_LEFT) . '.png') }}" alt="SDG {{ $sdg }}">
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="no-data">
                        <p>目前尚無文章資料</p>
                    </div>
                    @endforelse
                </div>

                {{-- 分頁 --}}
                @if($articles->total() > 0)
                <div class="pagination-div">
                    {{-- 上一頁 --}}
                    @if(!$articles->onFirstPage())
                        <a href="{{ $articles->previousPageUrl() }}" class="prev"></a>
                    @endif

                    {{-- 頁碼 --}}
                    @foreach($articles->getUrlRange(1, $articles->lastPage()) as $page => $url)
                        <a href="{{ $url }}" class="{{ $articles->currentPage() == $page ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach

                    {{-- 下一頁 --}}
                    @if($articles->hasMorePages())
                        <a href="{{ $articles->nextPageUrl() }}" class="next"></a>
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
