@extends('frontend.layouts.app')

@section('title', __('frontend.customer_service.title') . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('description', __('frontend.customer_service.description'))

{{-- JSON-LD 結構化資料 --}}
@push('head')
    {!! \App\Facades\JsonLd::generateByType('contact-page', [
        'tel' => $siteInfo['tel'] ?? '05-3701199',
        'email' => $siteInfo['email'] ?? 'sjtvonline@gmail.com'
    ]) !!}
@endpush

@section('content')
<section class="section-customer-service-center">
    <div class="block-div block-01">
        <div class="breadcrumb-div">
            <i></i>
            <span></span>
            {{ __('frontend.customer_service.title') }}
        </div>
        <div class="block-outer">
            <div class="customer-service-center-div">
                <div class="two-cols">
                    <div class="col01">
                        <div class="block-title">
                            <div class="big-title">
                                <h1>{{ __('frontend.customer_service.title') }}</h1>
                            </div>
                        </div>
                        <div class="info">
                            <p>
                                {{ __('frontend.customer_service.description') }}<br />
                                {{ __('frontend.customer_service.processing_time') }}
                            </p>
                            <hr />
                            <p>
                                {{ __('frontend.customer_service.free_hotline') }}：0800-60-5858<br />
                                {{ __('frontend.customer_service.phone') }}：{{ $siteInfo['tel'] ?? '05-3701199' }}<br />
                                {{ __('frontend.customer_service.fax') }}：{{ $siteInfo['fax'] ?? '05-3660026' }}<br />
                                {{ __('frontend.customer_service.email') }}：{{ $siteInfo['email'] ?? 'sjtvonline@gmail.com' }}
                            </p>
                        </div>
                    </div>
                    <div class="col02">
                        <div class="boxer">
                            {{-- Vue 客服表單組件 --}}
                            <customer-service-form
                                :privacy-url="'{{ route('privacy') }}'"
                                :submit-url="'{{ route('customer-service.send') }}'"
                                :csrf-token="'{{ csrf_token() }}'"
                                :user-data='@json($userData)'
                                :translations='@json(__('frontend.customer_service'))'
                            ></customer-service-form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

