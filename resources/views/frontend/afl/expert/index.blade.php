@extends('frontend.layouts.afl')

@section('title', '生命故事')

@section('main-class', 'main-expert-list')

@section('content')
<section class="section-expert-list">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a><i>/</i>生命故事
            </div>
        </div>
    </div>

    {{-- 首席專家 & 熱門專家區塊 --}}
    <div class="block-div block-main-hot-experts">
        <div class="block-outer">
            <div class="main-hot-experts">
                {{-- 首席專家 --}}
                @if($featuredExpert)
                <div class="main-expert">
                    <div class="subtitle" data-aos="fade-up" data-aos-duration="1000"><h2>首席專家</h2></div>
                    <div class="expert" data-aos="fade-up" data-aos-duration="1000">
                        <div class="img">
                            @if($featuredExpert->image && $featuredExpert->image->path)
                                <b><img src="{{ asset($featuredExpert->image->path) }}" alt="{{ $featuredExpert->name }}"></b>
                            @else
                                <b><img src="{{ asset('frontend/images/expert_img_01.jpg') }}" alt="{{ $featuredExpert->name }}"></b>
                            @endif
                            <span>首席專家</span>
                        </div>
                        <div class="info">
                            <div class="name"><h3>{{ $featuredExpert->getTranslation('name', 'zh_TW') }}</h3></div>
                            <div class="field">
                                <b>專家領域</b> {{ str_replace(',', '、', $featuredExpert->tags) }}
                            </div>
                            <div class="describe">
                                <p>{{ Str::limit(strip_tags($featuredExpert->getTranslation('bio', 'zh_TW')), 120) }}</p>
                            </div>
                        </div>
                        <div class="more">
                            <a href="{{ route('afl.story.index', $featuredExpert->id) }}">瞭解更多<i></i></a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- 熱門專家 --}}
                @if($hotExperts->count() > 0)
                <div class="hot-experts" data-aos="fade-up" data-aos-duration="1000">
                    <div class="subtitle" data-aos="fade-up" data-aos-duration="1000"><h2>熱門專家</h2></div>
                    <div class="expert-list" data-aos="fade-up" data-aos-duration="1000">
                        @foreach($hotExperts as $hotExpert)
                        <div class="expert">
                            <div class="img">
                                @if($hotExpert->image && $hotExpert->image->path)
                                    <b><img src="{{ asset($hotExpert->image->path) }}" alt="{{ $hotExpert->name }}"></b>
                                @else
                                    <b><img src="{{ asset('frontend/images/expert_img_01.jpg') }}" alt="{{ $hotExpert->name }}"></b>
                                @endif
                            </div>
                            <div class="info">
                                <div class="name"><h3>{{ $hotExpert->getTranslation('name', 'zh_TW') }}</h3></div>
                                <div class="field">
                                    <b>專家領域</b> {{ str_replace(',', '、', $hotExpert->tags) }}
                                </div>
                                <div class="describe">
                                    <p>{{ Str::limit(strip_tags($hotExpert->getTranslation('bio', 'zh_TW')), 80) }}</p>
                                </div>
                            </div>
                            <div class="more">
                                <a href="{{ route('afl.story.index', $hotExpert->id) }}"><i></i></a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 分類篩選 --}}
    <div class="block-div block-expert-category">
        <div class="block-outer">
            <div class="category-scroller">
                <div class="category-links" data-aos="fade-up" data-aos-duration="1000">
                    <div class="link">
                        <a href="{{ route('afl.expert.index') }}" class="{{ !request('category') ? 'active' : '' }}">全部</a>
                    </div>
                    @foreach($categories as $category)
                    <div class="link">
                        <a href="{{ route('afl.expert.index', ['category' => $category->id]) }}" 
                           class="{{ request('category') == $category->id ? 'active' : '' }}">
                            {{ $category->name }}
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- 專家列表 --}}
            <div class="expert-category-list" data-aos="fade-up" data-aos-duration="1000">
                <div class="list-items">
                    @forelse($expertList as $expert)
                    <div class="item">
                        <a href="{{ route('afl.story.index', $expert->id) }}">
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
                                        <p>{{ Str::limit(strip_tags($expert->getTranslation('bio', 'zh_TW')), 80) }}</p>
                                    </div>
                                </div>
                                <div class="more">
                                    <span><i></i></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="no-data">
                        <p>目前尚無專家資料</p>
                    </div>
                    @endforelse
                </div>

                {{-- 分頁 --}}
                @if($expertList->total() > 0)
                <div class="pagination-div">
                    {{-- 上一頁 --}}
                    @if($expertList->onFirstPage())
                        {{-- 不顯示上一頁按鈕 --}}
                    @else
                        <a href="{{ $expertList->previousPageUrl() }}" class="prev"></a>
                    @endif

                    {{-- 頁碼 --}}
                    @foreach($expertList->getUrlRange(1, $expertList->lastPage()) as $page => $url)
                        <a href="{{ $url }}" class="{{ $expertList->currentPage() == $page ? 'active' : '' }}">{{ $page }}</a>
                    @endforeach

                    {{-- 下一頁 --}}
                    @if($expertList->hasMorePages())
                        <a href="{{ $expertList->nextPageUrl() }}" class="next"></a>
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
