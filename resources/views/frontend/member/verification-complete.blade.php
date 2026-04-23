@extends('frontend.layouts.app')

@section('title', __('frontend.email_verify.complete_title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ __('frontend.email_verify.complete_title') }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<section class="section-member">            
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="member-div">
                <div class="boxer">
                    <div class="block-title">
                        <div class="big-title">
                            <h1>{{ __('frontend.email_verify.success') }}</h1>
                        </div>                         
                    </div>
                    <div class="remind">
                        <p>{{ __('frontend.email_verify.congrats') }}</p>
                        @auth
                        <p>{{ __('frontend.email_verify.welcome') }}，<strong>{{ auth()->user()->name }}</strong>！</p>
                        @endauth
                    </div>                                
                    <div class="verification-buttons">
                        <a href="{{ route('member.account') }}" class="btn-primary-custom">{{ __('frontend.email_verify.goto_center') }}</a>
                        <a href="{{ route('home') }}" class="btn-outline-custom">{{ __('frontend.email_verify.goto_home') }}</a>
                    </div>        
                </div>    
            </div>
        </div>
    </div>                       
</section>
@endsection

@push('styles')
<style>
.verification-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 30px;
    padding: 0 10px;
}

.btn-primary-custom,
.btn-outline-custom {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 100px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 400;
    transition: all 0.3s;
    white-space: nowrap;
    flex: 1;
    max-width: 150px;
}

.btn-primary-custom {
    background: linear-gradient(90deg, #2CC0E2 0%, #49D2BA 100%);
    border: none;
    color: #fff;
}

.btn-primary-custom:hover {
    background: linear-gradient(90deg, #49D2BA 0%, #2CC0E2 100%);
    color: #fff;
    text-decoration: none;
}

.btn-outline-custom {
    background: transparent;
    border: 1px solid #fff;
    color: #fff;
}

.btn-outline-custom:hover {
    background-color: #2CC0E2;
    border-color: #2CC0E2;
    color: #fff;
    text-decoration: none;
}

@media (max-width: 380px) {
    .btn-primary-custom,
    .btn-outline-custom {
        padding: 10px 15px;
        font-size: 13px;
    }
}
</style>
@endpush

@push('scripts')
<script>
jQuery(document).ready(function($) {
    AOS.init();
});
</script>
@endpush