<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>New Claims Batch Ready for Processing</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 30px 40px;">
                            <h2 style="margin: 0 0 20px 0; color: #1f2937; font-size: 24px; font-weight: 600; line-height: 1.2;">
                                New Claims Batch Ready for Processing
                            </h2>
                            
                            <p style="margin: 0 0 15px 0; color: #374151; font-size: 16px; line-height: 1.5;">
                                Dear {{ $batch->insurer->name }},
                            </p>
                            
                            <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.5;">
                                A new batch of claims has been created and is ready for processing:
                            </p>
                            
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f9fafb; border-radius: 6px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Batch Identifier:</strong> {{ $batch->identifier }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Provider:</strong> {{ $batch->provider_name }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Batch Date:</strong> {{ $batch->batch_date->format('F j, Y') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Number of Claims:</strong> {{ $batch->claim_count }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Total Amount:</strong> ${{ number_format($batch->total_amount, 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #1f2937; font-size: 14px; line-height: 1.5;">
                                                    <strong style="color: #111827;">Estimated Processing Cost:</strong> ${{ number_format($batch->estimated_cost, 2) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 20px 0 0 0; color: #374151; font-size: 16px; line-height: 1.5;">
                                Please process this batch according to your standard procedures.
                            </p>
                            
                            <p style="margin: 30px 0 0 0; color: #374151; font-size: 16px; line-height: 1.5;">
                                Best regards,<br>
                                <strong style="color: #111827;">Claims Processing System</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

