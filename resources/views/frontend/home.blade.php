@extends('frontend.layouts.app')

@section('title', $siteInfo['title'])

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('website', $siteInfo) !!}
@endpush

@section('content')
<section class="section-home">
    <div class="home-swiper-banner">
        <div class="swiper swiperBanner">
            <div class="swiper-wrapper">
                @forelse($banners as $banner)
                <div class="swiper-slide">
                    <div class="img">
                        @if($banner['desktop_image'])
                        <img src="{{ asset($banner['desktop_image']) }}" class="web" alt="{{ $banner['title'] }}" loading="eager" fetchpriority="high" decoding="async">
                        @else
                        <img src="{{ asset('frontend/images/default.webp') }}" class="web" alt="{{ $banner['title'] }}" loading="eager" fetchpriority="high" decoding="async">
                        @endif
                        @if($banner['mobile_image'])
                        <img src="{{ asset($banner['mobile_image']) }}" class="mobile" alt="{{ $banner['title'] }}" loading="eager" fetchpriority="high" decoding="async">
                        @else
                        <img src="{{ asset('frontend/images/default.webp') }}" class="mobile" alt="{{ $banner['title'] }}" loading="eager" fetchpriority="high" decoding="async">
                        @endif
                    </div>    
                    <div class="info" data-aos="fade-right">
                        @if($banner['tags'])
                        <div class="tags">
                            @foreach(explode(',', $banner['tags']) as $tag)
                                @if(trim($tag))
                                    <span class="tag">{{ trim($tag) }}</span>
                                @endif
                            @endforeach
                        </div>
                        @endif
                        @if($banner['title'])
                        <div class="title"><h1>{{ $banner['title'] }}</h1></div>
                        @endif
                        @if($banner['subtitle_1'])
                        <div class="views-episod">
                            <div class="episod">{{ $banner['subtitle_1'] }}</div>
                        </div>
                        @endif
                        @if($banner['subtitle_2'])
                        <div class="desc">
                            <p>{!! nl2br(e($banner['subtitle_2'])) !!}</p>
                        </div>
                        @endif
                        @if($banner['url'])
                        <div class="more"><a href="{{ $banner['url'] }}">{{ __('frontend.btn.watch_now') }}</a></div>
                        @endif      
                    </div>    
                </div>
                @empty
                {{-- 如果沒有輪播圖，顯示預設內容 --}}
                <div class="swiper-slide">
                    <div class="img">
                        <picture>
                            <source media="(max-width: 768px)" srcset="{{ asset('frontend/images/home_slider_img_01_mobile.jpg') }}">
                            <img src="{{ asset('frontend/images/home_slider_img_01.jpg') }}" alt="{{ __('frontend.site.slogan') }}" loading="eager" fetchpriority="high" decoding="async">
                        </picture>
                    </div>    
                    <div class="info" data-aos="fade-right">
                        <div class="title"><h1>{{ $siteInfo['title'] }}</h1></div>
                        <div class="desc">
                            <p>{{ __('frontend.site.slogan') }}</p>
                        </div>
                    </div>    
                </div>
                @endforelse                      
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
    
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.hot_news') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('articles.index') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="swiper-list-div" data-aos="fade-up" data-aos-duration="2000">
                <div class="swiper swiperList" id="swiperHotNews">
                    <div class="swiper-wrapper">
                        @forelse($articles as $index => $article)
                        <div class="swiper-slide">
                            <a href="{{ route('articles.show', $article['id']) }}">
                                <div class="img">
                                    <img src="{{ $article['image'] ?? asset('frontend/images/default.webp') }}" title="{{ $article['title'] }}" loading="lazy" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="order"><h3>TOP {{ $index + 1 }}</h3></div>
                                    <div class="desc">
                                        <p>{{ Str::limit($article['title'], 30) }}</p>
                                    </div>    
                                </div>    
                            </a>
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <a href="#">
                                <div class="img">
                                    <img src="{{ asset('frontend/images/default.webp') }}" loading="lazy" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="order"><h3>TOP 1</h3></div>
                                    <div class="desc">
                                        <p>{{ __('frontend.status.no_news') }}</p>
                                    </div>    
                                </div>    
                            </a>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>        
            </div>
        </div>
    </div>
    
    <div class="block-div block-02">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.hot_drama') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('drama.index') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="swiper-list-div" data-aos="fade-up" data-aos-duration="2000">
                <div class="swiper swiperList" id="swiperHotDrama">
                    <div class="swiper-wrapper">
                        @forelse($dramas as $drama)
                        <div class="swiper-slide">
                            <a href="{{ route('drama.videos.index', $drama['id']) }}">
                                <div class="img">
                                    <img src="{{ $drama['poster_desktop'] ?? asset('frontend/images/default.webp') }}" class="web" decoding="async">
                                    <img src="{{ $drama['poster_mobile'] ?? asset('frontend/images/default.webp') }}" class="mobile" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="program"><h3>{{ Str::limit($drama['title'], 20) }}</h3></div>                                           
                                </div>
                            </a>  
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <a href="#">
                                <div class="img">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="web" decoding="async">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="mobile" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="program"><h3>{{ __('frontend.status.no_drama') }}</h3></div>                                           
                                </div>
                            </a>  
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>        
            </div>
        </div>
    </div>
    
    <div class="block-div block-03">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.featured_program') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('program.index') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="swiper-list-div" data-aos="fade-up" data-aos-duration="2000">
                <div class="swiper swiperList" id="swiperPopularPrograms">
                    <div class="swiper-wrapper">
                        @forelse($programs as $program)
                        <div class="swiper-slide">
                            <a href="{{ route('program.videos.index', $program['id']) }}">
                                <div class="img">
                                    <img src="{{ $program['poster_desktop'] ?? asset('frontend/images/default.webp') }}" class="web" decoding="async">
                                    <img src="{{ $program['poster_mobile'] ?? asset('frontend/images/default.webp') }}" class="mobile" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="program"><h3>{{ Str::limit($program['title'], 20) }}</h3></div>                                           
                                </div>
                            </a>   
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <a href="#">
                                <div class="img">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="web" decoding="async">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="mobile" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="program"><h3>{{ __('frontend.status.no_program') }}</h3></div>                                           
                                </div>
                            </a>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>        
            </div>
        </div>
    </div>
    
    <div class="block-div block-04">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.live_feed') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('live.index') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="live-feed-div" ><!--電腦版-->
                <div class="two-cols">
                    @if($lives->count() > 0)
                    <div class="col01" data-aos="fade-right" data-aos-duration="2000">                               
                        <div class="video">
                            <a href="{{ route('live.index', $lives->first()['id']) }}">
                                    <div class="img" style="background-image: url({{ $lives->first()['thumbnail'] ?? asset('frontend/images/default.webp') }});">
                                    <img src="{{ $lives->first()['thumbnail'] ?? asset('frontend/images/default.webp') }}" loading="lazy" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="desc">
                                        <h3>{{ $lives->first()['title'] }}</h3>
                                    </div>    
                                </div>   
                            </a>
                        </div>                                   
                    </div>
                    @endif
                    @if($lives->count() > 1)
                    <div class="col02" data-aos="fade-left" data-aos-duration="2000" data-aos-delay="300">  
                        <div class="video-list">
                            @foreach($lives->slice(1, 4) as $live)
                            <div class="video">
                                <a href="{{ route('live.index', $live['id']) }}">
                                    <div class="img">
                                        <img src="{{ $live['thumbnail'] ?? asset('frontend/images/default.webp') }}" loading="lazy" decoding="async">
                                    </div>    
                                    <div class="info">
                                        <div class="desc">
                                            <h3>{{ $live['title'] }}</h3>
                                        </div>    
                                    </div> 
                                </a>
                            </div>
                            @endforeach
                        </div>                                 
                    </div>
                    @endif   
                </div>    
            </div>
            <div class="swiper-list-div swiper-live-feed" data-aos="fade-up" data-aos-duration="2000"><!--手機版-->
                <div class="swiper swiperList" id="swiperLiveFeed">
                    <div class="swiper-wrapper">
                        @forelse($lives as $live)
                        <div class="swiper-slide">
                            <a href="{{ route('live.index', $live['id']) }}">
                                <div class="img">
                                    <img src="{{ $live['thumbnail'] ?? asset('frontend/images/default.webp') }}" class="web" loading="lazy" decoding="async">
                                    <img src="{{ $live['thumbnail'] ?? asset('frontend/images/default.webp') }}" class="mobile" loading="lazy" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="desc">
                                        <h3>{{ $live['title'] }}</h3>
                                    </div>                                           
                                </div>
                            </a>   
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <a href="#">
                                <div class="img">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="web" loading="lazy" decoding="async">
                                    <img src="{{ asset('frontend/images/default.webp') }}" class="mobile" loading="lazy" decoding="async">
                                </div>    
                                <div class="info">
                                    <div class="desc">
                                        <h3>{{ __('frontend.status.no_live') }}</h3>
                                    </div>    
                                </div>
                            </a>
                        </div>
                        @endforelse                         
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>        
            </div>
        </div>
    </div>        
    
    <div class="block-div block-05">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.radio_listen') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('radio.index') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="swiper-list-div" data-aos="fade-up" data-aos-duration="2000">
                <div class="swiper swiperList" id="swiperRadioListening">
                    <div class="swiper-wrapper">
                        @forelse($radios as $radio)
                        <div class="swiper-slide">
                            <a href="{{ route('radio.show', $radio['id']) }}">
                                <div class="img">
                                    <img src="{{ $radio['image'] ?? asset('frontend/images/default.webp') }}">
                                </div>    
                                <div class="info">
                                    <div class="program">
                                        <h3>{{ Str::limit($radio['title'], 20) }}</h3>
                                        <p>{{ $radio['media_name'] ?? '' }}</p>
                                    </div>                           
                                </div>
                            </a>
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <a href="#">
                                <div class="img">
                                    <img src="{{ asset('frontend/images/default.webp') }}">
                                </div>    
                                <div class="info">
                                    <div class="program">
                                        <h3>{{ __('frontend.status.no_radio') }}</h3>
                                        <p>&nbsp;</p>
                                    </div>                           
                                </div>
                            </a>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>        
            </div>
        </div>
    </div>
    
    <div class="block-div block-06">
        <div class="block-outer">
            <div class="block-title" data-aos="fade-up" data-aos-duration="2000">
                <div class="sub-title">
                    <h2>{{ __('frontend.section.latest_focus') }}</h2>
                </div>
                <div class="more">    
                    <a href="{{ route('news') }}">{{ __('frontend.btn.view_more') }}</a>
                </div>    
            </div>
            <div class="foucs-list-div" data-aos="fade-up" data-aos-duration="2000">
                @forelse($latestFocus as $focus)
                <div class="item">
                    <a href="{{ route('news.show', $focus['id']) }}">
                        <div class="img">
                            <img src="{{ $focus['image'] ?? asset('frontend/images/default.webp') }}" loading="lazy" decoding="async" onerror="this.onerror=null; this.src='{{ asset('frontend/images/default.webp') }}'">
                        </div> 
                        <div class="info">
                            <div class="datetime">{{ \Carbon\Carbon::parse($focus['published_date'])->format('Y.m.d') }}</div>
                            <div class="program"><h3>{{ $focus['title'] }}</h3></div>                               
                        </div>
                    </a>      
                </div>
                @empty
                <div class="item">
                    <a href="#">
                        <div class="img">
                            <img src="{{ asset('frontend/images/default.webp') }}" loading="lazy" decoding="async">
                        </div> 
                        <div class="info">
                            <div class="datetime">{{ now()->format('Y.m.d') }}</div>
                            <div class="program"><h3>{{ __('frontend.status.no_focus') }}</h3></div>                               
                        </div>     
                    </a>  
                </div>
                @endforelse   
            </div>
        </div>
    </div>            
