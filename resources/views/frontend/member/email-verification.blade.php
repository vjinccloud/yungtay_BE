@extends('frontend.layouts.app')

@section('title', __('frontend.email_verify.title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ __('frontend.email_verify.title') }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<email-verification 
        @auth
        user-email="{{ Auth::user()->email }}"
        @else
        user-email=""
        @endauth
        :texts="{
            waitingTitle: '{{ __('frontend.email_verify.waiting_title') }}',
            waitingMessage: '{{ __('frontend.email_verify.waiting_message') }}',
            verificationSent: '{{ __('frontend.email_verify.verification_sent') }}',
            notLoggedIn: '⚠️ {{ __('frontend.member.not_logged_in') }}',
            backHome: '{{ __('frontend.btn.back_home') }}',
            resendProcessing: '{{ __('frontend.member.resend_processing') }}',
            resendEmail: '{{ __('frontend.email_verify.resend_email') }}',
            resendEmailCountdown: '{{ __('frontend.email_verify.resend_email_countdown') }}',
            resendLimit: '{{ __('frontend.email_verify.resend_limit') }}'
        }"
    ></email-verification>
@endsection

