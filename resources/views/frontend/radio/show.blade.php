@extends('frontend.layouts.app')

@section('title', $radio['title'] . ' - ' . __('frontend.nav.radio') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('radio', $radio) !!}
@endpush

{{-- 預載重要圖片 --}}
@push('styles')
<link rel="preload" as="image" href="{{ $radio['banner_desktop'] ?: asset('frontend/images/video_list_big_banner_01.jpg') }}" media="(min-width: 768px)">
<link rel="preload" as="image" href="{{ $radio['banner_mobile'] ?: asset('frontend/images/video_list_big_banner_01_mobile.jpg') }}" media="(max-width: 767px)">
<style>
    /* 使用 aspect-ratio 確保圖片容器符合圖片比例 */
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
    <section class="section-video-list section-radio-detail-list">
        {{-- 頂部大 Banner --}}
        <div class="big-video-introduce">
            <div class="img" style="background-color: #111319;">
                <picture>
                    @if($radio['banner_mobile'])
                    <source media="(max-width: 768px)" srcset="{{ $radio['banner_mobile'] }}">
                    @endif
                    <img src="{{ $radio['banner_desktop'] ?: asset('frontend/images/video_list_big_banner_01.jpg') }}"
                         alt="{{ $radio['title'] }}"
                         loading="eager">
                </picture>
            </div>
            <div class="info">
                <div class="title"><h1>{{ $radio['title'] }}</h1></div>
                <div class="views-episod">
                    <div class="episod">
                        {{ $radio['year'] ?? '' }}
                        @if($radio['total_seasons'] > 0)
                            · {{ __('frontend.radio.seasons_count', ['count' => $radio['total_seasons']]) }}
                        @endif
                    </div>
                </div>
                @if($radio['media_name'])
                <div class="program">{{ $radio['media_name'] }}</div>
                @endif
                @if($radio['description'])
                <div class="desc">
                    <p>{{ $radio['description'] }}</p>
                </div>
                @endif
                <div class="more">
                    <div class="links">
                        @auth
                            @if(auth()->user()->hasVerifiedEmail() && auth()->user()->profile_completed == 1)
                                <!-- Vue 收藏按鈕組件 -->
                                <collection-button
                                    content-type="radio"
                                    :content-id="{{ $radio['id'] }}"
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

        {{-- 集數列表區塊 --}}
        <div class="block-div block-01">
            <div class="block-outer">
                <div class="block-title">
                    <div class="sub-title">
                        <h2>{{ __('frontend.radio.episodes') }}</h2>
                    </div>
                    <div class="more" id="seasonSelector">
                        @if(count($radio['seasons']) > 1)
                        <div class="dropdown-select" data-id="dropdownEpisode">
                            <button type="button">
                                <span><b>{{ __('frontend.radio.season_number', ['number' => $radio['seasons'][0] ?? 1]) }}</b></span>
                                <i></i>
                            </button>
                            <div class="sub-menu">
                                @foreach($radio['seasons'] as $season)
                                <div class="sub-item">
                                    <input type="radio" name="episode" {{ $loop->first ? 'checked' : '' }}>
                                    <span>{{ __('frontend.radio.season_number', ['number' => $season]) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Vue 集數列表組件 --}}
                <div class="episode-list-div" id="radioEpisodeListApp">
                    <radio-episode-list
                        :episodes='@json($radio['episodes_by_season'])'
                        :radio-id="{{ $radio['id'] }}"
                        :total-seasons="{{ $radio['total_seasons'] }}"
                        :seasons='@json($radio['seasons'])'
                        :texts="{
                            episode: '{{ __('frontend.radio.episode_number') }}',
                            seasonLabel: '{{ __('frontend.radio.season_number', ['number' => ':number']) }}',
                            noEpisodes: '{{ __('frontend.radio.no_episodes') }}',
                            noAudio: '{{ __('frontend.radio.no_audio') }}',
                            audioNotSupported: '{{ __('frontend.audio.not_supported') }}'
                        }"
                    ></radio-episode-list>

                    {{-- SEO 備用內容（Vue 載入前 / 無 JS 環境顯示） --}}
                    <noscript>
                        @foreach($radio['episodes_by_season'] as $season => $episodes)
                            @foreach($episodes as $episode)
                                <div class="item">
                                    <div class="info">
                                        <h3>
                                            <b>{{ __('frontend.radio.episode_number', ['number' => $episode['episode_number']]) }}</b>
                                            <span>{{ $episode['duration_text'] ?? '' }}</span>
                                        </h3>
                                        @if($episode['description'])
                                        <p>{{ $episode['description'] }}</p>
                                        @endif
                                    </div>
                                    @if($episode['audio_url'])
                                    <div class="audio">
                                        <audio controls>
                                            <source src="{{ $episode['audio_url'] }}" type="audio/mpeg">
                                            {{ __('frontend.audio.not_supported') }}
                                        </audio>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                    </noscript>
                </div>
            </div>
        </div>

        <!-- 廣播觀看記錄組件 -->
        <view-recorder
            content-type="radio"
            :content-id="{{ $radio['id'] }}"
            :delay="3000">
        </view-recorder>
    </section>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($){
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

        // 點擊外部關閉下拉選單
        $(document).off('click.seasonDropdown').on('click.seasonDropdown', function(e) {
            if (!$(e.target).closest('#seasonSelector .dropdown-select').length) {
                $('#seasonSelector .dropdown-select').removeClass('active');
            }
        });
    });
</script>
@endpush