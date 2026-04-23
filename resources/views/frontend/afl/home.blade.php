{{-- AFL 首頁 --}}
@extends('frontend.layouts.afl')

@section('title', $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會')
@section('main-class', 'main-home')

@section('content')
<section class="section-home">
    {{-- 輪播區塊 (動態) --}}
    <div class="block-div block-swiper" data-aos="fade-up" data-aos-duration="500">
        <div class="block-outer">
            <div class="swiper swiperHome">
                <div class="swiper-wrapper">
                    @forelse($banners as $banner)
                    <div class="swiper-slide">
                        <div class="img">
                            @if($banner->desktopImage && $banner->desktopImage->path)
                                <img src="{{ asset($banner->desktopImage->path) }}" class="web">
                            @else
                                <img src="{{ asset('frontend/images/news_banner_01.jpg') }}" class="web">
                            @endif
                            @if($banner->mobileImage && $banner->mobileImage->path)
                                <img src="{{ asset($banner->mobileImage->path) }}" class="mobile">
                            @else
                                <img src="{{ asset('frontend/images/news_banner_01_mobile.jpg') }}" class="mobile">
                            @endif
                        </div>
                        <div class="info">
                            <div class="box">
                                <h1>{!! nl2br(e($banner->getTranslation('title', 'zh_TW'))) !!}</h1>
                                @if($banner->getTranslation('subtitle_1', 'zh_TW'))
                                <p>{{ $banner->getTranslation('subtitle_1', 'zh_TW') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    {{-- 如果沒有輪播圖，顯示預設 --}}
                    <div class="swiper-slide">
                        <div class="img">
                            <img src="{{ asset('frontend/images/news_banner_01.jpg') }}" class="web">
                            <img src="{{ asset('frontend/images/news_banner_01_mobile.jpg') }}" class="mobile">
                        </div>
                        <div class="info">
                            <div class="box">
                                <h1>你的身心靈<br />都值得被溫柔對待</h1>
                                <p>以更有效地面對重要且迫切的社會議題。</p>
                            </div>
                        </div>
                    </div>
                    @endforelse
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </div>

    {{-- 為愛前行區塊 --}}
    <div class="block-div block-dance">
        <div class="block-outer">
            <div class="two-cols">
                <div class="col01" data-aos="fade-right" data-aos-duration="500">
                    <div class="title">
                        <h2>為愛前行<br />舞動生命的光</h2>
                        <p>陪伴每一個選擇，讓生命持續發光。</p>
                    </div>
                    <div class="info-photo">
                        <div class="info">
                            <h3>真正的幸福是身心靈的溫柔守護。<br />您的每一次參與，將點亮跨世代的善終與善願，讓生命勇敢發光。</h3>
                        </div>
                        <div class="photo web">
                            <div class="item">
                                <a href="https://afl.org.tw/news/298" target="_blank">
                                    <img src="{{ asset('frontend/images/home_dance_01.jpg') }}">
                                </a>
                            </div>
                            <div class="item">
                                <a href="https://afl.org.tw/news/298" target="_blank">
                                    <img src="{{ asset('frontend/images/home_dance_02.jpg') }}">
                                </a>
                            </div>
                            <div class="item">
                                <a href="https://afl.org.tw/news/298" target="_blank">
                                    <img src="{{ asset('frontend/images/home_dance_03.jpg') }}">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col02" data-aos="fade-left" data-aos-duration="500" data-aos-delay="300">
                    <div class="article">
                        <a href="https://afl.org.tw/news/298" target="_blank">
                            <div class="img">
                                <img src="{{ asset('frontend/images/home_article_01.jpg') }}">
                            </div>
                            <div class="info">
                                <div class="more">
                                    <span>瞭解更多<i></i></span>
                                </div>
                                <div class="describe">
                                    <h3>因為愛，好好說<br />成為守護生命的力量</h3>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="photo mobile">
                        <div class="item">
                            <a href="https://afl.org.tw/news/298" target="_blank">
                                <img src="{{ asset('frontend/images/home_dance_01.jpg') }}">
                            </a>
                        </div>
                        <div class="item">
                            <a href="https://afl.org.tw/news/298" target="_blank">
                                <img src="{{ asset('frontend/images/home_dance_02.jpg') }}">
                            </a>
                        </div>
                        <div class="item">
                            <a href="https://afl.org.tw/news/298" target="_blank">
                                <img src="{{ asset('frontend/images/home_dance_03.jpg') }}">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 攜手合作區塊 --}}
    <div class="block-div block-cooperate">
        <div class="block-outer">
            <div class="cooperate-links-list">
                <div class="item item01" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                    <div class="already">攜手合作</div>
                    <div class="total"><b>28</b>間</div>
                    <div class="describe">ACP綠色通道醫院</div>
                </div>
                <div class="item item02" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
                    <div class="already">全國累計</div>
                    <div class="total"><b>127,348</b>人</div>
                    <div class="describe">完成AD簽署</div>
                </div>
                <div class="item item03" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                    <div class="already">專業培訓</div>
                    <div class="total"><b>3,073</b>人</div>
                    <div class="describe">病主法核心講師</div>
                </div>
                <div class="item item04" data-aos="fade-up" data-aos-duration="500" data-aos-delay="400">
                    <div class="already">公益補助</div>
                    <div class="total"><b>3,103</b>人</div>
                    <div class="describe">弱勢家庭ACP補助</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 關於我們區塊 --}}
    <div class="block-div block-founder">
        <div class="block-outer">
            <div class="about-founder">
                <div class="two-cols">
                    <div class="col01" data-aos="fade-left" data-aos-duration="500" data-aos-delay="300">
                        <div class="img"><img src="{{ asset('frontend/images/boss.png') }}"></div>
                    </div>
                    <div class="col02" data-aos="fade-right" data-aos-duration="500">
                        <div class="title">
                            <h2>關於我們</h2>
                            <p>Far far away, behind the word mountains, far from the countries Vokalia andConsonantia, there live the blind texts.</p>
                        </div>
                        <div class="info">
                            <p>以更有效地面對重要且迫切的社會議題。</p>
                            <p>這個基金會是為每一位相信愛、承載愛與分享愛的人而建立的。我期待著與大家攜手並肩，為社會帶來更多溫暖與關懷。讓我們的共同努力成為愛的進行曲，讓這股力量在每一個角落綻放，讓每一顆心都不孤單。</p>
                            <p>讓我們一起，為了愛的理想，勇敢前行，攜手創造一個更美好的明天。</p>
                            <a href="https://afl.org.tw/about/vision" class="more" target="_blank">瞭解更多</a>
                        </div>
                        <div class="sign"><img src="{{ asset('frontend/images/sign.png') }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SDGs 區塊 --}}
    <div class="block-div block-sdgs">
        <div class="block-outer">
            <div class="title" data-aos="fade-up" data-aos-duration="500">
                <h2>我們的SDGs</h2>
                <p>Far far away, behind the word mountains, far from the countries Vokalia and</p>
            </div>
            <div class="sdgs">
                <div class="list-items">
                    <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                        <div class="box">
                            <div class="img">
                                <img src="{{ asset('frontend/images/icon_sdg_01.png') }}">
                            </div>
                            <div class="info">
                                <h3>健康與福祉</h3>
                                <p>促進身心靈整合的全人健康，支持不同生命階段與處境的健康福祉。</p>
                            </div>
                        </div>
                    </div>
                    <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
                        <div class="box">
                            <div class="img">
                                <img src="{{ asset('frontend/images/icon_sdg_02.png') }}">
                            </div>
                            <div class="info">
                                <h3>優質教育</h3>
                                <p>以生命教育深化自我覺察與價值選擇，強化面對人生議題的韌性。</p>
                            </div>
                        </div>
                    </div>
                    <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                        <div class="box">
                            <div class="img">
                                <img src="{{ asset('frontend/images/icon_sdg_03.png') }}">
                            </div>
                            <div class="info">
                                <h3>減少不平等</h3>
                                <p>關注不利處境族群需求，縮短健康、教育與照護資源差距。</p>
                            </div>
                        </div>
                    </div>
                    <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="400">
                        <div class="box">
                            <div class="img">
                                <img src="{{ asset('frontend/images/icon_sdg_04.png') }}">
                            </div>
                            <div class="info">
                                <h3>和平正義及健全制度</h3>
                                <p>推動以人為本的制度與公共信任，保障尊嚴、選擇與基本權利。</p>
                            </div>
                        </div>
                    </div>
                    <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="500">
                        <div class="box">
                            <div class="img">
                                <img src="{{ asset('frontend/images/icon_sdg_05.png') }}">
                            </div>
                            <div class="info">
                                <h3>多元夥伴關係</h3>
                                <p>建立政府、醫療、教育與社福夥伴等多元夥伴關係，協力推動善生善終善願社會網絡。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 最新消息區塊 (動態) --}}
    <div class="block-div block-news">
        <div class="block-outer">
            <div class="title" data-aos="fade-up" data-aos-duration="500">
                <h2>最新消息</h2>
                <p>Far far away, behind the word mountains, far from the countries Vokalia and</p>
                <a href="{{ route('afl.news.index') }}" class="more">瞭解更多</a>
            </div>
            <div class="news-swiper" data-aos="fade-up" data-aos-duration="500">
                <div class="swiper swiperNews">
                    <div class="swiper-wrapper">
                        @forelse($homepageNews as $news)
                        <div class="swiper-slide">
                            <div class="news">
                                <a href="{{ route('afl.news.show', $news->id) }}">
                                    <div class="img">
                                        @if($news->image && $news->image->path)
                                            <img src="{{ asset($news->image->path) }}">
                                        @else
                                            <img src="{{ asset('frontend/images/news_img_0' . (($loop->index % 3) + 1) . '.jpg') }}">
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
                                            <h3>{{ $news->getTranslation('title', 'zh_TW') }}</h3>
                                        </div>
                                        <div class="describe">
                                            <p>{{ Str::limit(strip_tags($news->getTranslation('content', 'zh_TW')), 60) }}</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        @empty
                        <div class="swiper-slide">
                            <div class="news">
                                <div class="info">
                                    <p>目前沒有最新消息</p>
                                </div>
                            </div>
                        </div>
                        @endforelse
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </div>

    {{-- 為愛發光區塊 --}}
    <div class="block-div block-love-shine" data-aos="fade-up" data-aos-duration="500">
        <div class="block-outer">
            <div class="love-shine">
                <div class="img" style="background-image: url({{ asset('frontend/images/home_love_bg.jpg') }});">
                    <img src="{{ asset('frontend/images/home_love_bg.jpg') }}">
                </div>
                <div class="info">
                    <div class="two-cols">
                        <div class="col01">
                            <h3>為愛發光，<br />邀您一起照亮最需要的地方</h3>
                        </div>
                        <div class="col02">
                            <p>我們補助弱勢族群ACP 諮商、建構綠色通道醫院、培訓病主法專業講師、推動病主法制度改革、創新全人健康與幸福促進方案、培力照顧者的靈性健康。</p>
                            <p>您的支持，將讓理解與選擇，成為照亮生命的光。</p>
                            <a href="https://afl.org.tw/civicrm/contribute/transact?reset=1&id=2" class="more" target="_blank">請支持我們</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 影響力成果區塊 --}}
    <div class="block-div block-influence">
        <div class="block-outer">
            <div class="title" data-aos="fade-up" data-aos-duration="500">
                <h2>影響力成果</h2>
                <p>Far far away, behind the word mountains, far from the countries Vokalia and</p>
                <a href="https://parc.tw/story/special" class="more" target="_blank">瞭解更多</a>
            </div>
            <div class="influence-swiper" data-aos="fade-up" data-aos-duration="500">
                <div class="swiper swiperInfluence">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="story">
                                <a href="https://afl.org.tw/news/290" target="_blank">
                                    <div class="img">
                                        <img src="{{ asset('frontend/images/home_img_01.jpg') }}">
                                    </div>
                                    <div class="info">
                                        <div class="datetime">2025-09-18</div>
                                        <div class="tag">
                                            <span class="pink01">最新動態</span>
                                        </div>
                                        <div class="sdgs">
                                            <img src="{{ asset('frontend/images/icon_sdg_01.png') }}">
                                            <img src="{{ asset('frontend/images/icon_sdg_04.png') }}">
                                        </div>
                                        <div class="headline">
                                            <h3>一份支持，一份希望 —病主法落實，需要你的行動</h3>
                                        </div>
                                        <div class="describe">
                                            <p>《病人自主權利法》修法懶人包正式發布，為愛前行基金會自創立以來，持續投入《病人自主權利法》的倡議與修...</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="story">
                                <a href="https://afl.org.tw/news/288" target="_blank">
                                    <div class="img">
                                        <img src="{{ asset('frontend/images/home_img_02.jpg') }}">
                                    </div>
                                    <div class="info">
                                        <div class="datetime">2025-09-08</div>
                                        <div class="tag">
                                            <span class="pink01">最新動態</span>
                                        </div>
                                        <div class="sdgs">
                                            <img src="{{ asset('frontend/images/icon_sdg_01.png') }}">
                                            <img src="{{ asset('frontend/images/icon_sdg_04.png') }}">
                                        </div>
                                        <div class="headline">
                                            <h3>病主法修法完成初審，但 AD 執行困境未解——為愛前行基金會</h3>
                                        </div>
                                        <div class="describe">
                                            <p>修法只是第一步，座談揭示三大挑戰：啟動困難、資訊落差、家屬阻力《病人自主權利法》修正案近日於立法院完...</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="story">
                                <a href="https://afl.org.tw/news/285" target="_blank">
                                    <div class="img">
                                        <img src="{{ asset('frontend/images/home_img_03.jpg') }}">
                                    </div>
                                    <div class="info">
                                        <div class="datetime">2025-08-12</div>
                                        <div class="tag">
                                            <span class="pink01">最新動態</span>
                                        </div>
                                        <div class="sdgs">
                                            <img src="{{ asset('frontend/images/icon_sdg_01.png') }}">
                                            <img src="{{ asset('frontend/images/icon_sdg_04.png') }}">
                                        </div>
                                        <div class="headline">
                                            <h3>為愛啟程：用愛與智慧描繪生命自主的航程</h3>
                                        </div>
                                        <div class="describe">
                                            <p>8 月 10 日上午，「為愛啟程 2025 病主法宣導講座」成功展開了一場深具意義的生命對話。誠摯感謝每一位到場...</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </div>

    {{-- 病人自主權利法區塊 --}}
    <div class="block-div block-autonomy-law">
        <div class="block-outer">
            <div class="title" data-aos="fade-up" data-aos-duration="500">
                <h2>病人自主權利法</h2>
                <p>Far far away, behind the word mountains, far from the countries Vokalia and</p>
                <a href="https://parc.tw/" class="more" target="_blank">瞭解更多</a>
            </div>
            <div class="autonomy-law-list">
                <div class="item" data-aos="fade-up" data-aos-duration="500">
                    <a href="https://parc.tw/law/policy/meeting" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/icon_law_01.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>政策研究</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                    <a href="https://parc.tw/love-event" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/icon_law_02.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>弱勢補助申請</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
                    <a href="https://parc.tw/event/video/ExpertCourses" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/icon_law_03.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>病主課程系列</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                    <a href="https://parc.tw/event/cert" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/icon_law_04.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>時數認證申請</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="400">
                    <a href="https://parc.tw/event/lohas/ACP" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/icon_law_05.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>免費資源下載</h3>
                        </div>
                    </a>
                </div>
            </div>
            {{-- 手機版 Swiper --}}
            <div class="autonomy-swiper">
                <div class="swiper swiperAutonomy">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="autonomy">
                                <a href="https://parc.tw/law/policy/meeting" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/icon_law_01.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>政策研究</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="autonomy">
                                <a href="https://parc.tw/love-event" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/icon_law_02.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>弱勢補助申請</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="autonomy">
                                <a href="https://parc.tw/event/video/ExpertCourses" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/icon_law_03.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>病主課程系列</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="autonomy">
                                <a href="https://parc.tw/event/cert" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/icon_law_04.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>時數認證申請</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="autonomy">
                                <a href="https://parc.tw/event/lohas/ACP" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/icon_law_05.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>免費資源下載</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 全人健康與幸福區塊 --}}
    <div class="block-div block-health">
        <div class="block-outer">
            <div class="title" data-aos="fade-up" data-aos-duration="500">
                <h2>全人健康與幸福</h2>
                <p>Far far away, behind the word mountains, far from the countries Vokalia and</p>
            </div>
            <div class="health-list">
                <div class="item" data-aos="fade-up" data-aos-duration="500">
                    <a href="https://www.canva.com/design/DAG8_L40RnA/IL6WY7RGzYnP0gawn0S89w/edit?utm_content=DAG8_L40RnA&utm_campaign=designshare&utm_medium=link2&utm_source=sharebutton" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/health_circle_01.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>全人研究計劃</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                    <a href="https://afl.vjinc.biz/basic" target="_blank">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/health_circle_02.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>全人線上檢測</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
                    <a href="javascript:;">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/health_circle_03.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>全人教育訓練</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                    <a href="javascript:;">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/health_circle_04.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>全人社會處方</h3>
                        </div>
                    </a>
                </div>
                <div class="item" data-aos="fade-up" data-aos-duration="500" data-aos-delay="400">
                    <a href="javascript:;">
                        <div class="img">
                            <span><img src="{{ asset('frontend/images/health_circle_05.jpg') }}"></span>
                        </div>
                        <div class="describe">
                            <h3>全人陪伴服務</h3>
                        </div>
                    </a>
                </div>
            </div>
            {{-- 手機版 Swiper --}}
            <div class="health-swiper">
                <div class="swiper swiperHealth">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="health">
                                <a href="https://www.canva.com/design/DAG8_L40RnA/IL6WY7RGzYnP0gawn0S89w/edit?utm_content=DAG8_L40RnA&utm_campaign=designshare&utm_medium=link2&utm_source=sharebutton" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/health_circle_01.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>全人研究計劃</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="health">
                                <a href="https://afl.vjinc.biz/basic" target="_blank">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/health_circle_02.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>全人線上檢測</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="health">
                                <a href="javascript:;">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/health_circle_03.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>全人教育訓練</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="health">
                                <a href="javascript:;">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/health_circle_04.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>全人社會處方</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="swiper-slide">
                            <div class="health">
                                <a href="javascript:;">
                                    <div class="img">
                                        <span><img src="{{ asset('frontend/images/health_circle_05.jpg') }}"></span>
                                    </div>
                                    <div class="describe">
                                        <h3>全人陪伴服務</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 支持我們區塊 --}}
    <div class="block-div block-love" data-aos="fade-up" data-aos-duration="500">
        <div class="img" style="background-image: url({{ asset('frontend/images/love001.jpg') }});">
            <img src="{{ asset('frontend/images/love001.jpg') }}">
        </div>
        <div class="info">
            <h2>用愛，預約我們的圓滿人生</h2>
            <a href="https://afl.org.tw/civicrm/contribute/transact?reset=1&id=2" target="_blank">支持我們<i></i></a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
