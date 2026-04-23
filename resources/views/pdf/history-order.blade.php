<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: 'Noto';
            src: url('{{ storage_path("fonts/NotoSansTC.ttf") }}') format('truetype');
            font-weight: normal;
        }
        @font-face {
            font-family: 'Noto';
            src: url('{{ storage_path("fonts/NotoSansTC.ttf") }}') format('truetype');
            font-weight: bold;
        }

        @page { margin: 0; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Noto', sans-serif;
            font-size: 14px;
            color: #111;
        }

        .wrapper {
            width: 100%;
            border-collapse: collapse;
        }
        .wrapper > tbody > tr > td {
            vertical-align: top;
            min-height: 793px;
            height: 793px;
        }

        /* ====== 左側深色面板 ====== */
        .left-panel {
            width: 42%;
            background: #2d3238;
            color: #e0e0e0;
            padding: 0;
        }

        .left-header {
            background: #23272b;
            padding: 10px 12px;
            border-bottom: 1px solid #3a3f44;
        }

        .left-header-text {
            font-size: 16px;
            color: #ddd;
        }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }

        .spec-table th {
            padding: 8px 10px;
            font-size: 13px;
            color: #ccc;
            border-bottom: 2px solid #3a3f44;
            text-align: left;
        }

        .spec-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #3a3f44;
            vertical-align: top;
            font-size: 13px;
            color: #e0e0e0;
            line-height: 1.5;
        }

        .col-divider {
            border-right: 1px solid #3a3f44;
        }

        .spec-icon-td {
            width: 48px;
            text-align: center;
            vertical-align: top;
            padding-top: 7px;
            color: #ccc;
        }

        .icon-symbol {
            font-size: 15px;
            color: #d9534f;
            display: block;
            margin-bottom: 1px;
        }

        .icon-label {
            font-size: 11px;
            color: #aaa;
        }

        .spec-empty { color: #555; }

        /* ====== 右側白色面板 ====== */
        .right-panel {
            width: 58%;
            background: #ffffff;
            color: #222;
        }

        .right-header {
            padding: 10px 18px 4px 18px;
        }

        .right-header-text {
            font-size: 18px;
            color: #666;
            letter-spacing: 2px;
        }

        .image-box {
            text-align: center;
            padding: 6px 18px 8px 18px;
        }

        .image-box img {
            max-width: 100%;
            max-height: 220px;
        }

        .no-image {
            padding: 25px 0;
            color: #bbb;
            font-size: 12px;
            text-align: center;
        }

        .customer-section {
            padding: 0 18px 8px 18px;
        }

        .customer-title {
            font-size: 16px;
            color: #111;
            padding-bottom: 5px;
            margin-bottom: 6px;
            border-bottom: 2px solid #222;
        }

        .info-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .info-row td {
            padding: 1px 0;
            vertical-align: top;
        }

        .info-lbl {
            font-size: 13px;
            color: #666;
            padding-bottom: 1px;
        }

        .info-val {
            font-size: 13px;
            color: #111;
            background: #f3f3f3;
            border: 1px solid #ddd;
            padding: 5px 10px;
        }

        .footer {
            width: 100%;
            text-align: center;
            padding: 6px 0 0 0;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>

@php
    $cabinKeys    = array_keys($cabinSpecFields);
    $entranceKeys = array_keys($entranceSpecFields);
    $maxRows      = max(count($cabinKeys), count($entranceKeys));

    $iconMap = [
        'fa-lightbulb'      => "\u{2600}",
        'fa-door-closed'    => "\u{25A3}",
        'fa-columns'        => "\u{258C}",
        'fa-square'         => "\u{25A0}",
        'fa-th-list'        => "\u{2630}",
        'fa-grip-lines'     => "\u{2550}",
        'fa-minus'          => "\u{2500}",
        'fa-door-open'      => "\u{25A2}",
        'fa-border-style'   => "\u{25A1}",
        'fa-grip-vertical'  => "\u{2503}",
    ];
@endphp

<table class="wrapper" cellpadding="0" cellspacing="0">
    <tr>
        <!-- ====== 左側深色面板 ====== -->
        <td class="left-panel">
            <div class="left-header">
                <div class="left-header-text">規格 ({{ $order->series_model ?? '' }} 系列)</div>
            </div>

            <table class="spec-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th colspan="2" class="col-divider">車廂</th>
                        <th colspan="2">出入口</th>
                    </tr>
                </thead>
                <tbody>
                    @for ($i = 0; $i < $maxRows; $i++)
                        <tr>
                            @if ($i < count($cabinKeys))
                                @php
                                    $cKey   = $cabinKeys[$i];
                                    $cField = $cabinSpecFields[$cKey];
                                    $cVal   = $order->cabin_specs[$cKey] ?? null;
                                    $cIcon  = $iconMap[$cField['icon']] ?? "\u{25CF}";
                                @endphp
                                <td class="spec-icon-td">
                                    <span class="icon-symbol">{{ $cIcon }}</span>
                                    <span class="icon-label">{{ $cField['label'] }}</span>
                                </td>
                                <td class="col-divider">
                                    @if ($cVal)
                                        @if (is_array($cVal))
                                            {!! implode('<br>', array_map('e', $cVal)) !!}
                                        @else
                                            {!! nl2br(e($cVal)) !!}
                                        @endif
                                    @else
                                        <span class="spec-empty">—</span>
                                    @endif
                                </td>
                            @else
                                <td class="col-divider" colspan="2"></td>
                            @endif

                            @if ($i < count($entranceKeys))
                                @php
                                    $eKey   = $entranceKeys[$i];
                                    $eField = $entranceSpecFields[$eKey];
                                    $eVal   = $order->entrance_specs[$eKey] ?? null;
                                    $eIcon  = $iconMap[$eField['icon']] ?? "\u{25CF}";
                                @endphp
                                <td class="spec-icon-td">
                                    <span class="icon-symbol">{{ $eIcon }}</span>
                                    <span class="icon-label">{{ $eField['label'] }}</span>
                                </td>
                                <td>
                                    @if ($eVal)
                                        @if (is_array($eVal))
                                            {!! implode('<br>', array_map('e', $eVal)) !!}
                                        @else
                                            {!! nl2br(e($eVal)) !!}
                                        @endif
                                    @else
                                        <span class="spec-empty">—</span>
                                    @endif
                                </td>
                            @else
                                <td colspan="2"></td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
        </td>

        <!-- ====== 右側白色面板 ====== -->
        <td class="right-panel">
            <div class="right-header">
                <div class="right-header-text">ELEVATOR STYLE</div>
            </div>

            <div class="image-box">
                @if ($order->elevator_image && $elevatorImageBase64)
                    <img src="{{ $elevatorImageBase64 }}" alt="電梯渲染圖" />
                @else
                    <div class="no-image">（無渲染圖）</div>
                @endif
            </div>

            <div class="customer-section">
                <div class="customer-title">客戶訂車資料</div>

                <table class="info-row"><tr>
                    <td style="width:50%;padding-right:5px;">
                        <div class="info-lbl">客戶名稱</div>
                        <div class="info-val">{{ $order->customer_name ?: '—' }}</div>
                    </td>
                    <td style="width:50%;padding-left:5px;">
                        <div class="info-lbl">專案名稱</div>
                        <div class="info-val">{{ $order->project_name ?: '—' }}</div>
                    </td>
                </tr></table>

                <table class="info-row"><tr>
                    <td>
                        <div class="info-lbl">施工地點</div>
                        <div class="info-val">{{ $order->construction_location ?: '—' }}</div>
                    </td>
                </tr></table>

                <table class="info-row"><tr>
                    <td style="width:50%;padding-right:5px;">
                        <div class="info-lbl">客戶窗口姓名</div>
                        <div class="info-val">{{ $order->customer_contact_name ?: '—' }}</div>
                    </td>
                    <td style="width:50%;padding-left:5px;">
                        <div class="info-lbl">客戶窗口信箱</div>
                        <div class="info-val">{{ $order->customer_contact_email ?: '—' }}</div>
                    </td>
                </tr></table>

                <table class="info-row"><tr>
                    <td style="width:50%;padding-right:5px;">
                        <div class="info-lbl">業務人員姓名</div>
                        <div class="info-val">{{ $order->sales_name ?: '—' }}</div>
                    </td>
                    <td style="width:50%;padding-left:5px;">
                        <div class="info-lbl">業務人員信箱</div>
                        <div class="info-val">{{ $order->sales_email ?: '—' }}</div>
                    </td>
                </tr></table>

                <table class="info-row"><tr>
                    <td>
                        <div class="info-lbl">業務連絡電話</div>
                        <div class="info-val">{{ $order->sales_phone ?: '—' }}</div>
                    </td>
                </tr></table>

                @if ($order->note)
                    <table class="info-row"><tr>
                        <td>
                            <div class="info-lbl">備註</div>
                            <div class="info-val">{!! nl2br(e($order->note)) !!}</div>
                        </td>
                    </tr></table>
                @endif
            </div>
        </td>
    </tr>
</table>

</body>
</html>
