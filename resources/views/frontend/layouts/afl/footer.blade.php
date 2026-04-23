<footer>
    <div class="footer-top">
        <div class="block-outer">
            <div class="two-items">
                <div class="item">
                    <div class="logo">
                        <img src="{{ asset('frontend/images/footer_logo.png') }}" 
                             alt="{{ $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會' }}">
                    </div>
                    
                    <div class="support">
                        <a href="https://afl.org.tw/civicrm/contribute/transact?reset=1&id=2" target="_blank">支持我們<i></i></a>
                    </div>
                </div>
                
                <div class="item">
                    {{-- 手機版導航連結 --}}
                    <div class="link mobile">
                        <ul>
                            <li><a href="{{ route('afl.news.index') }}">最新消息</a></li>
                            <li><a href="https://parc.tw/story/special" target="_blank">影響力成果</a></li>
                            <li><a href="https://parc.tw/" target="_blank">病人自主權利法</a></li>
                            <li><a href="https://afl.org.tw/node/255" target="_blank">全人健康與幸福</a></li>
                            <li><a href="{{ route('afl.about.mission') }}">關於我們</a></li>
                        </ul>
                    </div>
                    
                    <div class="headline">
                        <h3>聯絡我們</h3>
                    </div>
                    
                    {{-- 社群連結 --}}
                    <div class="society">
                        @if(!empty($siteInfo['fb']))
                            <a href="{{ $siteInfo['fb'] }}" target="_blank"><img src="{{ asset('frontend/images/society_01.png') }}"></a>
                        @else
                            <a href="#" target="_blank"><img src="{{ asset('frontend/images/society_01.png') }}"></a>
                        @endif
                        @if(!empty($siteInfo['ig']))
                            <a href="{{ $siteInfo['ig'] }}" target="_blank"><img src="{{ asset('frontend/images/society_02.png') }}"></a>
                        @else
                            <a href="#" target="_blank"><img src="{{ asset('frontend/images/society_02.png') }}"></a>
                        @endif
                        @if(!empty($siteInfo['youtube']))
                            <a href="{{ $siteInfo['youtube'] }}" target="_blank"><img src="{{ asset('frontend/images/society_03.png') }}"></a>
                        @else
                            <a href="#" target="_blank"><img src="{{ asset('frontend/images/society_03.png') }}"></a>
                        @endif
                        @if(!empty($siteInfo['line']))
                            <a href="{{ $siteInfo['line'] }}" target="_blank"><img src="{{ asset('frontend/images/society_04.png') }}"></a>
                        @else
                            <a href="#" target="_blank"><img src="{{ asset('frontend/images/society_04.png') }}"></a>
                        @endif
                    </div>
                    
                    {{-- 聯絡資訊 --}}
                    <div class="contact">
                        <div class="email">
                            <i></i>
                            <a href="mailto:{{ $siteInfo['email'] ?? 'contact@gmail.com' }}" target="_blank">
                                {{ $siteInfo['email'] ?? 'contact@gmail.com' }}
                            </a>
                        </div>
                        <div class="phone">
                            <i></i>
                            <a href="tel:{{ $siteInfo['phone'] ?? '01-2585-1111' }}" target="_blank">
                                {{ $siteInfo['phone'] ?? '01-2585-1111' }}
                            </a>
                        </div>
                    </div>
                    
                    {{-- 電腦版導航連結 --}}
                    <div class="link web">
                        <ul>
                            <li><a href="{{ route('afl.news.index') }}">最新消息</a></li>
                            <li><a href="https://parc.tw/story/special" target="_blank">影響力成果</a></li>
                            <li><a href="https://parc.tw/" target="_blank">病人自主權利法</a></li>
                            <li><a href="https://afl.org.tw/node/255" target="_blank">全人健康與幸福</a></li>
                            <li><a href="{{ route('afl.about.mission') }}">關於我們</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="block-outer">
            <p>
                <span>{{ $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會' }}</span>
                <span>© {{ date('Y') }} All rights reserved.</span>
            </p>
        </div>
    </div>
</footer>
