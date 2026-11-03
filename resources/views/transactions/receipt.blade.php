<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt - {{ $transaction->transaction_code }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f8fafc;
            color: #000;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .receipt-card {
            background: #fff;
            max-width: 80mm;
            margin: 0 auto;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; }
        .mb-1 { margin-bottom: 4px; }
        .print-btn {
            display: block;
            margin: 15px auto 0;
            padding: 8px 16px;
            background: #0f172a;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-card { border: none; box-shadow: none; width: 100%; max-width: 80mm; padding: 5px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="text-center">
            <h1 style="font-size: 16px; font-weight: bold;">{{ $receipt['studio_name'] }}</h1>
            <p style="font-size: 10px;">{{ $receipt['studio_tagline'] }}</p>
            <p style="font-size: 10px;">{{ $receipt['studio_contact'] }}</p>
            <p style="font-size: 10px;">{{ $receipt['studio_address'] }}</p>
        </div>

        <div class="divider"></div>

        <div class="mb-1">Code: <strong>{{ $receipt['transaction_code'] }}</strong></div>
        <div class="mb-1">Date: {{ $receipt['date_time'] }}</div>
        <div class="mb-1">Customer: <strong>{{ $receipt['customer_name'] }}</strong></div>
        <div class="mb-1">Phone: {{ $receipt['contact_number'] }}</div>
        <div class="mb-1">Operator: {{ $receipt['operator'] }}</div>

        <div class="divider"></div>

        <div class="flex font-bold mb-1">
            <span>{{ $receipt['package_title'] }}</span>
            <span>{{ $receipt['package_price'] }}</span>
        </div>

        @if(!empty($receipt['addons']))
            @foreach($receipt['addons'] as $addon)
                <div class="flex" style="font-size: 11px; padding-left: 8px; margin-bottom: 2px;">
                    <span>{{ $addon['title'] }} x{{ $addon['qty'] }}</span>
                    <span>{{ $addon['subtotal'] }}</span>
                </div>
            @endforeach
        @endif

        @if($transaction->discount_amount > 0)
            <div class="flex" style="font-size: 11px; padding-left: 8px; color: #dc2626;">
                <span>Discount</span>
                <span>-{{ $receipt['discount_amount'] }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <div class="flex" style="font-size: 14px; font-weight: bold;">
            <span>TOTAL AMOUNT</span>
            <span>{{ $receipt['grand_total'] }}</span>
        </div>

        <div class="flex" style="font-size: 11px; margin-top: 4px;">
            <span>Payment Method:</span>
            <strong>{{ $receipt['payment_method'] }}</strong>
        </div>

        <div class="flex" style="font-size: 11px;">
            <span>Status:</span>
            <strong>{{ $receipt['payment_status'] }}</strong>
        </div>

        <div class="divider"></div>

        <p class="text-center" style="font-size: 10px; margin-top: 8px;">
            {{ $receipt['footer_text'] }}
        </p>

        <button onclick="window.print()" class="print-btn no-print">Print POS Thermal Receipt</button>
    </div>
</body>
</html>

