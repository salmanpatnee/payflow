<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $receiptData['receipt_number'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #4F46E5;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 32px;
            color: #4F46E5;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            color: #666;
        }

        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            background-color: #F3F4F6;
            padding: 20px;
            border-radius: 8px;
        }

        .receipt-info-section {
            flex: 1;
        }

        .receipt-info-section h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .receipt-info-section p {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .receipt-number {
            font-size: 18px;
            font-weight: bold;
            color: #4F46E5;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead {
            background-color: #4F46E5;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #E5E7EB;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #4F46E5;
        }

        .total-section {
            text-align: right;
            margin-bottom: 40px;
        }

        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 8px 0;
        }

        .total-label {
            width: 150px;
            text-align: right;
            padding-right: 20px;
            font-weight: 600;
        }

        .total-value {
            width: 150px;
            text-align: right;
        }

        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #4F46E5;
            border-top: 2px solid #4F46E5;
            padding-top: 12px;
            margin-top: 12px;
        }

        .transaction-details {
            background-color: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .transaction-details h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #4F46E5;
        }

        .detail-row {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            flex: 1;
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            flex: 2;
            color: #333;
        }

        .footer {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid #E5E7EB;
            color: #666;
            font-size: 12px;
        }

        .footer p {
            margin-bottom: 8px;
        }

        .stamp {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
            color: #10B981;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>PAYMENT RECEIPT</h1>
            <p>{{ config('app.name') }}</p>
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="receipt-info-section">
                <h3>Receipt Number</h3>
                <p class="receipt-number">{{ $receiptData['receipt_number'] }}</p>
            </div>
            <div class="receipt-info-section">
                <h3>Transaction Date</h3>
                <p>{{ \Carbon\Carbon::parse($receiptData['transaction_date'])->format('F j, Y') }}</p>
                <p>{{ \Carbon\Carbon::parse($receiptData['transaction_date'])->format('g:i A') }}</p>
            </div>
            <div class="receipt-info-section">
                <h3>Payment Method</h3>
                <p>{{ ucfirst($receiptData['payment_method'] ?? 'Card') }}</p>
            </div>
        </div>

        <!-- Payment Collection Info -->
        @if(!empty($receiptData['payment_collection']))
        <div class="transaction-details">
            <h3>Payment Details</h3>
            <div class="detail-row">
                <div class="detail-label">Collection</div>
                <div class="detail-value">{{ $receiptData['payment_collection']['name'] }}</div>
            </div>
            @if(!empty($receiptData['payment_collection']['description']))
            <div class="detail-row">
                <div class="detail-label">Description</div>
                <div class="detail-value">{{ $receiptData['payment_collection']['description'] }}</div>
            </div>
            @endif
        </div>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>{{ $receiptData['payment_item']['name'] }}</strong></td>
                    <td>{{ $receiptData['payment_item']['description'] ?? '-' }}</td>
                    <td style="text-align: center;">{{ $receiptData['payment_item']['quantity'] ?? 1 }}</td>
                    <td style="text-align: right;">
                        {{ strtoupper($receiptData['payment_item']['currency']) }}
                        {{ number_format($receiptData['payment_item']['price'], 2) }}
                    </td>
                    <td style="text-align: right;">
                        {{ strtoupper($receiptData['payment_item']['currency']) }}
                        {{ number_format($receiptData['payment_item']['amount'], 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <div class="total-label">Subtotal:</div>
                <div class="total-value">
                    {{ strtoupper($receiptData['payment_item']['currency']) }}
                    {{ number_format($receiptData['payment_item']['amount'], 2) }}
                </div>
            </div>
            <div class="total-row grand-total">
                <div class="total-label">Total Paid:</div>
                <div class="total-value">
                    {{ strtoupper($receiptData['payment_item']['currency']) }}
                    {{ number_format($receiptData['payment_item']['amount'], 2) }}
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="transaction-details">
            <h3>Transaction Information</h3>
            <div class="detail-row">
                <div class="detail-label">Transaction ID</div>
                <div class="detail-value">#{{ $receiptData['transaction_id'] }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Stripe Event ID</div>
                <div class="detail-value">{{ $receiptData['stripe_event_id'] }}</div>
            </div>
            @if(!empty($receiptData['payment_item']['paid_at']))
            <div class="detail-row">
                <div class="detail-label">Payment Completed</div>
                <div class="detail-value">{{ \Carbon\Carbon::parse($receiptData['payment_item']['paid_at'])->format('F j, Y g:i A') }}</div>
            </div>
            @endif
        </div>

        <!-- Paid Stamp -->
        <div class="stamp">
            ✓ PAID IN FULL
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official payment receipt.</p>
            <p>For questions about this receipt, please contact {{ config('mail.from.address') }}</p>
            <p>Generated on {{ now()->format('F j, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>
