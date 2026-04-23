{{-- 會員中心左側選單 (共用組件) --}}
<div class="member-navi">
    <div class="navi-info">
        <div class="breadcrumb-div">
            <i></i>
            <span></span>
            {{ __('frontend.member.center') }}
        </div>   
        <div class="thumbnail-bonus-div"> 
            <div class="col01">
                <div class="thumbnail">
                    <div class="img">
                        <img src="{{ asset('frontend/images/icon_member_thumbnail.svg') }}">
                    </div>                                              
                </div>
            </div>
            <div class="col02">    
                <div class="name">
                    {{ Auth::user()->name ?? __('frontend.member.welcome') }}
                </div>  
                {{-- 會員紅利功能暫時隱藏 --}}
                {{-- <div class="bonus">
                    <div class="box">
                        <div class="sub01"><i></i><label>{{ __('frontend.member.bonus') }}</label></div><div class="sub02"><b>200</b></div>
                    </div>
                    <i class="question" data-popup-id="popupMemberBonus"></i>
                </div> --}}
            </div>    
        </div>      
        <div class="logout mobile"><!--手機版-->
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-mobile').submit();">{{ __('frontend.member.logout') }}</a>
            <form id="logout-form-mobile" method="POST" action="{{ route('member.logout') }}" style="display: none;">
                @csrf
            </form>
        </div>
        <hr />
        <div class="navi-outer">
            <div class="navi">
                <a href="{{ route('member.account') }}" @class(['active' => $currentPage === 'account'])>{{ __('frontend.member.account') }}</a>
                <a href="{{ route('member.collection') }}" @class(['active' => $currentPage === 'collection'])>{{ __('frontend.member.collection') }}</a>
                <a href="{{ route('member.history') }}" @class(['active' => $currentPage === 'history'])>{{ __('frontend.member.history') }}</a>
                <a href="{{ route('member.customer-service-records') }}" @class(['active' => $currentPage === 'customer-service-records'])>{{ __('frontend.member.customer_service_records') }}</a>
                <a href="{{ route('member.notifications') }}" @class(['active' => $currentPage === 'notifications'])>{{ __('frontend.member.notice') }}</a>                                        
            </div>
        </div>
        <div class="logout web"><!--電腦版-->
            <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form-web').submit();">{{ __('frontend.member.logout') }}</a>
            <form id="logout-form-web" method="POST" action="{{ route('member.logout') }}" style="display: none;">
                @csrf
            </form>
        </div>
    </div>      
</div>