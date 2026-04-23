{{-- 
    Email 輸入欄位模組
    參數：
    - $name: 欄位名稱 (預設: 'email')
    - $label: 顯示標籤 (預設: 'Email')
    - $placeholder: 佔位符文字 (預設: '請輸入Email')
    - $required: 是否必填 (預設: false)
    - $value: 預設值 (預設: old($name))
--}}

@php
    $name = $name ?? 'email';
    $label = $label ?? 'Email';
    $required = $required ?? false;
    $placeholder = $placeholder ?? '請輸入Email';
    $value = $value ?? old($name);
@endphp

<div class="label">{{ $label }}@if($required)<span>*</span>@endif</div>
<div class="controller">
    <input 
        type="email" 
        name="{{ $name }}" 
        placeholder="{{ $placeholder }}" 
        value="{{ $value }}"
        @if($required) required @endif
    >
</div>