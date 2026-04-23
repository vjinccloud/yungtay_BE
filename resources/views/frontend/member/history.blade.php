@extends('frontend.layouts.app')

@section('title', __('frontend.member.history') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ __('frontend.member.history') }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<section class="section-member section-member-detail">            
    <div class="block-div block-01">
        <div class="block-outer">                    
            <div class="member-div">
                <div class="member-center">
                    @include('frontend.member.partials.sidebar', ['currentPage' => 'history'])
                    
                    <div class="member-content member-navi-use-sticky">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.member.history') }}</h1>
                            </div>                         
                        </div>
                        <div class="tab-list">
                            <div class="tab-links-outer">
                                <div class="links">
                                    <a href="#" class="tab-link active" data-type="article">{{ __('frontend.nav.news') }}</a>
                                    <a href="#" class="tab-link" data-type="drama">{{ __('frontend.nav.drama') }}</a>
                                    <a href="#" class="tab-link" data-type="program">{{ __('frontend.nav.program') }}</a>
                                    <a href="#" class="tab-link" data-type="live">{{ __('frontend.nav.live') }}</a>
                                    <a href="#" class="tab-link" data-type="radio">{{ __('frontend.nav.radio') }}</a>
                                </div>    
                            </div>
                            <div class="tab-filter">
                                <div class="dropdown-select" data-id="dropdownTime">
                                    <button type="button"><span><b>{{ __('frontend.filter.all_time') }}</b></span><i></i></button>
                                    <div class="sub-menu">
                                        <div class="sub-item">
                                            <input type="radio" name="filterDatetime" data-time-range="all" checked>
                                            <span>{{ __('frontend.filter.all_time') }}</span>
                                        </div>
                                        <div class="sub-item">
                                            <input type="radio" name="filterDatetime" data-time-range="one_month">
                                            <span>{{ __('frontend.filter.one_month') }}</span>
                                        </div>
                                        <div class="sub-item">
                                            <input type="radio" name="filterDatetime" data-time-range="three_months">
                                            <span>{{ __('frontend.filter.three_months') }}</span>
                                        </div>
                                        <div class="sub-item">
                                            <input type="radio" name="filterDatetime" data-time-range="six_months">
                                            <span>{{ __('frontend.filter.six_months') }}</span>
                                        </div>
                                        <div class="sub-item">
                                            <input type="radio" name="filterDatetime" data-time-range="one_year">
                                            <span>{{ __('frontend.filter.one_year') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>    
                            <div class="tab-content" style="min-height: 500px;">
                                <member-view-history
                                    initial-type="article"
                                    initial-time-range="all"
                                    :texts="{
                                        no_history: '{{ __('frontend.member.no_history') }}',
                                        live_now: '{{ __('frontend.live.live_now') }}',
                                        loading: '{{ __('frontend.loading') }}',
                                        time: {
                                            just_now: '{{ __('frontend.time.just_now') }}',
                                            minutes_ago: '{{ __('frontend.time.minutes_ago') }}',
                                            hours_ago: '{{ __('frontend.time.hours_ago') }}',
                                            yesterday: '{{ __('frontend.time.yesterday') }}',
                                            days_ago: '{{ __('frontend.time.days_ago') }}',
                                            weeks_ago: '{{ __('frontend.time.weeks_ago') }}',
                                            months_ago: '{{ __('frontend.time.months_ago') }}'
                                        }
                                    }">
                                </member-view-history>
                            </div>        
                        </div>
                    </div>        
                </div>                       
            </div>
        </div>
    </div>                       
</section>
@endsection

@section('popups')
@include('frontend.member.partials.popups')
@endsection

@push('scripts')
<script>
jQuery(document).ready(function($){
    AOS.init();
    
    // 會員紅利彈窗
    $('[data-popup-id]').on('click', function() {
        var popupId = $(this).data('popup-id');
        $('#' + popupId).addClass('active').fadeIn();
    });
    
    $('[data-close-id]').on('click', function() {
        var popupId = $(this).data('close-id');
        $('#' + popupId).removeClass('active').fadeOut();
    });
    
    // 時間篩選功能完全交由 Vue 組件處理，移除 jQuery 邏輯避免衝突
}); 
</script>
@endpush