<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $orgName }}</title>
    <!--[if mso]>
    <noscript>
        <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img  { border: 0; outline: none; text-decoration: none; }
        body { margin: 0 !important; padding: 0 !important; background-color: #f0f4f8; }
        @media only screen and (max-width: 600px) {
            .email-container    { width: 100% !important; }
            .email-body-padding { padding: 24px 20px !important; }
            .otp-text           { font-size: 36px !important; letter-spacing: 8px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f0f4f8; font-family:'Segoe UI',Helvetica,Arial,sans-serif;">

<!-- Hidden inbox preview text -->
<div style="display:none; font-size:1px; color:#f0f4f8; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Your password reset code is: {{ $otp }}. Expires in 10 minutes.
</div>

<!-- Outer wrapper -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:#f0f4f8; padding:40px 16px;">
    <tr>
        <td align="center">

            <!-- ── Email card ── -->
            <table role="presentation" class="email-container" cellpadding="0" cellspacing="0" border="0"
                   style="width:100%; max-width:560px; background:#ffffff; border-radius:20px;
                          overflow:hidden; box-shadow:0 8px 40px rgba(0,0,0,.10);">

                <!-- HEADER -->
                <tr>
                    <td align="center"
                        style="background:#1d4ed8; padding:36px 40px 32px;">

                        <!-- Org initials badge -->
                        <div style="display:inline-block; width:56px; height:56px; border-radius:14px;
                                    background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.35);
                                    font-size:20px; font-weight:800; color:#ffffff; line-height:56px;
                                    text-align:center; margin-bottom:16px;
                                    font-family:'Segoe UI',Arial,sans-serif;">
                            {{ mb_strtoupper(mb_substr($orgName, 0, 2)) }}
                        </div>

                        <p style="margin:0; font-size:20px; font-weight:700; color:#ffffff;
                                  letter-spacing:-0.3px; line-height:1.2;">
                            {{ $orgName }}
                        </p>
                        <p style="margin:6px 0 0; font-size:11px; font-weight:600;
                                  color:rgba(255,255,255,.65); letter-spacing:2px; text-transform:uppercase;">
                            Password Reset
                        </p>
                    </td>
                </tr>

                <!-- BODY -->
                <tr>
                    <td class="email-body-padding" style="padding:40px 44px 32px;">

                        <!-- Greeting -->
                        <p style="margin:0 0 6px; font-size:20px; font-weight:700;
                                  color:#0f172a; line-height:1.3;">
                            {{ $greeting }}
                        </p>
                        <p style="margin:0 0 32px; font-size:15px; color:#475569; line-height:1.65;">
                            {{ $line1 }}
                        </p>

                        <!-- OTP box -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td align="center" style="padding-bottom:12px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td align="center"
                                                style="background:#eff6ff; border:2px solid #bfdbfe;
                                                       border-radius:16px; padding:28px 36px 24px;">

                                                <p style="margin:0 0 10px; font-size:10px; font-weight:700;
                                                          color:#64748b; letter-spacing:3px; text-transform:uppercase;">
                                                    Your Reset Code
                                                </p>

                                                <!-- OTP digits -->
                                                <p class="otp-text"
                                                   style="margin:0; font-size:44px; font-weight:800; color:#1d4ed8;
                                                          letter-spacing:14px; line-height:1;
                                                          font-family:'Courier New',Courier,monospace;">
                                                    {{ $otp }}
                                                </p>

                                                <!-- Expiry badge -->
                                                <p style="margin:14px 0 0; font-size:11px; font-weight:600;
                                                          color:#64748b; background:#e2e8f0; border-radius:99px;
                                                          padding:4px 12px; display:inline-block; line-height:1.5;">
                                                    &#9203; {{ $expires }}
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>

                        <!-- Ignore notice -->
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="margin-top:28px;">
                            <tr>
                                <td style="background:#fafafa; border-left:3px solid #e2e8f0;
                                           border-radius:0 8px 8px 0; padding:14px 16px;">
                                    <p style="margin:0; font-size:13px; color:#94a3b8; line-height:1.6;">
                                        {{ $ignore }}
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <!-- DIVIDER -->
                <tr>
                    <td style="padding:0 44px;">
                        <div style="height:1px; background:#f1f5f9;"></div>
                    </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                    <td align="center" style="padding:24px 44px 32px;">
                        <p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
                            &copy; {{ $year }} <strong style="color:#64748b;">{{ $orgName }}</strong>.
                            All rights reserved.
                        </p>
                        <p style="margin:8px 0 0; font-size:11px; color:#cbd5e1;">
                            This is an automated message &mdash; please do not reply.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
