{{-- resources/views/frontend/media/videos/index.blade.php --}}
@extends('frontend.layouts.app')

@section('title', $content['title'] . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('description', Str::limit($content['description'], 150))
@section('keywords', implode(', ', [$content['title'], $type === 'drama' ? __('frontend.nav.drama') : __('frontend.nav.program'), $siteInfo['title'] ?? '信吉衛視']))

{{-- JSON-LD 結構化資料 --}}
@push('head')
@php
    // 組合完整資料給 JsonLd 使用
    $jsonLdData = array_merge($content, [
        'episodes' => $episodes,
        'seasonInfo' => $seasonInfo ?? []
    ]);
@endphp
@if($type === 'drama' || $type === 'program')
    {!! \App\Facades\JsonLd::generateByType('media-content', [
        'type' => $type,
        'data' => $jsonLdData
    ]) !!}
@endif
@endpush

{{-- 預載重要圖片 --}}
@push('styles')
<link rel="preload" as="image" href="{{ $content['banner_desktop'] ?: asset('frontend/images/video_list_big_banner_01.jpg') }}" media="(min-width: 768px)">
<link rel="preload" as="image" href="{{ $content['banner_mobile'] ?: asset('frontend/images/video_list_big_banner_01_mobile.jpg') }}" media="(max-width: 767px)">
<style>
    /* 使用 aspect-ratio 確保圖片容器符合新的圖片比例 */
    .section-video-list .big-video-introduce .img {
        position: relative;
        width: 100%;
        aspect-ratio: 1915 / 798; /* 桌機版 banner 圖片比例 */
        overflow: hidden;
    }

    /* 確保圖片正確填滿容器 */
    .section-video-list .big-video-introduce .img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* 手機版根據後台設定的比例 430x240 */
    @media (max-width: 768px) {
        .section-video-list .big-video-introduce .img {
            aspect-ratio: 430 / 240; /* 手機版 banner 圖片比例 (約 1.79:1) */
        }
    }
</style>
@endpush

@section('content')
<section class="section-video-list">
    <div class="big-video-introduce">
        <div class="img" style="background-color: #111319;">
            <picture>
                @if($content['banner_mobile'])
                <source media="(max-width: 768px)" srcset="{{ $content['banner_mobile'] }}">
                @endif
                <img src="{{ $content['banner_desktop'] ?: asset('frontend/images/video_list_big_banner_01.jpg') }}"
                     alt="{{ $content['title'] }}"
                     loading="eager">
            </picture>
        </div>
        <div class="info">
            <div class="title"><h1>{{ $content['title'] }}</h1></div>
            <div class="views-episod">
                <div class="episod">{{ $content['release_year'] }} ·  {{ __('frontend.video.seasons_count', ['count' => $content['season_number']]) }}</div>
            </div>
            <div class="desc">
                <p>{{ $content['description'] }}</p>
            </div>
            <div class="sub-info"><a href="javascript:;" data-popup-id="popupVideoInfo">{{ __('frontend.video.more_info') }}<i></i></a></div>
            <div class="more">
                @if($episodes->isNotEmpty() && $episodes->first()->isNotEmpty())
                    <a href="{{ route($type . '.video.show', [$content['id'], $episodes->first()->first()['id']]) }}">{{ __('frontend.btn.watch_now') }}</a>
                @else
                    <a href="javascript:;" class="disabled">{{ __('frontend.status.no_episodes') }}</a>
                @endif
                <div class="links">
                    @auth
                        @if(auth()->user()->hasVerifiedEmail() && auth()->user()->profile_completed == 1)
                            <!-- Vue 收藏按鈕組件 -->
                            <collection-button 
                                content-type="{{ $type }}" 
                                :content-id="{{ $content['id'] }}"
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
            </div>
        </div>
    </div>
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ __('frontend.video.episodes') }}</h2>
                </div>
                <div class="more" id="seasonSelector">
                    @if(count($episodes) > 0)
                    <div class="dropdown-select" data-id="dropdownEpisode">
                        <button type="button"><span><b>{{ __('frontend.video.season_number', ['number' => array_key_first($episodes->toArray()) ?? 1]) }}</b></span><i></i></button>
                        <div class="sub-menu">
                            @foreach($episodes as $season => $seasonEpisodes)
                            <div class="sub-item">
                                <input type="radio" name="episode" {{ $loop->first ? 'checked' : '' }}>
                                <span>{{ __('frontend.video.season_number', ['number' => $season]) }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="episode-list-div" id="episodeListApp">
                {{-- Vue 組件掛載點 --}}
                <episode-list
                    :episodes='@json($episodes)'
                    :content-id="{{ $content['id'] }}"
                    :content-type="'{{ $type }}'"
                    :total-seasons="{{ $content['season_number'] }}"
                    :texts="{
                        episode: '{{ __('frontend.video.episode') }}',
                        noEpisodes: '{{ __('frontend.status.no_episodes') }}'
                    }"
                ></episode-list>

                {{-- SEO 備用內容（Vue 載入前顯示） --}}
                <noscript>
                    @foreach($episodes as $season => $seasonEpisodes)
                        @foreach($seasonEpisodes as $episode)
                            <div class="item">
                                <a href="{{ route($type . '.video.show', [$content['id'], $episode['id']]) }}">
                                    第 {{ $episode['seq'] }} 集 - {{ $episode['duration'] }}
                                </a>
                            </div>
                        @endforeach
                    @endforeach
                </noscript>
            </div>
            @if(!empty($recommendations))
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ __('frontend.video.recommended') }}</h2>
                </div>
                <div class="more">

                </div>
            </div>
            <div class="swiper-list-div" data-aos="fade-up" data-aos-duration="2000">
                <div class="swiper swiperList" id="swiperRecommended">
                    <div class="swiper-wrapper">
                        @foreach($recommendations as $recommend)
                        <div class="swiper-slide">
                            <a href="{{ route($type . '.videos.index', $recommend['id']) }}">
                                <div class="img">
                                    <picture>
                                        @if($recommend['poster_mobile'])
                                        <source media="(max-width: 768px)" srcset="{{ $recommend['poster_mobile'] }}">
                                        @endif
                                        <img src="{{ $recommend['poster_desktop'] ?: asset('frontend/images/popular_programs_img_01.jpg') }}"
                                             alt="{{ $recommend['title'] }}"
                                             loading="lazy">
                                    </picture>
                                </div>
                                <div class="info">
                                    <div class="program"><h3>{{ $recommend['title'] }}</h3></div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@section('popups')
{{-- 更多資訊彈窗 --}}
<div class="popup-div popup-middle-div" id="popupVideoInfo">
    <div class="block-outer">
        <div class="close"><i  data-close-id="popupVideoInfo"></i></div>
        <div class="boxer" style="align-items: flex-start;">
            <div class="video-introduce" style="width: 100%;">
                <div class="info">
                    <h3>{{ __('frontend.video.synopsis') }}</h3>
                    <p>{{ $content['description'] ?? '' }}</p>
                    @if(!empty($content['cast']))
                        <h3>{{ __('frontend.video.cast') }}</h3>
                        <p>{{ $content['cast'] }}</p>
                    @endif
                    @if(!empty($content['crew']))
                        <h3>{{ __('frontend.video.crew') }}</h3>
                        <p>{!! nl2br($content['crew']) !!}</p>
                    @endif
                    @if(!empty($content['other_info']))
                        <h3>{{ __('frontend.video.other_info') }}</h3>
                        <p>{!! nl2br($content['other_info']) !!}</p>
                    @endif
                </div>
                @if(!empty($content['tags']) && is_array($content['tags']))
                <div class="tags">
                    @foreach($content['tags'] as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($){
        $(function() {
            AOS.init();

            // 初始化彈窗功能（使用 script.js 中的函數）
            if (typeof setVideoSubInfoPopup === 'function') {
                setVideoSubInfoPopup();
            }

            // 初始化下拉選單功能
            if (typeof setDropDownSelect === 'function') {
                setDropDownSelect();
            }

            // 季數選擇器點擊事件
            $('#seasonSelector .dropdown-select button').off('click').on('click', function(e) {
                e.stopPropagation();
                var parent = $(this).closest('.dropdown-select');
                
                if (parent.hasClass('active')) {
                    parent.removeClass('active');
                } else {
                    $('.dropdown-select').removeClass('active');
                    parent.addClass('active');
                }
            });

            // 選擇季數
            $('#seasonSelector .dropdown-select .sub-item').off('click').on('click', function(e) {
                e.stopPropagation();
                var txt = $(this).find('span').html();
                var parent = $(this).closest('.dropdown-select');
                parent.find('button span b').html(txt);
                parent.removeClass('active');
            });

            // 點擊外部關閉下拉選單
            $(document).off('click.seasonDropdown').on('click.seasonDropdown', function(e) {
                if (!$(e.target).closest('#seasonSelector .dropdown-select').length) {
                    $('#seasonSelector .dropdown-select').removeClass('active');
                }
            });

            // 圖片淡入載入效果
            $('.fade-in-image').each(function() {
                var img = $(this);
                var imgSrc = img.attr('src');

                // 建立新的圖片物件來預載
                var newImg = new Image();
                newImg.onload = function() {
                    // 圖片載入完成後添加 loaded 類別
                    img.addClass('loaded');
                    img.closest('.img').addClass('images-loaded');
                };
                newImg.src = imgSrc;

                // 如果圖片已經在快取中，直接顯示
                if (newImg.complete) {
                    img.addClass('loaded');
                    img.closest('.img').addClass('images-loaded');
                }
            });

            // 處理 banner 圖片載入
            $('.big-video-introduce .img img').each(function() {
                var img = $(this);
                if (img.is(':visible')) {
                    var tempImg = new Image();
                    tempImg.onload = function() {
                        img.closest('.img').addClass('images-loaded');
                    };
                    tempImg.src = img.attr('src');
                    
                    // 如果圖片已在快取中
                    if (tempImg.complete) {
                        img.closest('.img').addClass('images-loaded');
                    }
                }
            });
        });

        // 初始化推薦輪播
        var swiperRecommended = new Swiper("#swiperRecommended", {
            slidesPerView: 4,
            spaceBetween: 20,
            navigation: {
                nextEl: "#swiperRecommended .swiper-button-next",
                prevEl: "#swiperRecommended .swiper-button-prev",
            },
            breakpoints: {
                350: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 15,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 15,
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 20,
                }
            }
        });
    });
</script>
@endpush
