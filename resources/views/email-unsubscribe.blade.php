<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        p {
            color: #6b7280;
            margin-bottom: 8px;
        }
        .email {
            font-weight: 600;
            color: #111827;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✓</div>
        <h1>Successfully Unsubscribed</h1>
        <p>You have been unsubscribed from our email list.</p>
        <p class="email">{{ $email }}</p>
        <p style="font-size: 14px; margin-top: 20px;">You will no longer receive marketing emails from us.</p>
    </div>
</body>
</html>

