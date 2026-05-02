<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Contact Form Submission</title>
</head>
<body style="font-family: sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #0d9488;">Nauja žinutė iš kontaktų formos</h2>

    <p><strong>Vardas:</strong> {{ $senderName }}</p>
    <p><strong>El. paštas:</strong> <a href="mailto:{{ $senderEmail }}">{{ $senderEmail }}</a></p>
    <p><strong>Tema:</strong> {{ $emailSubject }}</p>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;" />

    <p style="white-space: pre-wrap;">{{ $body }}</p>
</body>
</html>
