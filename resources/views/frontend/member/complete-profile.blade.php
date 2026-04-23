@extends('frontend.layouts.app')

@section('title', __('frontend.member.complete_profile_title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('meta')
<meta name="description" content="請完成個人資料以使用會員功能">
@endsection

@section('content')
<section class="section-member">            
    <div class="block-div block-01">
        <div class="block-outer">
            <div class="block-title">
                <div class="big-title">
                    <h1>{{ __('frontend.member.complete_profile_title') }}</h1>
                </div>                         
            </div>
            <div class="member-div">
                <div class="boxer">
                    <!-- Vue 組件 -->
                    <complete-profile-form 
                        :user-email="'{{ $userEmail }}'"
                        :cities="{{ json_encode($cities ?? []) }}"
                        :areas="{{ json_encode($areas ?? []) }}"
                        :texts="{
                            title: '{{ __('frontend.member.complete_profile') }}',
                            description: '{{ __('frontend.member.complete_profile_description') }}',
                            email: '{{ __('frontend.form.email') }}',
                            name: '{{ __('frontend.form.name') }}',
                            phone: '{{ __('frontend.form.phone') }}',
                            birthday: '{{ __('frontend.form.birthday') }}',
                            gender: '{{ __('frontend.form.gender') }}',
                            placeResidence: '{{ __('frontend.form.place_residence') }}',
                            genderMale: '{{ __('frontend.form.gender_male') }}',
                            genderFemale: '{{ __('frontend.form.gender_female') }}',
                            submitBtn: '{{ __('frontend.member.complete_registration') }}',
                            processing: '{{ __('frontend.member.update_processing') }}',
                            placeholderEmail: '{{ __('frontend.form.placeholder_email') }}',
                            placeholderName: '{{ __('frontend.form.placeholder_name') }}',
                            placeholderPhone: '{{ __('frontend.form.placeholder_phone') }}',
                            placeholderDate: '{{ __('frontend.form.placeholder_date') }}',
                            placeholderResidence: '{{ __('frontend.form.placeholder_residence') }}',
                            selectCity: '{{ __('frontend.form.select_city') }}',
                            selectArea: '{{ __('frontend.form.select_area') }}',
                            required: '{{ __('frontend.form.required') }}'
                        }">
                    </complete-profile-form>
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
});
</script>

@if(session('info'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'info',
        title: '提醒',
        text: '{{ session('info') }}',
        confirmButtonColor: '#2CC0E2'
    });
});
</script>
@endif

@if(session('error'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: '錯誤',
        text: '{{ session('error') }}',
        confirmButtonColor: '#2CC0E2'
    });
});
</script>
@endif
@endpush