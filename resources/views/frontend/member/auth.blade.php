@extends('frontend.layouts.app')

@section('title', $pageTitle . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ $pageTitle }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
<meta name="current-tab" content="{{ $currentTab }}">
@endsection

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('webpage', [
        'name' => '會員登入與註冊',
        'description' => '登入或註冊 SJTV 會員帳號，享受專屬內容與服務。',
        'url' => url()->current(),
        'mainEntity' => [
            'name' => $siteInfo['title'] ?? 'SJTV'
        ]
    ]) !!}
@endpush

@section('content')
<section class="section-member">            
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="block-title">
                <div class="big-title">
                    <h1>{{ __('frontend.member.center') }}</h1>
                </div>                         
            </div>
            <div class="member-div">
                <div class="boxer">
                    <div class="tab-links">
                        <a href="#" id="login-tab" class="{{ $currentTab === 'login' ? 'active' : '' }}" data-form="login">{{ __('frontend.member.login') }}</a>
                        <a href="#" id="register-tab" class="{{ $currentTab === 'register' ? 'active' : '' }}" data-form="register">{{ __('frontend.member.register') }}</a>
                    </div>
                    
                    <!-- Vue 組件容器 -->
                    <login-form v-show="currentForm === 'login'" :texts="{
                        accountEmail: '{{ __('frontend.form.account_email') }}',
                        password: '{{ __('frontend.form.password') }}',
                        placeholderEmail: '{{ __('frontend.form.placeholder_email') }}',
                        placeholderPassword: '{{ __('frontend.form.placeholder_password') }}',
                        keepLogin: '{{ __('frontend.form.keep_login') }}',
                        loginBtn: '{{ __('frontend.btn.login') }}',
                        loginProcessing: '{{ __('frontend.member.login_processing') }}',
                        forgotPassword: '{{ __('frontend.form.forgot_password') }}',
                        otherLogin: '{{ __('frontend.form.other_login') }}',
                        googleLogin: '{{ __('frontend.form.google_login') }}',
                        lineLogin: '{{ __('frontend.form.line_login') }}',
                        validationRequired: '{{ __('frontend.form.validation_required') }}',
                        validationEmail: '{{ __('frontend.form.validation_email') }}',
                        validationPasswordMin: '{{ __('frontend.form.validation_password_min') }}',
                        checkFormFields: '{{ __('frontend.form.check_form_fields') }}',
                        required: '{{ __('frontend.form.required') }}'
                    }"></login-form>
                    
                    <register-form v-show="currentForm === 'register'" 
                        :cities="{{ json_encode($cities ?? []) }}"
                        :texts="{
                        accountEmail: '{{ __('frontend.form.account_email') }}',
                        password: '{{ __('frontend.form.password') }}',
                        passwordConfirm: '{{ __('frontend.form.password_confirm') }}',
                        name: '{{ __('frontend.form.name') }}',
                        birthday: '{{ __('frontend.form.birthday') }}',
                        placeResidence: '{{ __('frontend.form.place_residence') }}',
                        gender: '{{ __('frontend.form.gender') }}',
                        placeholderEmail: '{{ __('frontend.form.placeholder_email') }}',
                        placeholderPassword: '{{ __('frontend.form.placeholder_password_rule') }}',
                        placeholderPasswordConfirm: '{{ __('frontend.form.placeholder_password_confirm') }}',
                        placeholderName: '{{ __('frontend.form.placeholder_name') }}',
                        placeholderDate: '{{ __('frontend.form.placeholder_date') }}',
                        placeholderResidence: '{{ __('frontend.form.placeholder_residence') }}',
                        placeholderGender: '{{ __('frontend.form.placeholder_gender') }}',
                        genderMale: '{{ __('frontend.form.gender_male') }}',
                        genderFemale: '{{ __('frontend.form.gender_female') }}',
                        agreeTerms: '{{ __('frontend.form.agree_terms') }}',
                        termsService: '{{ __('frontend.form.terms_service') }}',
                        privacyPolicy: '{{ __('frontend.form.privacy_policy') }}',
                        agreeTermsText: '{{ __('frontend.form.agree_terms_text') }}',
                        registerBtn: '{{ __('frontend.btn.register') }}',
                        registerProcessing: '{{ __('frontend.member.register_processing') }}',
                        validationRequired: '{{ __('frontend.form.validation_required') }}',
                        validationEmail: '{{ __('frontend.form.validation_email') }}',
                        validationPasswordMin: '{{ __('frontend.form.validation_password_min') }}',
                        validationPasswordConfirm: '{{ __('frontend.form.validation_password_confirm') }}',
                        validationName: '{{ __('frontend.form.validation_name') }}',
                        validationBirthday: '{{ __('frontend.form.validation_birthday') }}',
                        validationResidence: '{{ __('frontend.form.validation_residence') }}',
                        validationGender: '{{ __('frontend.form.validation_gender') }}',
                        validationAgreeTerms: '{{ __('frontend.form.validation_agree_terms') }}',
                        checkFormFields: '{{ __('frontend.form.check_form_fields') }}',
                        confirmRegister: '{{ __('frontend.form.confirm_register') }}',
                        required: '{{ __('frontend.form.required') }}'
                    }"></register-form>
                </div>    
            </div>
        </div>
    </div>                      
