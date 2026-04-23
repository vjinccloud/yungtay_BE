@extends('frontend.layouts.app')

@section('title', __('frontend.member.collection') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ __('frontend.member.collection') }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<section class="section-member section-member-detail">            
    <div class="block-div block-01">
        <div class="block-outer">                    
            <div class="member-div">
                <div class="member-center">
                    @include('frontend.member.partials.sidebar', ['currentPage' => 'collection'])
                    
                    <div class="member-content member-navi-use-sticky">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.member.collection') }}</h1>
                            </div>                         
                        </div>
                        <div class="tab-list">
                            <div class="tab-links-outer">
                                <div class="links">
                                    <a href="#" data-type="articles" class="active">{{ __('frontend.nav.news') }}（{{ $typeCounts['articles'] ?? 0 }}）</a>
                                    <a href="#" data-type="drama">{{ __('frontend.nav.drama') }}（{{ $typeCounts['drama'] ?? 0 }}）</a>
                                    <a href="#" data-type="program">{{ __('frontend.nav.program') }}（{{ $typeCounts['program'] ?? 0 }}）</a>
                                    <a href="#" data-type="radio">{{ __('frontend.nav.radio') }}（{{ $typeCounts['radio'] ?? 0 }}）</a>
                                </div>    
                            </div>
                            <div class="tab-content" style="min-height: 500px;">
                                <!-- Vue 組件 -->
                                <member-collection-list 
                                    initial-type="articles"
                                    :texts="{
                                        no_collection: '{{ __('frontend.member.no_collection') }}',
                                        collect: '{{ __('frontend.btn.collect') }}',
                                        collected: '{{ __('frontend.btn.collected') }}',
                                        uncollect: '{{ __('frontend.btn.uncollect') }}'
                                    }">
                                </member-collection-list>
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
$(document).ready(function() {
    // 會員紅利彈窗
    $('[data-popup-id]').on('click', function() {
        var popupId = $(this).data('popup-id');
        $('#' + popupId).addClass('active').fadeIn();
    });
    
    $('[data-close-id]').on('click', function() {
        var popupId = $(this).data('close-id');
        $('#' + popupId).removeClass('active').fadeOut();
    });
});
</script>
@endpush