</section>
@endsection

@push('styles')
<style>
    /* 影音/節目輪播：電腦 172:97、手機 5:6 */
    #swiperHotDrama .swiper-slide .img,
    #swiperPopularPrograms .swiper-slide .img {
        position: relative;
        width: 100%;
        aspect-ratio: 172 / 97;
        overflow: hidden;
    }
    #swiperHotDrama .swiper-slide .img img,
    #swiperPopularPrograms .swiper-slide .img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    /* 桌面版：顯示 .web 圖片，隱藏 .mobile 圖片 */
    #swiperHotDrama .swiper-slide .img img.mobile,
    #swiperPopularPrograms .swiper-slide .img img.mobile {
        display: none;
    }
    
    /* 手機版 */
    @media (max-width: 768px) {
        #swiperHotDrama .swiper-slide .img,
        #swiperPopularPrograms .swiper-slide .img { 
            aspect-ratio: 5 / 6; 
        }
        
        /* 手機版：隱藏 .web 圖片，顯示 .mobile 圖片 */
        #swiperHotDrama .swiper-slide .img img.web,
        #swiperPopularPrograms .swiper-slide .img img.web {
            display: none;
        }
        
        #swiperHotDrama .swiper-slide .img img.mobile,
        #swiperPopularPrograms .swiper-slide .img img.mobile {
            display: block;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    jQuery(document).ready(function($) {
        AOS.init();
        
        // 確保 Swiper 初始化
        if (typeof setSwiper === 'function') {
            setSwiper();
        }
    });
</script>
@endpush