</section>
@endsection

@push('scripts')
<!-- jQuery Validation Plugin -->
<script src="{{ asset('frontend/js/jquery.validate.js') }}"></script>

<script>
jQuery(document).ready(function($){
    $(function() {
        AOS.init();
    });

    // 檢查 session 訊息並顯示 SweetAlert
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: '登入失敗',
            text: '{{ session("error") }}',
            confirmButtonText: '確定',
            confirmButtonColor: '#d33'
        });
    @endif

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: '成功',
            text: '{{ session("success") }}',
            confirmButtonText: '確定',
            confirmButtonColor: '#28a745'
        });
    @endif

    @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: '提示',
            text: '{{ session("info") }}',
            confirmButtonText: '確定',
            confirmButtonColor: '#17a2b8'
        });
    @endif  
    
    // Tab 切換邏輯 - 不重新載入頁面
    $('.tab-links a').click(function(e) {
        e.preventDefault();
        
        const formType = $(this).data('form');
        const newUrl = `/member/${formType}`;
        const pageTitle = formType === 'login' ? '{{ __('frontend.member.login') }}' : '{{ __('frontend.member.register') }}';
        
        // 更新 active 狀態
        $('.tab-links a').removeClass('active');
        $(this).addClass('active');
        
        // 動態更新 title
        document.title = `${pageTitle} - {{ $siteInfo['title'] ?? '信吉衛視' }}`;
        
        // 更新 URL（不重新載入頁面）
        history.pushState({ form: formType, title: pageTitle }, '', newUrl);
        
        // 觸發 Vue 組件的 tab 切換
        if (window.vueApp) {
            window.vueApp.currentForm = formType;
        }
    });
    
    // 處理瀏覽器前進/後退
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.form) {
            const formType = event.state.form;
            const pageTitle = event.state.title || (formType === 'login' ? '{{ __('frontend.member.login') }}' : '{{ __('frontend.member.register') }}');
            
            // 更新 title
            document.title = `${pageTitle} - {{ $siteInfo['title'] ?? '信吉衛視' }}`;
            
            // 更新 tab active 狀態
            $('.tab-links a').removeClass('active');
            $(`.tab-links a[data-form="${formType}"]`).addClass('active');
            
            // 通知 Vue 組件切換
            if (window.vueApp) {
                window.vueApp.currentForm = formType;
            }
        } else {
            // 預設為登入
            document.title = '{{ __('frontend.member.login') }} - {{ $siteInfo['title'] ?? '信吉衛視' }}';
            $('.tab-links a').removeClass('active');
            $('.tab-links a[data-form="login"]').addClass('active');
            
            if (window.vueApp) {
                window.vueApp.currentForm = 'login';
            }
        }
    });
    
    // 設定初始狀態到 history
    const currentTab = '{{ $currentTab }}';
    if (!history.state) {
        history.replaceState({ form: currentTab }, '', `/member/${currentTab}`);
    }
    
    // 確保 Vue 應用正確初始化
    $(document).ready(function() {
        // 等待 Vue 應用載入完成
        setTimeout(function() {
            if (window.vueApp) {
                window.vueApp.currentForm = currentTab;
                console.log('Vue app initialized with:', currentTab);
            } else {
                console.error('Vue app not found');
            }
        }, 100);
    });
});
</script>
@endpush