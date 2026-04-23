@extends('frontend.layouts.app')

@section('content')
<search-results :texts="{{ json_encode([
    'placeholder' => __('frontend.search.placeholder'),
    'noResults' => __('frontend.search.no_results'),
    'tabs' => [
        'all' => __('frontend.search.tabs.all'),
        'article' => __('frontend.search.tabs.article'),
        'drama' => __('frontend.search.tabs.drama'),
        'program' => __('frontend.search.tabs.program'),
        'live' => __('frontend.search.tabs.live'),
        'radio' => __('frontend.search.tabs.radio'),
        'news' => __('frontend.search.tabs.news'),
    ],
    'types' => [
        'article' => __('frontend.search.types.article'),
        'drama' => __('frontend.search.types.drama'),
        'program' => __('frontend.search.types.program'),
        'live' => __('frontend.search.types.live'),
        'radio' => __('frontend.search.types.radio'),
        'news' => __('frontend.search.types.news'),
    ]
]) }}"></search-results>
@endsection