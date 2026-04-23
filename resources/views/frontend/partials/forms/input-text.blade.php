{{-- 
    通用文字輸入欄位模組
    參數：
    - $name: 欄位名稱
    - $label: 顯示標籤
    - $placeholder: 佔位符文字 (預設: "請輸入{$label}")
    - $required: 是否必填 (預設: false)
    - $type: input type (預設: 'text')
    - $value: 預設值 (預設: old($name))
    - $maxlength: 最大長度 (選填)
    - $class: 額外的 CSS class (選填)
--}}

@php
    $type = $type ?? 'text';
    $required = $required ?? false;
    $placeholder = $placeholder ?? "請輸入{$label}";
    $value = $value ?? old($name);
    $class = $class ?? '';
@endphp

<div class="label">{{ $label }}@if($required)<span>*</span>@endif</div>
<div class="controller">
    <input 
        type="{{ $type }}" 
        name="{{ $name }}" 
        placeholder="{{ $placeholder }}" 
        value="{{ $value }}"
        @if($required) required @endif
        @if(isset($maxlength)) maxlength="{{ $maxlength }}" @endif
        @if($class) class="{{ $class }}" @endif
    >
</div>