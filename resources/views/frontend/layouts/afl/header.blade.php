<header>
    <div class="header-div">
        <div class="block-outer">
            <div class="brand-navi">
                <div class="brand">
                    <a href="{{ route('afl.home') }}">
                        <img src="{{ asset('frontend/images/header_logo.png') }}" 
                             alt="{{ $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會' }}" 
                             title="{{ $siteInfo['title'] ?? '財團法人新北市為愛前行社會福利基金會' }}">
                    </a>
                </div>
                <div class="navi">
                    <ul>
                        {{-- 最新消息 --}}
                        <li><a href="{{ route('afl.news.index') }}">最新消息</a></li>
                        
                        {{-- 影響力成果（有子選單） --}}
                        <li class="had-sub-menu">
                            <a href="javascript:;" class="link">影響力成果<i></i></a>
                            <div class="sub-menu">
                                <div class="sub-items">
                                    <div><a href="{{ route('afl.expert.index') }}">生命故事</a></div>
                                </div>
                            </div>
                        </li>
                        
                        {{-- 病人自主權利法 --}}
                        <li>
                            <a href="https://parc.tw/" target="_blank">病人自主權利法</a>
                        </li>
                        
                        {{-- 全人健康與幸福 --}}
                        <li>
                            <a href="https://afl.org.tw/node/255" target="_blank">全人健康與幸福</a>
                        </li>
                        
                        {{-- 關於我們（有子選單） --}}
                        <li class="had-sub-menu">
                            <a href="javascript:;" class="link">關於我們<i></i></a>
                            <div class="sub-menu">
                                <div class="sub-items">
                                    <div><a href="{{ route('afl.about.mission') }}">使命與願景</a></div>
                                    <div><a href="{{ route('afl.about.founder') }}">創辦人的話</a></div>
                                </div>
                            </div>
                        </li>
                        
                        {{-- 支持我們 --}}
                        <li class="support"><a href="https://afl.org.tw/civicrm/contribute/transact?reset=1&id=2" target="_blank">支持我們</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
