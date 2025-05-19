<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق الخاص بك</title>
    <style>
        /* Reset and base styles */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f4f4f4;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            direction: rtl;
        }

        /* Added direction: rtl here for body */

        /* Main wrapper table */
        .email-wrapper {
            width: 100%;
            max-width: 600px;
            /* Adjust as needed */
            margin: 0 auto;
            background-color: #ffffff;
            border-collapse: collapse;
        }

        /* Header */
        .header {
            background-color: #E47F46;
            /* Your brand's primary color */
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        /* Content area */
        .content {
            padding: 30px;
            text-align: right;
            /* For RTL */
            line-height: 1.6;
            color: #333333;
        }

        .content p {
            margin-bottom: 15px;
        }

        .otp-code {
            background-color: #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        .otp-code span {
            font-size: 32px;
            font-weight: bold;
            color: #0056b3;
            /* Darker shade of primary or a contrast color */
            letter-spacing: 4px;
            display: block;
            /* Ensure it takes full width for centering */
        }

        .otp-info {
            font-size: 0.9em;
            color: #6c757d;
        }

        /* Button (Example) - Removed from display for now as per your previous diff */
        /*
        .button-td, .button-a {
            text-align: center;
            border-radius: 5px;
        }
        .button-a {
            background: #E47F46;
            border: 15px solid #E47F46;
            padding: 0;
            color: #ffffff;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        */

        /* Footer */
        .footer {
            background-color: #eeeeee;
            color: #777777;
            padding: 20px;
            text-align: center;
            font-size: 0.8em;
        }

        .footer a {
            color: #E47F46;
            text-decoration: none;
        }

        /* LTR text helper */
        .ltr-text {
            direction: ltr;
            unicode-bidi: embed;
            /* Or try isolate if embed doesn't fully work */
            display: inline-block;
        }

    </style>
</head>
<body>
    <table role="presentation" class="email-wrapper" cellspacing="0" cellpadding="0" border="0" align="center">
        <!-- Header -->
        <tr>
            <td class="header">
                <h1>عالباب</h1>
            </td>
        </tr>

        <!-- Content -->
        <tr>
            <td class="content">
                <p>مرحبًا <span class="ltr-text">{{ $userName }}</span>،</p>
                <p>شكرًا لك على التسجيل في عالباب. لتأكيد عنوان بريدك الإلكتروني وتفعيل حسابك، يرجى استخدام رمز التحقق التالي:</p>

                <div class="otp-code">
                    <span><span class="ltr-text">{{ $otp }}</span></span>
                </div>

                <p class="otp-info">هذا الرمز صالح لمدة <span class="ltr-text">10</span> دقائق فقط.</p>
                <p>إذا لم تقم بطلب هذا الرمز، فلا داعي لاتخاذ أي إجراء. يمكنك تجاهل هذا البريد الإلكتروني بأمان.</p>


                <p style="margin-top: 30px;">مع أطيب التحيات،<br>فريق عالباب</p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td class="footer">
                <p>&copy; <span class="ltr-text">{{ date('Y') }}</span> عالباب . جميع الحقوق محفوظة.</p>
            </td>
        </tr>
    </table>
</body>
</html>
