@extends('frontend.layouts.app')

@section('title', __('frontend.password_reset.link_sent') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))

@section('content')
    <section class="section-member">            
        <div class="block-div block-01">
            <div class="block-outer">
                
                <div class="member-div">
                    <div class="boxer">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.password_reset.link_sent') }}</h1>
                            </div>                         
                        </div>
                        <div class="remind">
                            <p>{{ __('frontend.password_reset.check_within_hour') }}</p>
                            <p>{{ __('frontend.password_reset.check_email_message') }}</p>
                        </div>
                        
                        <div class="form-keyin-div">                               
                            <div class="item action">
                                <a href="{{ route('home') }}" class="btn-outline">{{ __('frontend.btn.back_home') }}</a>
                            </div>
                        </div>        
                    </div>    
                </div>
            </div>
        </div>                       
    </section>
@endsection

@push('styles')
<style>
.btn-outline {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #fff;
    border-radius: 100px;
    color: #fff;
    text-decoration: none;
    padding: 10px 20px;
    text-align: center;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    transition: all 0.3s;
    outline: none;
}

.btn-outline:hover {
    background-color: #2CC0E2;
    border: 1px solid #2CC0E2;
    color: #fff;
    text-decoration: none;
}

.btn-outline:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(44, 192, 226, 0.3); /* 自訂焦點樣式 */
}

.btn-outline:active {
    transform: translateY(1px); /* 點擊反饋 */
}

.highlight {
    color: #2CC0E2;
    font-weight: bold;
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