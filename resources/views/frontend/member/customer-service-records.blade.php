@extends('frontend.layouts.app')

@section('title', $pageTitle . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ $pageTitle }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<section class="section-member section-member-detail">
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="member-div">
                <div class="member-center">
                    @include('frontend.member.partials.sidebar', ['currentPage' => 'customer-service-records'])

                    <div class="member-content member-navi-use-sticky">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.member.customer_service_records') }}</h1>
                            </div>
                        </div>
                        <!-- Vue 組件掛載點 -->
                        <member-customer-service-records
                            :texts="{
                                subject: '{{ __('frontend.customer_service.subject') }}',
                                message: '{{ __('frontend.customer_service.message') }}',
                                date: '{{ __('frontend.form.date') }}',
                                no_records: '{{ __('frontend.member.no_customer_service_records') }}'
                            }"
                        ></member-customer-service-records>
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
});
</script>
@endpush