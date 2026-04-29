<?php
// 用 PHP 直接寫入 blade，避免 PowerShell 編碼問題
$blade = <<<'BLADE'
<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8">
<style>
body { font-family: notosanstc, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 0; }
td, th, div, span, p { font-family: notosanstc, sans-serif; }
</style>
</head>
<body>

@php
    $cabinKeys    = array_keys($cabinSpecFields);
    $entranceKeys = array_keys($entranceSpecFields);
    $maxRows      = max(count($cabinKeys), count($entranceKeys));

    function pdfSpecLines($val): array {
        if (is_array($val)) {
            $lines = $val;
        } elseif (is_string($val)) {
            $val   = str_replace('\\n', "\n", $val);
            $lines = explode("\n", $val);
        } else {
            $lines = [strval($val)];
        }
        $sep    = "\xE3\x80\x80"; // U+3000 ideographic space (encoding-safe hex)
        $result = [];
        foreach ($lines as $line) {
            $parts = explode($sep, $line);
            if (count($parts) >= 2) {
                $result[] = ['value' => trim($parts[0]), 'sub' => trim(implode($sep, array_slice($parts, 1)))];
            } else {
                $result[] = ['value' => trim($line), 'sub' => null];
            }
        }
        return $result;
    }
@endphp

{{-- 主框架：左右兩欄 --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr>

{{-- ====== 左側規格面板 ====== --}}
<td width="48%" style="vertical-align:top;">

    {{-- Header：使用 bgcolor 屬性確保 mPDF 能渲染背景色 --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <td bgcolor="#464C53" style="padding:10px 14px;">
            <span style="font-size:14px; color:#CCCCCC; font-weight:bold;">規格 ({{ $order->series_model ?? '' }} 系列)</span>
        </td>
    </tr>
    </table>

    {{-- 規格表 --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; border-right:2px solid #EDEDED;">
        <thead>
            <tr>
                <td width="70" colspan="2" bgcolor="#F5F5F5" style="padding:8px 12px; font-size:11px; font-weight:bold; color:#1E2939; border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED;">車廂</td>
                <td colspan="2" bgcolor="#F5F5F5" style="padding:8px 12px; font-size:11px; font-weight:bold; color:#1E2939; border-bottom:1px solid #EDEDED;">出入口</td>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $maxRows; $i++)
            <tr style="border-bottom:1px solid #EDEDED;">

                {{-- 車廂 --}}
                @if ($i < count($cabinKeys))
                    @php
                        $cKey     = $cabinKeys[$i];
                        $cField   = $cabinSpecFields[$cKey];
                        $cVal     = $order->cabin_specs[$cKey] ?? null;
                        $cIconSrc = $iconBase64Map[$cField['icon']] ?? '';
                    @endphp
                    <td width="70" style="padding:8px 4px 8px 8px; border-right:1px solid #EDEDED; vertical-align:middle; text-align:center;">
                        @if ($cIconSrc)<img src="{{ $cIconSrc }}" width="34" height="34" alt=""><br>@endif
                        <span style="font-size:10px; color:#4A5565;">{{ $cField['label'] }}</span>
                    </td>
                    <td style="padding:8px 8px 8px 4px; vertical-align:top; border-right:1px solid #EDEDED;">
                        @if ($cVal)
                            @foreach (pdfSpecLines($cVal) as $line)
                                @if ($line['sub'])
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.6; vertical-align:top;">{{ $line['value'] }}</td>
                                        <td style="font-size:10px; color:#99A1AF; line-height:1.6; vertical-align:top; text-align:right; white-space:nowrap; padding-left:4px;">{{ $line['sub'] }}</td>
                                    </tr></table>
                                @else
                                    <div style="font-size:11px; color:#101828; line-height:1.6;">{{ $line['value'] }}</div>
                                @endif
                            @endforeach
                        @else
                            <span style="color:#99A1AF;">—</span>
                        @endif
                    </td>
                @else
                    <td width="70" style="border-right:1px solid #EDEDED;"></td>
                    <td style="border-right:1px solid #EDEDED;"></td>
                @endif

                {{-- 出入口 --}}
                @if ($i < count($entranceKeys))
                    @php
                        $eKey     = $entranceKeys[$i];
                        $eField   = $entranceSpecFields[$eKey];
                        $eVal     = $order->entrance_specs[$eKey] ?? null;
                        $eIconSrc = $iconBase64Map[$eField['icon']] ?? '';
                    @endphp
                    <td width="70" style="padding:8px 4px 8px 8px; border-right:1px solid #EDEDED; vertical-align:middle; text-align:center;">
                        @if ($eIconSrc)<img src="{{ $eIconSrc }}" width="34" height="34" alt=""><br>@endif
                        <span style="font-size:10px; color:#4A5565;">{{ $eField['label'] }}</span>
                    </td>
                    <td style="padding:8px 8px 8px 4px; vertical-align:top;">
                        @if ($eVal)
                            @foreach (pdfSpecLines($eVal) as $line)
                                @if ($line['sub'])
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.6; vertical-align:top;">{{ $line['value'] }}</td>
                                        <td style="font-size:10px; color:#99A1AF; line-height:1.6; vertical-align:top; text-align:right; white-space:nowrap; padding-left:4px;">{{ $line['sub'] }}</td>
                                    </tr></table>
                                @else
                                    <div style="font-size:11px; color:#101828; line-height:1.6;">{{ $line['value'] }}</div>
                                @endif
                            @endforeach
                        @else
                            <span style="color:#99A1AF;">—</span>
                        @endif
                    </td>
                @else
                    <td width="70" style="border-right:1px solid #EDEDED;"></td>
                    <td></td>
                @endif

            </tr>
            @endfor
        </tbody>
    </table>
</td>

{{-- ====== 右側資料面板 ====== --}}
<td width="4%"></td>
<td width="48%" style="vertical-align:top;">

    {{-- ELEVATOR STYLE header --}}
    <div style="padding-bottom:6px;">
        <span style="font-size:16px; color:#E3E3E3; letter-spacing:2px; font-weight:bold;">ELEVATOR STYLE</span>
    </div>

    {{-- 電梯渲染圖 --}}
    @if ($order->elevator_image && $elevatorImageBase64)
    <div style="text-align:center; padding-bottom:10px;">
        <img src="{{ $elevatorImageBase64 }}" style="max-width:100%; max-height:200px;" alt="">
    </div>
    @endif

    {{-- 客戶訂單資料 --}}
    <div style="font-size:14px; font-weight:bold; color:#1E2939; padding-bottom:8px;">客戶訂單資料</div>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:6px;">
    <tr>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">客戶名稱</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->customer_name ?: '—' }}</div>
        </td>
        <td width="2%"></td>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">專案名稱</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->project_name ?: '—' }}</div>
        </td>
    </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:6px;">
    <tr>
        <td style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">施工地點</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->construction_location ?: '—' }}</div>
        </td>
    </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:6px;">
    <tr>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">客戶窗口姓名</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->customer_contact_name ?: '—' }}</div>
        </td>
        <td width="2%"></td>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">客戶窗口信箱</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->customer_contact_email ?: '—' }}</div>
        </td>
    </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:6px;">
    <tr>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">業務人員姓名</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->sales_name ?: '—' }}</div>
        </td>
        <td width="2%"></td>
        <td width="49%" style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">業務人員信箱</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{{ $order->sales_email ?: '—' }}</div>
        </td>
    </tr>
    </table>

    <div style="margin-top:4px;">
        <span style="font-size:11px; color:#6A7282;">業務連絡電話：</span>
        <span style="font-size:13px;">{{ $order->sales_phone ?: '—' }}</span>
    </div>

    @if ($order->note)
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-top:8px;">
    <tr>
        <td style="vertical-align:top;">
            <div style="font-size:10px; color:#6A7282; margin-bottom:2px;">備註</div>
            <div style="font-size:11px; border:1px solid #E5E7EB; padding:4px 8px;">{!! nl2br(e($order->note)) !!}</div>
        </td>
    </tr>
    </table>
    @endif

</td>
</tr>
</table>

</body>
</html>
BLADE;

$target = __DIR__ . '/../resources/views/pdf/history-order.blade.php';
file_put_contents($target, $blade);
echo "Written " . strlen($blade) . " bytes to: " . realpath($target) . "\n";
