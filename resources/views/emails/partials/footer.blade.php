{{-- 郵件模板共用 Footer --}}
<div class="footer">
    <p><strong>{{ $siteName ?? config('app.name', '信吉衛視') }}</strong></p>
    <p>{{ $footerContact ?? '客服信箱：service@sjtv.com.tw' }}</p>
    @if(isset($footerLinks) && is_array($footerLinks))
        <p>
            @foreach($footerLinks as $index => $link)
                <a href="{{ $link['url'] }}">{{ $link['text'] }}</a>
                @if(!$loop->last) | @endif
            @endforeach
        </p>
    @else
        <p>
            <a href="{{ url('/') }}">{{ __('emails.footer.home', [], 'zh_TW') ?? '官方網站' }}</a> | 
            <a href="mailto:service@sjtv.com.tw">{{ __('emails.footer.service', [], 'zh_TW') ?? '聯絡我們' }}</a>
        </p>
    @endif
    <p style="margin-top: 15px; font-size: 12px; opacity: 0.7;">
        {{ $footerNote ?? '此為系統自動發送的郵件，請勿直接回覆。' }}
    </p>
</div>