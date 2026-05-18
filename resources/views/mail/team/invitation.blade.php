<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 24px auto; color: #222; line-height: 1.55;">
    <h2 style="color:#4338ca;">Zaproszenie do GallopTrans</h2>
    <p>{{ $invitation->organization->name }} zaprasza Cię do dołączenia jako <strong>{{ $invitation->role }}</strong>.</p>
    <p>Kliknij poniższy przycisk, aby utworzyć konto i dołączyć do firmy:</p>
    <p><a href="{{ $acceptUrl }}" style="display:inline-block;background:#4338ca;color:#fff;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;">Akceptuję zaproszenie</a></p>
    <p style="font-size:12px;color:#888;">Link jest ważny do {{ $invitation->expires_at->format('Y-m-d H:i') }}. Jeśli nie spodziewasz się tego zaproszenia, zignoruj tę wiadomość.</p>
</body></html>
