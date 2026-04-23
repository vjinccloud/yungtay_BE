{{-- 使命與願景頁面 --}}
@extends('frontend.layouts.afl')

@section('title', '使命與願景 - ' . ($siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會'))
@section('main-class', 'main-mission-vision')

@section('content')
<section class="section-mission-vision">
    {{-- 麵包屑 --}}
    <div class="block-breadcrumb">
        <div class="block-outer">
            <div class="breadcrumb">
                <a href="{{ route('afl.home') }}">首頁</a>
                <i>/</i>
                使命與願景
            </div>
        </div>
    </div>

    {{-- 使命與願景標題區塊 --}}
    <div class="block-div block-mission-vision">
        <div class="mission-title" data-aos="fade-up" data-aos-duration="500">
            <h2>使命與願景</h2>
        </div>
        <div class="mission-info" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300" id="anchor0">
            <p>用愛，預約我們的圓滿人生</p>
            <a href="#anchor1">開始見證，為愛啟程</a><br />
            <img src="{{ asset('frontend/images/icon_next_down_pink.svg') }}">
        </div>
        <div class="mission-deco">
            <img src="{{ asset('frontend/images/vision_deco_01.png') }}" class="flash">
        </div>
    </div>

    {{-- 介紹區塊 --}}
    <div class="block-div block-mission-introduce" id="anchor1">
        <div class="block-outer">
            <div class="introduce-info">
                <div class="two-cols">
                    <div class="col01">
                        <div class="chat-box" data-aos="fade-up" data-aos-duration="500">
                            <div class="describe">
                                財團法人新北市為愛前行社會福利基金會（簡稱：為愛前行基金會）成立於 2023 年，與「病人自主研究中心」共同推動《病人自主權利法》，透過四大業務：政策研究、教育訓練、弱勢服務、大眾宣導，持續推動病人自主權、生命教育及社會公平，為多元族群提供支持與資源。
                            </div>
                            <div class="corner">
                                <img src="{{ asset('frontend/images/mission_chat_corner.svg') }}">
                            </div>
                        </div>
                    </div>
                    <div class="col02">
                        <div class="animate">
                            <div class="move move-1">
                                <img src="{{ asset('frontend/images/vision_icon_01.svg') }}">
                            </div>
                            <div class="move move-2">
                                <img src="{{ asset('frontend/images/vision_icon_02.svg') }}">
                            </div>
                            <div class="move move-3">
                                <img src="{{ asset('frontend/images/vision_women_01.svg') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="introduce-animate">
                <div class="move">
                    <img src="{{ asset('frontend/images/vision_icon_03.svg') }}" class="img01" data-aos="fade-right" data-aos-duration="500" data-aos-delay="100">
                    <img src="{{ asset('frontend/images/vision_man_01.svg') }}" class="img02" data-aos="fade-right" data-aos-duration="500" data-aos-delay="600">
                </div>
            </div>
        </div>
    </div>

    {{-- 攜手合作區塊 --}}
    <div class="block-div block-mission-cooperate">
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

    {{-- 核心理念區塊 --}}
    <div class="block-div block-mission-core">
        <div class="block-outer">
            <div class="info" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                <p>為愛前行基金會以生命教育為核心，致力於跨世代生命議題的支持與關懷。我們深信，幸福不僅是長壽或富有，而是人的 <span class="pink">身、心、靈</span> 都能被溫柔對待，在生命的每個階段都充滿尊嚴與愛的陪伴。我們幫助所有人打造屬於自己的<span class="pink">善生、善終與善願</span>，希望每個人都能活得有意義、離開時舒坦，並懷抱利他精神將善願傳承給未來。</p>
                <p>為愛前行基金會期許自己成為溫暖的同行者，推動生命教育的普及與深化陪伴每個生命歡悅前行，創造一個跨世代共融的幸福社會，讓每個生命都能在愛中綻放獨特光芒。</p>
            </div>
            <div class="animate">
                <div class="move-1" data-aos="fade-right" data-aos-duration="500" data-aos-delay="100">
                    <img src="{{ asset('frontend/images/vision_women_02.svg') }}">
                </div>
                <div class="move-2" data-aos="fade-right" data-aos-duration="500" data-aos-delay="300">
                    <img src="{{ asset('frontend/images/vision_women_03.svg') }}">
                </div>
                <div class="move-3" data-aos="fade-right" data-aos-duration="500">
                    <img src="{{ asset('frontend/images/mission_deco_01.svg') }}">
                </div>
                <div class="move-4" data-aos="fade-right" data-aos-duration="500">
                    <img src="{{ asset('frontend/images/mission_deco_02.svg') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- 願景、使命、核心價值區塊 --}}
    <div class="block-div block-mission-describe">
        <div class="block-outer">
            <div class="sub-block" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                <h3>願景（Vision）</h3>
                <p>創造跨世代、多元、平等、共融的幸福社會，讓每個生命都能被理解、被尊重，綻放獨特的光芒。</p>
            </div>
            <div class="sub-block" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                <h3>使命（Mission）</h3>
                <p>集結人生智慧與跨域專業，打造公益行動平台，研發並實踐超高齡社會的全人解方。</p>
            </div>
            <div class="sub-block" data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
                <h3>核心價值（Core Values）</h3>
                <p>愛｜以終為始｜公益｜永續</p>
            </div>
        </div>
    </div>

    {{-- 核心價值詳細區塊 --}}
    <div class="block-div block-mission-love">
        <div class="block-outer">
            <div class="three-items">
                <div class="col01" data-aos="fade-right" data-aos-duration="500" data-aos-delay="100">
                    <div class="sub-block">
                        <h3>愛</h3>
                        <p>以同理與關懷為出發點，陪伴生命每個重要時刻</p>
                    </div>
                    <div class="sub-block">
                        <h3>以終為始</h3>
                        <p>從人生全程思考選擇，回應善生、善終與善願</p>
                    </div>
                </div>
                <div class="col02" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                    <div class="animate">
                        <div class="flash"><span></span></div>
                        <div class="img"><img src="{{ asset('frontend/images/vision_flash.png') }}"></div>
                    </div>
                </div>
                <div class="col03" data-aos="fade-left" data-aos-duration="500" data-aos-delay="500">
                    <div class="sub-block">
                        <h3>公益</h3>
                        <p>為多元族群創造可近、可及、可複製的支持</p>
                    </div>
                    <div class="sub-block">
                        <h3>永續</h3>
                        <p>建立長期影響力，讓善的行動得以持續擴散</p>
                    </div>
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
