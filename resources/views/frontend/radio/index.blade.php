@extends('frontend.layouts.app')

@section('title', __('frontend.nav.radio') . ' - ' . $siteInfo['title'])

{{-- JSON-LD 結構化資料 --}}
@push('head')
@if(isset($featuredRadios) && !empty($featuredRadios))
    {!! \App\Facades\JsonLd::generateByType('collection-page', [
        'type' => 'radio',
        'items' => $featuredRadios,
        'pageInfo' => [
            'title' => __('frontend.nav.radio'),
            'description' => __('frontend.filter.collection_description', ['type' => __('frontend.nav.radio')])
        ]
    ]) !!}
@endif
@endpush

@section('content')
    <section class="section-radio-list">
        <div class="breadcrumb-div">
                <i></i>
                <span></span>
                {{ __('frontend.nav.radio') }}
        </div>
        <div class="block-div block-01">
            <div class="block-outer">
                <div class="mobile-arrow-scroll">
                    <i class="scroll-prev"></i>
                    <i class="scroll-next"></i>
                </div>
                <div class="tab-links-outer"  id="subScrollCate">
                    <div class="links">
                        <a href="#" class="{{ $currentCategory == '' ? 'active' : '' }}" data-category="">{{ __('frontend.filter.all_radio') }}</a>
                        @foreach($categories as $category)
                            <a href="#" class="{{ $currentCategory == $category['id'] ? 'active' : '' }}" data-category="{{ $category['id'] }}">{{ $category['name'] }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="tab-content" style="min-height: 500px;">
                    <!-- Vue 組件 -->
                    <radio-list
                        initial-category="{{ request('category') }}"
                        :categories='@json($categories)'
                        :texts="{
                            no_data: '{{ __('frontend.status.no_radio') }}'
                        }">
                    </radio-list>
                </div>
            </div>
        </div>
    </section>
@endsection
