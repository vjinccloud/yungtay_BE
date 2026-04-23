@extends('frontend.layouts.app')

@section('title', '404 - ' . __('frontend.error.404_title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))

@push('styles')
<link rel="stylesheet" href="{{ asset('frontend/css/error.css') }}">
@endpush

@section('content')
<section class="section-error-404">   
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="back"><a href="{{ url('/') }}"><i></i>{{ __('frontend.btn.back_home') }}</a></div>
            <div class="error">
                <h2>404</h2>
                <p>{{ __('frontend.error.404_message') }}</p>
            </div> 
        </div>       
    </div>    
</section>
@endsection