@extends('frontend.layouts.app')

@section('title', __('frontend.password_reset.forgot_title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))

@section('content')
    <section class="section-member">            
        <div class="block-div block-01">
            <div class="block-outer">
                
                <div class="member-div">
                    <div class="boxer">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.password_reset.forgot_title') }}</h1>
                            </div>                         
                        </div>
                        <div class="remind">
                            <p>{{ __('frontend.password_reset.email_label') }}</p>
                        </div>
                        
                        <!-- Vue 組件掛載點 -->
                        <forgot-password-form :texts="{
                            email: '{{ __('frontend.form.email') }}',
                            placeholderEmail: '{{ __('frontend.form.placeholder_email') }}',
                            cancel: '{{ __('frontend.btn.cancel') }}',
                            confirm: '{{ __('frontend.btn.submit') }}',
                            processing: '{{ __('frontend.member.sending_email') }}',
                            sending: '{{ __('frontend.member.sending_email') }}',
                            sendFailed: '{{ __('frontend.password_reset.send_failed') }}',
                            checkFormFields: '{{ __('frontend.form.check_form_fields') }}',
                            validationRequired: '{{ __('frontend.form.validation_required') }}',
                            validationEmail: '{{ __('frontend.form.validation_email') }}'
                        }"></forgot-password-form>
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