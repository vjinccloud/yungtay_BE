<div id="mobile-nav">
    <div class="block-outer">      
        <div class="search">
            <form action="{{ route('search') }}" method="GET">
                <button type="submit"></button>
                <input type="text" name="keyword" placeholder="{{ __('frontend.search.placeholder') }}" maxlength="30">
            </form>
        </div>     
        <div class="navi">
            <ul>
                <li><a href="{{ route('articles.index') }}">{{ __('frontend.nav.news') }}</a></li>
                <li><a href="{{ route('drama.index') }}">{{ __('frontend.nav.drama') }}</a></li>
                <li><a href="{{ route('program.index') }}">{{ __('frontend.nav.program') }}</a></li>
                <li><a href="{{ route('live.index') }}">{{ __('frontend.nav.live') }}</a></li>
                <li><a href="{{ route('radio.index') }}">{{ __('frontend.nav.radio') }}</a></li>
            </ul>
        </div>
        <div class="links">
            <a href="{{ route('customer-service') }}">{{ __('frontend.nav.customer_service') }}</a>
            <a href="{{ route('news') }}">{{ __('frontend.nav.latest_news') }}</a>
            <a href="{{ route('privacy') }}">{{ __('frontend.nav.privacy_policy') }}</a>
        </div>
        <div class="society">
            <a href="#" target="_blank"><img src="{{ asset('frontend/images/icon_youtube_white.svg') }}"></a>
            <a href="#" target="_blank"><img src="{{ asset('frontend/images/icon_instagram_white.svg') }}"></a>
            <a href="#" target="_blank"><img src="{{ asset('frontend/images/icon_youtube_white.svg') }}"></a>
        </div>
        <div class="language">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'zh_TW']) }}" class="{{ app()->getLocale() === 'zh_TW' ? 'active' : '' }}">{{ __('frontend.language.chinese') }}</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">{{ __('frontend.language.english') }}</a>
        </div>
    </div>
</div>