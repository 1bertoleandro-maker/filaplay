<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family: -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:480px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background-color:#0f1115; padding:24px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color:#35c759; width:36px; height:36px; border-radius:10px; text-align:center; vertical-align:middle;">
                                        <span style="font-size:18px;">🎾</span>
                                    </td>
                                    <td style="padding-left:10px; font-size:18px; font-weight:800; color:#ffffff;">
                                        Fila<span style="color:#35c759;">Play</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @if ($nomeClube)
                                <p style="margin:0 0 8px; font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#35c759;">{{ $nomeClube }}</p>
                            @endif
                            <h1 style="margin:0 0 16px; font-size:20px; color:#111827;">{{ $tituloAssunto }}</h1>
                            <p style="margin:0; font-size:16px; line-height:1.6; color:#374151; white-space:pre-line;">{{ $corpo }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 28px;">
                            <p style="margin:0; font-size:12px; color:#9ca3af;">
                                Este e-mail foi enviado automaticamente pela plataforma FilaPlay (gotreino.com). Se você não esperava esta mensagem, pode ignorá-la.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
