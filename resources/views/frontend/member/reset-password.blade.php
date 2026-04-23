@extends('frontend.layouts.app')

@section('title', __('frontend.password_reset.title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))

@section('content')
    <section class="section-member">            
        <div class="block-div block-01">
            <div class="block-outer">
                
                <div class="member-div">
                    <div class="boxer">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.password_reset.title') }}</h1>
                            </div>                         
                        </div>
                        <div class="remind">
                            <p>{{ __('frontend.password_reset.new_password') }}</p>
                        </div>
                        
                        <!-- Vue 組件掛載點 -->
                        <reset-password-form 
                            token="{{ $token }}" 
                            email="{{ $email }}"
                            :texts="{
                                newPassword: '{{ __('frontend.password_reset.new_password') }}',
                                confirmPassword: '{{ __('frontend.password_reset.confirm_password') }}',
                                placeholderNewPassword: '{{ __('frontend.form.placeholder_password_rule') }}',
                                placeholderConfirmPassword: '{{ __('frontend.form.placeholder_confirm_password') }}',
                                cancel: '{{ __('frontend.btn.cancel') }}',
                                resetBtn: '{{ __('frontend.password_reset.reset_button') }}',
                                resetProcessing: '{{ __('frontend.member.update_processing') }}',
                                confirmTitle: '{{ __('frontend.password_reset.confirm_title') }}',
                                loadingMessage: '{{ __('frontend.password_reset.loading_message') }}',
                                checkFormFields: '{{ __('frontend.form.check_form_fields') }}',
                                tooManyRequests: '{{ __('frontend.password_reset.too_many_requests') }}',
                                resetFailed: '{{ __('frontend.password_reset.reset_failed') }}',
                                checkNetwork: '{{ __('frontend.password_reset.check_network') }}',
                                validationRequired: '{{ __('frontend.form.validation_required') }}',
                                validationPasswordFormat: '{{ __('frontend.form.validation_password_format') }}',
                                validationConfirmRequired: '{{ __('frontend.form.validation_confirm_required') }}',
                                validationPasswordMatch: '{{ __('frontend.form.validation_password_match') }}',
                                required: '{{ __('frontend.form.required') }}',
                                gotoLogin: '{{ __('frontend.password_reset.goto_login') }}',
                                linkExpiredTitle: '{{ __('frontend.password_reset.link_expired_title') }}',
                                retryForgotPassword: '{{ __('frontend.password_reset.retry_forgot_password') }}',
                                backToLogin: '{{ __('frontend.password_reset.back_to_login') }}'
                            }"
                        ></reset-password-form>
                    </div>    
                </div>
            </div>
        </div>                       
    </section>
@endsection

@push('scripts')
<script>
    // 任何頁面特定的 JS
</script>
@endpush