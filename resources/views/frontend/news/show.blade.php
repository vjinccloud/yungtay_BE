@extends('frontend.layouts.app')

@section('title', $news['title'] . ' - ' . $siteInfo['title'])
@section('description', Str::limit(strip_tags($news['content']), 160))
@section('keywords', $siteInfo['title'] . ', ' . __('frontend.nav.latest_news') . ', ' . $news['title'])
@section('og_image', asset($news['image']))

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('news', $news) !!}
@endpush

@section('content')
<section class="section-latest-news-detail">    
    <div class="breadcrumb-div">
        <div class="breadcrumb">                
            <i></i>
            <span></span>
            <a href="{{ route('news') }}">{{ __('frontend.nav.latest_news') }}</a>
            <span></span>
            {{ $news['title'] }}      
        </div>                 
    </div>         
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="datetime-back">
                <div class="back"><a href="{{ route('news') }}"><i></i>{{ __('frontend.btn.back') }}</a></div>
                <div class="datetime">{{ $news['publish_date'] }}</div>                        
            </div>    
            <div class="block-title">
                <div class="sub-title">
                    <h2>{{ $news['title'] }}</h2>
                </div>                         
            </div>     
            <div class="news-detail-div">  
                {{-- 輸出富文本內容，保留 HTML 格式 --}}
                {!! $news['content'] !!}
            </div>
               
        </div>
    </div>                       
</section>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function($) {
        // AOS 動畫初始化
        AOS.init();
    });
</script>
@endpush