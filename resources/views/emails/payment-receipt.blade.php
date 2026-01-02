<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
</head>
<body style="font-family: 'Helvetica', 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #4F46E5; padding: 40px 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Payment Received</h1>
                            <p style="color: #E0E7FF; margin: 10px 0 0 0; font-size: 16px;">Thank you for your payment!</p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #333;">
                                Your payment has been successfully processed. Please find your payment receipt attached to this email.
                            </p>

                            <!-- Receipt Summary -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #F3F4F6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <h2 style="margin: 0 0 16px 0; font-size: 18px; color: #4F46E5;">Receipt Summary</h2>

                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="font-weight: 600; color: #666; font-size: 14px;">Receipt Number:</td>
                                                <td style="text-align: right; font-size: 14px; color: #333;">{{ $receiptData['receipt_number'] }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #666; font-size: 14px;">Date:</td>
                                                <td style="text-align: right; font-size: 14px; color: #333;">
                                                    {{ \Carbon\Carbon::parse($receiptData['transaction_date'])->format('F j, Y') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #666; font-size: 14px;">Payment Method:</td>
                                                <td style="text-align: right; font-size: 14px; color: #333;">
                                                    {{ ucfirst($receiptData['payment_method'] ?? 'Card') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600; color: #666; font-size: 14px; padding-top: 12px; border-top: 2px solid #E5E7EB;">Amount Paid:</td>
                                                <td style="text-align: right; font-size: 18px; font-weight: bold; color: #4F46E5; padding-top: 12px; border-top: 2px solid #E5E7EB;">
                                                    {{ strtoupper($receiptData['payment_item']['currency']) }}
                                                    {{ number_format($receiptData['payment_item']['amount'], 2) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Item Details -->
                            <h3 style="margin: 30px 0 16px 0; font-size: 16px; color: #333;">Payment Details</h3>
                            <table width="100%" cellpadding="12" cellspacing="0" style="border: 1px solid #E5E7EB; border-radius: 8px;">
                                <tr>
                                    <td style="background-color: #F9FAFB; font-weight: 600; color: #666; font-size: 14px; border-bottom: 1px solid #E5E7EB;">Item</td>
                                    <td style="background-color: #F9FAFB; text-align: right; font-weight: 600; color: #666; font-size: 14px; border-bottom: 1px solid #E5E7EB;">Amount</td>
                                </tr>
                                <tr>
                                    <td style="font-size: 14px; color: #333;">
                                        <strong>{{ $receiptData['payment_item']['name'] }}</strong>
                                        @if(!empty($receiptData['payment_item']['description']))
                                        <br>
                                        <span style="color: #666; font-size: 13px;">{{ $receiptData['payment_item']['description'] }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right; font-size: 14px; color: #333;">
                                        {{ strtoupper($receiptData['payment_item']['currency']) }}
                                        {{ number_format($receiptData['payment_item']['amount'], 2) }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 30px 0 0 0; font-size: 14px; color: #666; line-height: 1.8;">
                                A detailed PDF receipt is attached to this email. Please keep it for your records.
                            </p>

                            @if(!empty(config('app.support_email')))
                            <p style="margin: 20px 0 0 0; font-size: 14px; color: #666;">
                                If you have any questions about this payment, please contact us at
                                <a href="mailto:{{ config('app.support_email') }}" style="color: #4F46E5; text-decoration: none;">
                                    {{ config('app.support_email') }}
                                </a>
                            </p>
                            @endif
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F9FAFB; padding: 30px; text-align: center; border-top: 1px solid #E5E7EB;">
                            <p style="margin: 0; font-size: 14px; color: #666;">
                                This is an automated email from {{ config('app.name') }}.
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
