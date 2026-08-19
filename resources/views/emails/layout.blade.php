<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $subject ?? 'QuizCore' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        /* ---------- RESET & BASE ---------- */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
        }
        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }
        div[style*="margin: 16px 0"] {
            margin: 0 !important;
        }
        table, td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
        }
        table {
            border-spacing: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            margin: 0 auto !important;
        }
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }
        a {
            text-decoration: none;
        }
        p {
            margin: 0;
            padding: 0;
        }

        /* ---------- TYPOGRAPHY ---------- */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #334155;
            background-color: #f1f5f9;
        }

        /* ---------- LAYOUT ---------- */
        .email-wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding: 24px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* ---------- HEADER ---------- */
        .email-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 32px 40px;
            text-align: center;
        }
        .brand-name {
            font-size: 24px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .brand-tagline {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ---------- BODY ---------- */
        .email-body {
            padding: 36px 40px 28px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 12px;
        }
        .message {
            color: #475569;
            margin-bottom: 20px;
        }
        .message p {
            margin-bottom: 10px;
        }
        .message p:last-child {
            margin-bottom: 0;
        }

        /* ---------- CARD / CONTAINER ---------- */
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 20px 0;
        }
        .info-card .label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 4px;
        }
        .info-card .value {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            word-break: break-all;
        }
        .info-card .value + .label {
            margin-top: 16px;
        }

        /* ---------- OTP / CODE ---------- */
        .code-box {
            background-color: #f0f9ff;
            border: 2px dashed #38bdf8;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0284c7;
            margin-bottom: 8px;
        }
        .code-value {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #0c4a6e;
            font-family: 'Courier New', Courier, monospace;
        }

        /* ---------- BUTTON ---------- */
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            margin: 8px 0;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }

        /* ---------- FOOTER ---------- */
        .email-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 40px;
            text-align: center;
        }
        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }
        .footer-text a {
            color: #64748b;
            text-decoration: underline;
        }

        /* ---------- RESPONSIVE ---------- */
        @media only screen and (max-width: 620px) {
            .email-wrapper {
                padding: 12px 0;
            }
            .email-container {
                border-radius: 0;
                border-left: none;
                border-right: none;
            }
            .email-header {
                padding: 24px 20px;
            }
            .email-body {
                padding: 24px 20px 20px;
            }
            .email-footer {
                padding: 16px 20px;
            }
            .code-value {
                font-size: 26px;
                letter-spacing: 6px;
            }
        }
        @media only screen and (max-width: 480px) {
            .email-body {
                padding: 20px 16px 16px;
            }
            .info-card {
                padding: 16px;
            }
            .code-value {
                font-size: 22px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size:15px; line-height:1.6; color:#334155;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <!--[if mso]>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center">
                    <tr>
                        <td>
                <![endif]-->

                <!-- EMAIL CONTAINER -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="background:linear-gradient(135deg, #1e293b 0%, #334155 100%); padding:32px 40px; text-align:center;">
                            <h1 style="margin:0; font-size:24px; font-weight:700; color:#ffffff; letter-spacing:0.5px;">QuizCore</h1>
                            <p style="margin:4px 0 0; font-size:13px; color:#94a3b8; letter-spacing:1px; text-transform:uppercase;">Quiz Management Software</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 28px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc; border-top:1px solid #e2e8f0; padding:20px 40px; text-align:center;">
                            <p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.5;">
                                &copy; {{ date('Y') }} QuizCore &middot; Quiz Management Software<br>
                                Need help? Contact <a href="mailto:support@quizcore.com" style="color:#64748b; text-decoration:underline;">support@quizcore.com</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <!-- END EMAIL CONTAINER -->

                <!--[if mso]>
                        </td>
                    </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>