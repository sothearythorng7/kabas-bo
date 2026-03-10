<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Ubuntu:wght@400;500;700&display=swap');
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Ubuntu, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 30px 15px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    {{-- Header --}}
                    <tr>
                        <td align="center" style="background-color: #ffffff; padding: 30px 40px; border-radius: 8px 8px 0 0; border-bottom: 3px solid #2D7A3E;">
                            <a href="https://www.kabasconceptstore.com">
                                <img src="https://www.kabasconceptstore.com/images/kabas_concept_store_logo.png" alt="Kabas Concept Store" style="max-width: 200px; height: auto;">
                            </a>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="background-color: #ffffff; padding: 40px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color: #212529; padding: 30px 40px; border-radius: 0 0 8px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="color: #adb5bd; font-size: 13px; line-height: 20px;">
                                        <p style="margin: 0 0 8px;">Kabas Concept Store</p>
                                        <p style="margin: 0 0 8px;">
                                            <a href="https://www.kabasconceptstore.com" style="color: #5FAE51; text-decoration: none;">www.kabasconceptstore.com</a>
                                        </p>
                                        <p style="margin: 0; color: #6c757d; font-size: 11px;">
                                            This email was sent to you because you placed an order on our website.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
