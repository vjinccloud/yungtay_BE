@extends('frontend.layouts.app')

@section('title', $pageTitle . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="{{ $pageTitle }} - {{ $siteInfo['title'] ?? '信吉衛視' }}">
@endsection

@section('content')
<section class="section-member section-member-detail">            
    <div class="block-div block-01">
        <div class="block-outer">                    
            <div class="member-div">
                <div class="member-center">
                    @include('frontend.member.partials.sidebar', ['currentPage' => 'account'])
                    
                    <!-- Vue 組件容器 -->
                    <div class="member-content member-navi-use-sticky">
                        <account-form 
                            :user="{{ json_encode($user) }}"
                            :cities="{{ json_encode($cities) }}"
                            :areas="{{ json_encode($areas) }}"
                            :texts="{
                                profile: '{{ __('frontend.member.profile') }}',
                                account: '{{ __('frontend.member.account') }}',
                                name: '{{ __('frontend.form.name') }}',
                                email: '{{ __('frontend.form.email') }}',
                                phone: '{{ __('frontend.form.phone') }}',
                                birthday: '{{ __('frontend.form.birthday') }}',
                                canOnlySetOnce: '{{ __('frontend.form.can_only_set_once') }}',
                                gender: '{{ __('frontend.form.gender') }}',
                                city: '{{ __('frontend.form.city') }}',
                                area: '{{ __('frontend.form.area') }}',
                                password: '{{ __('frontend.form.password') }}',
                                confirmPassword: '{{ __('frontend.form.password_confirm') }}',
                                idNumber: '{{ __('frontend.form.id_number') }}',
                                residence: '{{ __('frontend.form.place_residence') }}',
                                address: '{{ __('frontend.form.address') }}',
                                genderMale: '{{ __('frontend.form.gender_male') }}',
                                genderFemale: '{{ __('frontend.form.gender_female') }}',
                                placeholderName: '{{ __('frontend.form.placeholder_name') }}',
                                placeholderPassword: '{{ __('frontend.form.placeholder_password') }}',
                                placeholderConfirmPassword: '{{ __('frontend.form.placeholder_confirm_password') }}',
                                placeholderEmail: '{{ __('frontend.form.placeholder_email') }}',
                                placeholderPhone: '{{ __('frontend.form.placeholder_phone') }}',
                                placeholderIdNumber: '{{ __('frontend.form.placeholder_id_number') }}',
                                placeholderAddress: '{{ __('frontend.form.placeholder_address') }}',
                                selectGender: '{{ __('frontend.form.select_gender') }}',
                                selectCity: '{{ __('frontend.form.select_city') }}',
                                selectArea: '{{ __('frontend.form.select_area') }}',
                                confirmUpdate: '{{ __('frontend.member.confirm_update') }}',
                                checkFormFields: '{{ __('frontend.form.check_form_fields') }}',
                                updateFailed: '{{ __('frontend.member.update_failed') }}',
                                validationPasswordMatch: '{{ __('frontend.form.validation_password_match') }}',
                                placeholderDate: '{{ __('frontend.form.placeholder_date') }}',
                                placeholderGender: '{{ __('frontend.form.placeholder_gender') }}',
                                placeholderResidence: '{{ __('frontend.form.placeholder_residence') }}',
                                required: '{{ __('frontend.form.required') }}',
                                optional: '{{ __('frontend.form.optional') }}',
                                updateBtn: '{{ __('frontend.member.update_btn') }}',
                                updateProcessing: '{{ __('frontend.member.update_processing') }}',
                                profileUpdated: '{{ __('frontend.member.profile_updated') }}',
                                validationRequired: '{{ __('frontend.form.validation_required') }}',
                                validationName: '{{ __('frontend.form.validation_name') }}',
                                validationNameRequired: '{{ __('frontend.form.validation_required') }}',
                                validationNameMinlength: '{{ __('frontend.form.validation_name_minlength') }}',
                                validationPasswordMin: '{{ __('frontend.form.validation_password_min') }}',
                                validationBirthday: '{{ __('frontend.form.validation_birthday') }}',
                                validationBirthdateRequired: '{{ __('frontend.form.validation_birthdate_required') }}',
                                validationGender: '{{ __('frontend.form.validation_gender') }}',
                                validationGenderRequired: '{{ __('frontend.form.validation_gender') }}',
                                validationResidence: '{{ __('frontend.form.validation_residence') }}',
                                validationCityRequired: '{{ __('frontend.form.select_city') }}',
                                validationAreaRequired: '{{ __('frontend.form.select_area') }}',
                                validationAddressRequired: '{{ __('frontend.form.validation_address_required') }}',
                                validationAddressMinlength: '{{ __('frontend.form.validation_address_minlength') }}'
                            }">
                        </account-form>
                    </div>        
                </div>                       
            </div>
        </div>
    </div>                       
</section>
@endsection

@section('popups')
@include('frontend.member.partials.popups')
@endsection

@push('scripts')
<script>
jQuery(document).ready(function($){
    AOS.init();
    
    // 會員紅利彈窗
    $('[data-popup-id]').on('click', function() {
        var popupId = $(this).data('popup-id');
        $('#' + popupId).addClass('active').fadeIn();
    });
    
    $('[data-close-id]').on('click', function() {
        var popupId = $(this).data('close-id');
        $('#' + popupId).removeClass('active').fadeOut();
    });
}); 
</script>
@endpush