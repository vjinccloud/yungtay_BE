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
        $sep    = "\xE3\x80\x80"; // U+3000 ideographic space
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

{{-- 主框架:左右兩欄 (對應原版 col-6 + col-6 with margin-left:15px) --}}
<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
<tr>

{{-- ============================================================ --}}
{{-- 左側規格面板 (col-6)                                          --}}
{{-- ============================================================ --}}
<td width="49%" style="vertical-align:top;">

    {{-- 灰色 Header 區塊 --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <td bgcolor="#464C53" style="padding:8px 14px;">
            <span style="font-size:13px; color:#CCCCCC; font-weight:bold;">規格 ({{ $order->series_model ?? '' }} 系列)</span>
        </td>
    </tr>
    </table>

    {{-- 規格表本體 --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
        <thead>
            <tr>
                <td colspan="2" bgcolor="#F5F5F5" style="padding:8px 12px; font-size:11px; font-weight:bold; color:#1E2939; border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED; width:50%;">車廂</td>
                <td colspan="2" bgcolor="#F5F5F5" style="padding:8px 12px; font-size:11px; font-weight:bold; color:#1E2939; border-bottom:1px solid #EDEDED; width:50%;">出入口</td>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $maxRows; $i++)
            <tr>

                {{-- 車廂 icon --}}
                @if ($i < count($cabinKeys))
                    @php
                        $cKey     = $cabinKeys[$i];
                        $cField   = $cabinSpecFields[$cKey];
                        $cVal     = $order->cabin_specs[$cKey] ?? null;
                        $cIconSrc = $iconBase64Map[$cField['icon']] ?? '';
                    @endphp
                    <td width="60" style="padding:8px 4px 8px 8px; border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED; vertical-align:middle; text-align:center;">
                        @if ($cIconSrc)<img src="{{ $cIconSrc }}" width="34" height="34" alt=""><br>@endif
                        <span style="font-size:10px; color:#4A5565;">{{ $cField['label'] }}</span>
                    </td>
                    <td style="padding:8px 8px 8px 4px; vertical-align:middle; border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED;">
                        @if ($cVal)
                            @foreach (pdfSpecLines($cVal) as $line)
                                @if ($line['sub'])
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.5; vertical-align:top;">{{ $line['value'] }}</td>
                                        <td style="font-size:10px; color:#99A1AF; line-height:1.5; vertical-align:top; text-align:right; white-space:nowrap; padding-left:4px;">{{ $line['sub'] }}</td>
                                    </tr></table>
                                @else
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.5;">{{ $line['value'] }}</td>
                                    </tr></table>
                                @endif
                            @endforeach
                        @else
                            <span style="color:#99A1AF;">—</span>
                        @endif
                    </td>
                @else
                    <td width="60" style="border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED;">&nbsp;</td>
                    <td style="border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED;">&nbsp;</td>
                @endif

                {{-- 出入口 icon --}}
                @if ($i < count($entranceKeys))
                    @php
                        $eKey     = $entranceKeys[$i];
                        $eField   = $entranceSpecFields[$eKey];
                        $eVal     = $order->entrance_specs[$eKey] ?? null;
                        $eIconSrc = $iconBase64Map[$eField['icon']] ?? '';
                    @endphp
                    <td width="60" style="padding:8px 4px 8px 8px; border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED; vertical-align:middle; text-align:center;">
                        @if ($eIconSrc)<img src="{{ $eIconSrc }}" width="34" height="34" alt=""><br>@endif
                        <span style="font-size:10px; color:#4A5565;">{{ $eField['label'] }}</span>
                    </td>
                    <td style="padding:8px 8px 8px 4px; vertical-align:middle; border-bottom:1px solid #EDEDED;">
                        @if ($eVal)
                            @foreach (pdfSpecLines($eVal) as $line)
                                @if ($line['sub'])
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.5; vertical-align:top;">{{ $line['value'] }}</td>
                                        <td style="font-size:10px; color:#99A1AF; line-height:1.5; vertical-align:top; text-align:right; white-space:nowrap; padding-left:4px;">{{ $line['sub'] }}</td>
                                    </tr></table>
                                @else
                                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;"><tr>
                                        <td style="font-size:11px; color:#101828; line-height:1.5;">{{ $line['value'] }}</td>
                                    </tr></table>
                                @endif
                            @endforeach
                        @else
                            <span style="color:#99A1AF;">—</span>
                        @endif
                    </td>
                @else
                    <td width="60" style="border-right:1px solid #EDEDED; border-bottom:1px solid #EDEDED;">&nbsp;</td>
                    <td style="border-bottom:1px solid #EDEDED;">&nbsp;</td>
                @endif

            </tr>
            @endfor
        </tbody>
    </table>
</td>

{{-- 中間間距 (對應原版 margin-left:15px) --}}
<td width="2%">&nbsp;</td>

{{-- ============================================================ --}}
{{-- 右側資料面板 (col-6 with px-4)                                --}}
{{-- ============================================================ --}}
<td width="49%" style="vertical-align:top;">

    {{-- 整個右側內容包一層,套用左右 padding (對應原版 px-4) --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
    <tr>
        <td style="padding:8px 16px 12px 16px;">

            {{-- ELEVATOR STYLE 標題 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td style="padding-bottom:8px;">
                    <span style="font-size:14px; color:#E3E3E3; letter-spacing:1px; font-weight:bold;">ELEVATOR STYLE</span>
                </td>
            </tr>
            </table>

            {{-- 電梯渲染圖 --}}
            @if ($order->elevator_image && $elevatorImageBase64)
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td align="center" style="padding-bottom:12px;">
                    <img src="{{ $elevatorImageBase64 }}" style="max-width:100%; max-height:240px;" alt="">
                </td>
            </tr>
            </table>
            @endif

            {{-- 客戶訂單資料 標題 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td style="font-size:13px; font-weight:bold; color:#1E2939; padding-bottom:6px;">客戶訂單資料</td>
            </tr>
            </table>

            {{-- 客戶名稱 / 專案名稱 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">客戶名稱</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->customer_name ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
                <td width="2%">&nbsp;</td>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">專案名稱</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->project_name ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>

            {{-- row 間距 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="font-size:1px; line-height:1px; height:8px;">&nbsp;</td></tr>
            </table>

            {{-- 施工地點 (整列) --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">施工地點</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->construction_location ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="font-size:1px; line-height:1px; height:8px;">&nbsp;</td></tr>
            </table>

            {{-- 客戶窗口姓名 / 信箱 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">客戶窗口姓名</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->customer_contact_name ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
                <td width="2%">&nbsp;</td>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">客戶窗口信箱</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->customer_contact_email ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="font-size:1px; line-height:1px; height:8px;">&nbsp;</td></tr>
            </table>

            {{-- 業務人員姓名 / 信箱 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">業務人員姓名</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->sales_name ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
                <td width="2%">&nbsp;</td>
                <td width="49%" style="vertical-align:top;">
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">業務人員信箱</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB; height:22px;">{!! $order->sales_email ?: '&nbsp;' !!}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="font-size:1px; line-height:1px; height:10px;">&nbsp;</td></tr>
            </table>

            {{-- 業務連絡電話 --}}
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td>
                    <span style="font-size:10px; color:#6A7282;">業務連絡電話:</span>
                    <span style="font-size:12px; color:#101828;">{{ $order->sales_phone ?: '—' }}</span>
                </td>
            </tr>
            </table>

            {{-- 備註 --}}
            @if ($order->note)
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="font-size:1px; line-height:1px; height:8px;">&nbsp;</td></tr>
            </table>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr>
                <td>
                    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        <tr>
                            <td style="font-size:10px; color:#6A7282; padding-bottom:2px;">備註</td>
                        </tr>
                        <tr>
                            <td style="font-size:11px; color:#101828; padding:5px 8px; border:1px solid #E5E7EB;">{!! nl2br(e($order->note)) !!}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>
            @endif

        </td>
    </tr>
    </table>

</td>
</tr>
</table>

</body>
</html>