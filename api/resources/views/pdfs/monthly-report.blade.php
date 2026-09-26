<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Performance Report{{ $report['period'] ? ' — '.$report['period'] : '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .page {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20mm 15mm;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }
        .header .period {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 22px 0 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        table.summary {
            width: 100%;
            border-collapse: collapse;
        }
        table.summary td {
            padding: 8px 6px;
            border-bottom: 1px solid #ddd;
        }
        table.summary td.label {
            width: 45%;
            color: #555;
        }
        table.summary td.value {
            text-align: right;
            font-weight: bold;
        }
        /* The one number the report exists for. Kept visually distinct from the
           components above it so a loss is not read as a gain at a glance. */
        table.summary tr.profit td {
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
            font-size: 15px;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        table.summary tr.profit td.value.negative {
            color: #b42318;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
        }
        table.grid thead th {
            background: #333;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.grid thead th.num,
        table.grid tbody td.num {
            text-align: right;
        }
        table.grid tbody td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        table.grid tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .missing {
            color: #999;
            font-style: italic;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>{{ $report['business_name'] }}</h1>
            @if ($report['period'])
                <div class="period">{{ $report['period'] }}</div>
            @endif
            <p>Monthly Performance Report</p>
        </div>

        <h2>Summary</h2>
        <table class="summary">
            @foreach ([
                'sales' => 'Total sales',
                'purchases' => 'Total purchases',
                'expenses' => 'Total expenses',
            ] as $key => $label)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td class="value">
                        @if ($report['figures'][$key] === null)
                            <span class="missing">not recorded</span>
                        @else
                            {{ $report['currency'] }} {{ number_format($report['figures'][$key], 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- Profit is shown as reported, never recomputed here. If the trigger's
                 arithmetic and this layout ever disagreed, a recomputed figure would
                 quietly paper over the disagreement instead of showing it. --}}
            <tr class="profit">
                <td class="label">Profit / loss</td>
                <td class="value{{ ($report['figures']['profit_loss'] ?? 0) < 0 ? ' negative' : '' }}">
                    @if ($report['figures']['profit_loss'] === null)
                        <span class="missing">not recorded</span>
                    @else
                        {{ $report['currency'] }} {{ number_format(abs($report['figures']['profit_loss']), 2) }}
                        @if ($report['figures']['profit_loss'] < 0)
                            <span style="font-size:10px;">(loss)</span>
                        @endif
                    @endif
                </td>
            </tr>
        </table>

        <h2>Activity</h2>
        <table class="summary">
            @foreach ([
                'sales' => 'Sales recorded',
                'purchases' => 'Purchases recorded',
            ] as $key => $label)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td class="value">
                        @if ($report['counts'][$key] === null)
                            <span class="missing">not recorded</span>
                        @else
                            {{ number_format($report['counts'][$key]) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>

        @if (count($report['branches']))
            <h2>By branch</h2>
            <table class="grid">
                <thead>
                    <tr>
                        <th>Branch</th>
                        <th class="num">Sales</th>
                        <th class="num">Purchases</th>
                        <th class="num">Expenses</th>
                        <th class="num">Profit / loss</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['branches'] as $branch)
                        <tr>
                            <td>{{ $branch['name'] }}</td>
                            @foreach (['sales', 'purchases', 'expenses', 'profit_loss'] as $key)
                                <td class="num">
                                    @if (($branch[$key] ?? null) === null)
                                        <span class="missing">—</span>
                                    @else
                                        {{ number_format(abs($branch[$key]), 2) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="footer">
            {{ $report['business_name'] }}@if ($report['period']) · {{ $report['period'] }}@endif
            · Generated {{ now()->format('F j, Y') }} · Figures in {{ $report['currency'] }}
        </div>
    </div>
</body>
</html>
