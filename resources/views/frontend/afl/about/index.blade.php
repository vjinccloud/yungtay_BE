{{-- 創辦人的話頁面 --}}
@extends('frontend.layouts.afl')

@section('title', '創辦人的話 - ' . ($siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會'))
@section('main-class', 'main-about-us')

@section('content')
<section class="section-about-us">
    {{-- 創辦人的話區塊 --}}
    <div class="block-div block-about-words">
        <div class="img">
            <img src="{{ asset('frontend/images/about_img_01.jpg') }}">
        </div>
        <div class="block-breadcrumb">
            <div class="block-outer">
                <div class="breadcrumb">
                    <a href="{{ route('afl.home') }}">首頁</a>
                    <i>/</i>
                    創辦人的話
                </div>
            </div>
        </div>
        <div class="info01" data-aos="fade-right" data-aos-duration="500" data-aos-delay="400">
            <div class="box">
                <div class="width">
                    <h1>創辦人的話</h1>
                    <p>這個基金會是為每一位相信愛、承載愛與分享愛的人而建立的。<br />我期待著與大家攜手並肩，為社會帶來更多溫暖與關懷。</p>
                </div>
            </div>
        </div>
        <div class="info02" data-aos="fade-left" data-aos-duration="500" data-aos-delay="100">
            <div class="box">
                <div class="width">
                    <div class="deco"><img src="{{ asset('frontend/images/deco_02.png') }}"></div>
                    <div class="describe">
                        <p>我由衷地想與大家分享，創辦這個基金會是我生命旅程中的一個重要時刻，源自於無數社會公益前輩的鼓勵與啟發。在這個瞬息萬變的時代，我們必須凝聚力量，在愛的核心價值上，提供具體的創新服務，以更有效地面對重要且迫切的社會議題。</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 關於創辦人介紹區塊 --}}
    <div class="block-div block-about-introduce">
        <div class="block-outer">
            <div class="about-founder">
                <div class="two-cols">
                    <div class="col01" data-aos="fade-left" data-aos-duration="500" data-aos-delay="300">
                        <div class="img"><img src="{{ asset('frontend/images/boss.png') }}"></div>
                    </div>
                    <div class="col02" data-aos="fade-right" data-aos-duration="500">
                        <div class="about-title"><h2>身心靈<br />都值得被溫柔對待</h2></div>
                        <div class="info">
                            <p>作為一名罕見疾病的重症病人，在過去的20多年裡，我一直在第一線服務那些與我有著相似經歷的病友們。在這段漫長的旅程中，我深深體會到生命的脆弱與可貴。</p>
                            <p>曾經，我有幸在立法委員的職位上<span class="pink">推動《病人自主權利法》，這是亞洲首部完整保障病人醫療自主權的法律</span>。每當回憶起自己在立法過程中與許多專家夥伴和病友團體的共同努力，我總是心懷感激與幸福。這不僅是對自己的期許，更是對所有仍在奮鬥的病人及其家屬的支持力量。</p>
                            <p>身為病人、重症家屬、社會工作者及政策制定者，我結合了多重身份的視角，感受到生命的渺小與偉大。</p>
                            <p>在困難面前，<span class="pink">愛是我們最大的力量</span>。對於那些正在經歷苦痛的家庭，我希望這個基金會能成為他們堅實的後盾，讓他們在不確定的生活中依然感受到溫暖、希望與支持。即使面對病痛與死亡，也能看到生命的<span class="pink">另一種絢麗，讓愛與記憶在心中永駐</span>。</p>
                        </div>
                        <div class="sign"><img src="{{ asset('frontend/images/sign.png') }}"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 為愛前行團隊區塊 --}}
    <div class="block-div block-about-team">
        <div class="block-outer">
            <div class="about-team">
                <div class="team-animate">
                    <div class="about-title about-title01" data-aos="fade-up" data-aos-duration="500">
                        <h2>為愛前行</h2>
                        <p>以更有效地面對重要且迫切</p>
                    </div>
                    <div class="move-1" data-aos="fade-right-wide" data-aos-duration="2000">
                        <img src="{{ asset('frontend/images/about_move_01.svg') }}">
                    </div>
                    <div class="move-2" data-aos="fade-left-wide" data-aos-duration="2000">
                        <img src="{{ asset('frontend/images/about_move_01.svg') }}">
                    </div>
                    <div class="about-title about-title02" data-aos="fade-up" data-aos-duration="500">
                        <p>以更有效地面對重要且迫切</p>
                        <h2>我們相伴</h2>
                    </div>
                </div>

                <div class="staff">
                    <div class="img01" data-aos="fade-right" data-aos-duration="500" data-aos-delay="300"><img src="{{ asset('frontend/images/about_team_01.png') }}"></div>
                    <div class="img02" data-aos="fade-right" data-aos-duration="500" data-aos-delay="100"><img src="{{ asset('frontend/images/about_team_02.png') }}"></div>
                    <div class="arrow"><img src="{{ asset('frontend/images/about_icon_arrow_down_pink.svg') }}"></div>
                </div>
            </div>
            <div class="about-info" data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
                <p>
                    <span>這個基金會是為每一位<b>相信愛、承載愛與分享愛的人而建立的。</b></span>
                    <span>我期待著與大家攜手並肩，為社會帶來更多溫暖與關懷。</span>
                    <span>讓我們的共同努力成為愛的進行曲，讓這股力量在每一個角落綻放</span>
                </p>
                <p>讓每一顆心都不孤單。讓我們一起，<b>為了愛的理想，勇敢前行，攜手創造一個更美好的明天。</b></p>
            </div>
        </div>
    </div>

    {{-- 關於我們圖片區塊 --}}
    <div class="block-div block-about-photo">
        <img src="{{ asset('frontend/images/about_img_02.jpg') }}" data-aos="fade-up" data-aos-duration="500">
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