jQuery(document).ready(function($) {
    // 初始化首頁 Swiper
    if ($('.swiperHome').length) {
        new Swiper('.swiperHome', {
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiperHome .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiperHome .swiper-button-next',
                prevEl: '.swiperHome .swiper-button-prev',
            },
        });
    }

    // 初始化最新消息 Swiper
    if ($('.swiperNews').length) {
        new Swiper('.swiperNews', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiperNews .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.block-news .swiper-button-next',
                prevEl: '.block-news .swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 30,
                },
            },
        });
    }

    // 初始化影響力成果 Swiper
    if ($('.swiperInfluence').length) {
        new Swiper('.swiperInfluence', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiperInfluence .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.block-influence .swiper-button-next',
                prevEl: '.block-influence .swiper-button-prev',
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                1024: {
                    slidesPerView: 3,
                    spaceBetween: 30,
                },
            },
        });
    }

    // 初始化病人自主權利法 Swiper (手機版)
    if ($('.swiperAutonomy').length) {
        new Swiper('.swiperAutonomy', {
            slidesPerView: 2,
            spaceBetween: 15,
            loop: true,
            pagination: {
                el: '.swiperAutonomy .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                480: {
                    slidesPerView: 3,
                    spaceBetween: 15,
                },
            },
        });
    }

    // 初始化全人健康與幸福 Swiper (手機版)
    if ($('.swiperHealth').length) {
        new Swiper('.swiperHealth', {
            slidesPerView: 2,
            spaceBetween: 15,
            loop: true,
            pagination: {
                el: '.swiperHealth .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                480: {
                    slidesPerView: 3,
                    spaceBetween: 15,
                },
            },
        });
    }
});
</script>
@endpush
