<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }
        .receipt {
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
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 11px;
        }
        .receipt-number {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 20px;
            padding: 8px;
            background: #f5f5f5;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            width: 120px;
            color: #555;
        }
        .info-value {
            display: table-cell;
            padding: 3px 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items thead th {
            background: #333;
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        table.items thead th.qty,
        table.items thead th.price,
        table.items thead th.total {
            text-align: right;
        }
        table.items tbody td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        table.items tbody td.qty,
        table.items tbody td.price,
        table.items tbody td.total {
            text-align: right;
        }
        table.items tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .totals {
            float: right;
            width: 250px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 4px 8px;
        }
        .totals .label {
            text-align: left;
            font-weight: bold;
            color: #555;
        }
        .totals .value {
            text-align: right;
        }
        .totals .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 6px;
        }
        .totals .grand-total td {
            padding-top: 6px;
        }
        .payment-details {
            clear: both;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 1px solid #ddd;
        }
        .payment-details .info-grid {
            margin-bottom: 0;
        }
        .thank-you {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #333;
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            color: #999;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>{{ $receipt->businessBranch?->name ?? 'Store' }}</h1>
            <p>{{ $receipt->businessBranch?->address ?? '' }}</p>
        </div>

        <div class="receipt-number">
            Receipt #{{ $receipt->receipt_number }}
        </div>

        <div class="info-grid">
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $receipt->created_at->format('F j, Y g:i A') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Cashier:</span>
                <span class="info-value">{{ $receipt->user?->firstname ?? '' }} {{ $receipt->user?->lastname ?? '' }}</span>
            </div>
            @if($receipt->customer)
            <div class="info-row">
                <span class="info-label">Customer:</span>
                <span class="info-value">{{ $receipt->customer?->user?->firstname ?? $receipt->customer?->name ?? 'N/A' }}</span>
            </div>
            @endif
            @if($receipt->notes)
            <div class="info-row">
                <span class="info-label">Notes:</span>
                <span class="info-value">{{ $receipt->notes }}</span>
            </div>
            @endif
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="qty">Qty</th>
                    <th class="price">Unit Price</th>
                    <th class="price">Discount</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="qty">{{ $item->quantity }}</td>
                    <td class="price">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="price">{{ $item->discount > 0 ? number_format($item->discount, 2) : '-' }}</td>
                    <td class="total">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="clearfix">
            <div class="totals">
                <table>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="value">{{ number_format($receipt->subtotal, 2) }}</td>
                    </tr>
                    @if($receipt->discount > 0)
                    <tr>
                        <td class="label">Discount</td>
                        <td class="value">-{{ number_format($receipt->discount, 2) }}</td>
                    </tr>
                    @endif
                    @if($receipt->tax > 0)
                    <tr>
                        <td class="label">Tax</td>
                        <td class="value">{{ number_format($receipt->tax, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="label">Grand Total</td>
                        <td class="value">{{ number_format($receipt->total, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="payment-details">
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value">{{ ucfirst($receipt->payment_method) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Amount Paid:</span>
                    <span class="info-value">{{ number_format($receipt->amount_paid, 2) }}</span>
                </div>
                @if($receipt->change_given > 0)
                <div class="info-row">
                    <span class="info-label">Change Given:</span>
                    <span class="info-value">{{ number_format($receipt->change_given, 2) }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="thank-you">
            Thank you for your purchase!
        </div>

        <div class="footer">
            Receipt #{{ $receipt->receipt_number }} | Generated on {{ now()->format('F j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
