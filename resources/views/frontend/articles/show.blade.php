@extends('frontend.layouts.app')

@section('title', $article['title'] . ' - ' . $siteInfo['title'])
@section('description', Str::limit(strip_tags($article['content']), 120) . ' - ' . $siteInfo['title'] . __('frontend.nav.news'))
@section('keywords', $siteInfo['title'] . ', ' . __('frontend.nav.news') . ', ' . ($article['tags'] ?? ''))

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('article', $article) !!}
@endpush


@section('content')
<section class="section-hot-news-detail">
    <div class="breadcrumb-div">
        <div class="breadcrumb">
            <i></i>
            <span></span>
            <a href="{{ route('articles.index') }}">{{ __('frontend.nav.news') }}</a>
            <span></span>
            @if(isset($article['category_name']) && $article['category_name'])
                {{ $article['category_name'] }}
            @else
                {{ __('frontend.status.uncategorized') }}
            @endif
            <span></span>
            {{ $article['title'] }}
        </div>
    </div>
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="datetime-back">
                <div class="two-cols">
                    <div class="col01">
                        <div class="back">
                            <a href="{{ route('articles.index') }}"
                               onclick="event.preventDefault(); var prevPage = document.referrer; if(prevPage && prevPage.indexOf(window.location.host) !== -1) { window.history.back(); } else { window.location.href = this.href; }">
                                <i></i>{{ __('frontend.btn.back') }}
                            </a>
                        </div>
                    </div>
                    <div class="col02">
                        <div class="tags">
                            @if($article['tags'])
                                @foreach(explode(',', $article['tags']) as $tag)
                                    <span class="tag">{{ trim($tag) }}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="datetime">
                            {{ $article['publish_date'] }}
                            @if($article['author'])・{{ $article['author'] }}@endif
                            @if($article['location'])・{{ $article['location'] }}@endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ $article['title'] }}</h2>
                </div>
            </div>
            <div class="news-detail-related">
                <div class="two-cols">
                    <div class="col01">
                        <div class="links">
                            @auth
                                @if(auth()->user()->hasVerifiedEmail() && auth()->user()->profile_completed == 1)
                                    <!-- Vue 收藏按鈕組件 -->
                                    <collection-button
                                        content-type="articles"
                                        :content-id="{{ $article['id'] }}"
                                        :show-text="false"
                                        button-class="collection-icon-btn"
                                        :texts="{
                                            collect: '{{ __('frontend.btn.collect') }}',
                                            collected: '{{ __('frontend.btn.collected') }}',
                                            uncollect: '{{ __('frontend.btn.uncollect') }}'
                                        }">
                                    </collection-button>
                                @endif
                            @endauth
                        </div>
                        <div class="news-detail-div">
                            {!! $article['content'] !!}
                        </div>
                    </div>
                    @if(isset($relatedArticles) && count($relatedArticles) > 0)
                    <div class="col02">
                        <div class="related-list">
                            <div class="sub-title"><h3>{{ __('frontend.section.related_news') }}</h3></div>
                            <div class="list-news">
                                @foreach($relatedArticles as $related)
                                    <div class="news">
                                        <a href="{{ route('articles.show', $related['id']) }}">
                                            <div class="img">
                                                <img
                                                    src="{{ $related['image'] ?: asset('frontend/images/default.webp') }}"
                                                    alt="{{ $related['title'] }}"
                                                    loading="lazy"
                                                    decoding="async">
                                            </div>
                                            <div class="info">
                                                <div class="datetime">{{ $related['category_name'] }}・{{ $related['publish_date'] }}</div>
                                                <div class="desc">
                                                    <h3>{{ $related['title'] }}</h3>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Vue 觀看記錄組件 -->
            <view-recorder
                content-type="article"
                :content-id="{{ $article['id'] }}"
                :delay="3000">
            </view-recorder>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($){$(function() {      AOS.init(); });  });
    $('[data-fancybox="gallery01"],[data-fancybox="gallery02"]').fancybox({});
</script>
@endpush
