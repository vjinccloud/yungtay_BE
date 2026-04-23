<footer>
    <section class="section-footer">
        <div class="footer-top">
            <div class="block-outer">
                <div class="two-cols">
                    <div class="col01">
                        <div class="brand">
                            <img src="{{ asset('frontend/images/footer_logo.png') }}" alt="{{ $siteInfo['title'] ?? __('frontend.site.name') }}" title="{{ $siteInfo['title'] ?? __('frontend.site.name') }}">
                        </div>
                        <div class="links">
                            <a href="{{ route('customer-service') }}">{{ __('frontend.nav.customer_service') }}</a>
                            <a href="{{ route('member.account') }}">{{ __('frontend.member.center') }}</a>
                            <a href="{{ route('news') }}">{{ __('frontend.nav.latest_news') }}</a>
                            <a href="{{ route('privacy') }}">{{ __('frontend.nav.privacy_policy') }}</a>
                        </div>
                    </div>
                    <div class="col02">
                        <div class="society">
                            @if(!empty($siteInfo['youtube']))
                                <a href="{{ $siteInfo['youtube'] }}" target="_blank"><img src="{{ asset('frontend/images/icon_youtube_white.svg') }}"></a>
                            @endif
                            @if(!empty($siteInfo['ig']))
                                <a href="{{ $siteInfo['ig'] }}" target="_blank"><img src="{{ asset('frontend/images/icon_instagram_white.svg') }}"></a>
                            @endif
                            @if(!empty($siteInfo['fb']))
                                <a href="{{ $siteInfo['fb'] }}" target="_blank"><img src="{{ asset('frontend/images/icon_facebook_white.svg') }}"></a>
                            @endif
                        </div>
                        <div class="application">
                            @if(!empty($siteInfo['app_apple_store']))
                                <a href="{{ $siteInfo['app_apple_store'] }}" target="_blank"><img src="{{ asset('frontend/images/icon_app_store.svg') }}"></a>
                            @endif
                            @if(!empty($siteInfo['app_google_play']))
                                <a href="{{ $siteInfo['app_google_play'] }}" target="_blank"><img src="{{ asset('frontend/images/icon_google_play.svg') }}"></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="block-outer">
                <div class="two-cols">
                    <div class="col01">
                        <a href="#" class="gotop">{{ __('frontend.btn.top') }}<i></i></a>
                    </div>
                    <div class="col02">
                        <div class="copyright">
                            {{ __('frontend.site.copyright', ['year' => date('Y')]) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</footer>
