<header>
    <div class="header-div">
        <div class="block-outer">
            <div class="two-cols">
                <div class="col01">
                    <div class="brand">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset('frontend/images/header_logo.png') }}" alt="{{ $siteInfo['title'] ?? __('frontend.site.name') }}" title="{{ $siteInfo['title'] ?? __('frontend.site.name') }}">
                        </a>
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
                </div>
                <div class="col02">    
                    <div class="switch switch-day-night">
                        <input type="checkbox" id="switchDayNight"/>
                        <div>
                            <label for="switchDayNight"></label>
                        </div>
                    </div>
                    <form class="search" action="{{ route('search') }}" method="GET">
                        <button type="submit"></button>
                        <input type="text" name="keyword" placeholder="{{ __('frontend.search.placeholder') }}" maxlength="30">
                    </form>
                    <div class="language">
                        <i class="global"></i>
                        <div class="dropdown-menu">
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'zh_TW']) }}" class="{{ app()->getLocale() === 'zh_TW' ? 'active' : '' }}">{{ __('frontend.language.chinese') }}</a>
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}">{{ __('frontend.language.english') }}</a>
                        </div>    
                    </div>
                    <div class="member">
                        @auth
                            <a href="{{ route('member.account') }}"><i class="user"></i></a>
                        @else
                            <a href="{{ route('member.login') }}"><i class="user"></i></a>
                        @endauth
                    </div>       
                </div>
            </div>            
        </div>
    </div>
</header>