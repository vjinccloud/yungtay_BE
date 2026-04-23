{{-- resources/views/frontend/media/videos/show.blade.php --}}
@extends('frontend.layouts.app')

@section('title', $content['title'] . ' - ' . __('frontend.video.episode', ['number' => $currentEpisode['seq']]) . ' - ' . ($siteInfo['title'] ?? '信吉衛視'))
@section('description', Str::limit($currentEpisode['title'] ?? $content['description'] ?? '', 150))
@section('keywords', implode(', ', [$content['title'], __('frontend.video.episode', ['number' => $currentEpisode['seq']]), $type === 'drama' ? __('frontend.nav.drama') : __('frontend.nav.program'), $siteInfo['title'] ?? '信吉衛視']))

{{-- JSON-LD 結構化資料 --}}
@push('head')
{!! \App\Facades\JsonLd::generateByType('video-object', [
    'content' => $content,
    'episode' => $currentEpisode,
    'type' => $type
]) !!}
@endpush

@section('content')
<section class="section-video-detail">
    <div class="block-div block-01">
        <div class="block-outer">
            {{-- 桌機版影片 --}}
            <div class="video-detail">
            @if($currentEpisode['video_type'] === 'youtube' && ! empty($currentEpisode['video_embed_url']))
                <iframe width="560" height="315"
                src="{{ $currentEpisode['video_embed_url'] }}"
                frameborder="0"
               allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                </iframe>
            @elseif($currentEpisode['video_type'] === 'upload' && ! empty($currentEpisode['video_url']))
               <video controls preload="metadata" width="100%">
                <source
                    src="{{ route('episode.stream', ['filePath' => $currentEpisode['video_file_path']]) }}"
                    type="video/mp4"
                >
                {{ __('frontend.video.not_supported') }}
                </video>

            @else
                <p>{{ __('frontend.video.unavailable') }}</p>
            @endif
            </div>

            {{-- 影片資訊 --}}
            {{-- <div class="video-info">
                <h1>{{ $content['title'] }} - {{ __('frontend.video.episode', ['number' => $currentEpisode['seq']]) }}</h1>
                @if(!empty($currentEpisode['duration']))
                <p class="duration">{{ __('frontend.video.duration') }}：{{ $currentEpisode['duration'] }}</p>
                @endif
                @if(!empty($currentEpisode['title']))
                    <p class="episode-title">{{ $currentEpisode['title'] }}</p>
                @endif
            </div> --}}

            {{-- 上下集按鈕 --}}
            {{-- <div class="episode-nav">
                @if($prevEpisode)
                    <a href="{{ route($type . '.video.show', [$content['id'], $prevEpisode->id]) }}" class="prev-btn">
                        <i class="fa fa-chevron-left"></i> {{ __('frontend.video.previous_episode') }}
                    </a>
                @endif

                @if($nextEpisode)
                    <a href="{{ route($type . '.video.show', [$content['id'], $nextEpisode->id]) }}" class="next-btn" id="nextEpisodeBtn">
                        {{ __('frontend.video.next_episode') }} <i class="fa fa-chevron-right"></i>
                    </a>
                @endif
            </div> --}}

            {{-- 集數列表 --}}
        </div>
    </div>

    <!-- 影片觀看記錄組件 -->
    <view-recorder
        content-type="{{ $type }}"
        :content-id="{{ $content['id'] }}"
        :episode-id="{{ $currentEpisode['id'] }}"
        :delay="5000">
    </view-recorder>
</section>
@endsection

@push('scripts')
<script src="{{ asset('frontend/js/jquery.fullscreen-min.js') }}"></script>
<script>
    jQuery(document).ready(function($) {
        // 自動播放下一集功能
        @if($nextEpisode)
        if ($('video').length > 0) {
            $('video').on('ended', function() {
                if (confirm('{{ __('frontend.video.auto_next') }}')) {
                    window.location.href = $('#nextEpisodeBtn').attr('href');
                }
            });
        }
        @endif
    });
</script>
@endpush
