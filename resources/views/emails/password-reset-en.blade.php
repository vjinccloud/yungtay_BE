@extends('emails.layouts.base')

@section('title', __('emails.password_reset.title') . ' - ' . $siteName)

@php
    $headerSubtitle = __('emails.password_reset.title');
    $footerLinks = [
        ['url' => url('/'), 'text' => __('emails.password_reset.footer_home')],
        ['url' => 'mailto:service@sjtv.com.tw', 'text' => __('emails.password_reset.footer_service')]
    ];
    $footerNote = __('emails.password_reset.footer_note');
@endphp

@section('content')
    <div class="greeting">
        {{ __('emails.password_reset.greeting', ['name' => $user->name]) }}
    </div>

    <div class="message">
        <p>{{ __('emails.password_reset.intro_1') }}</p>
        <p>{{ __('emails.password_reset.intro_2') }}</p>
    </div>

    <div class="button-container">
        <a href="{{ $resetUrl }}" class="reset-button" style="color: #ffffff !important; text-decoration: none;">
            {{ __('emails.password_reset.reset_button') }}
        </a>
    </div>

    <div class="expires-info">
        <strong>{{ __('emails.password_reset.expires_info') }}</strong>
    </div>

    <!-- Alternative method -->
    <div class="alternative">
        <h4>{{ __('emails.password_reset.alternative_title') }}</h4>
        <p>{{ __('emails.password_reset.alternative_text') }}</p>
        <div class="url">{{ $resetUrl }}</div>
    </div>

    <div class="warning">
        <strong>{{ __('emails.password_reset.security_title') }}</strong>
        <br>• {{ __('emails.password_reset.security_1') }}
        <br>• {{ __('emails.password_reset.security_2') }}
        <br>• {{ __('emails.password_reset.security_3') }}
    </div>
@endsection