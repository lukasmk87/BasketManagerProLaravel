<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <style>
        /* Minimal error page styles */
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
            background-color: #f8fafc;
            color: #636b6f;
        }
        .container { 
            text-align: center; 
            max-width: 400px;
            padding: 20px;
        }
        .code { 
            font-size: 72px; 
            font-weight: bold; 
            color: #e3342f; 
            margin-bottom: 20px;
        }
        .message { 
            font-size: 18px; 
            color: #636b6f; 
            line-height: 1.5;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #3490dc;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">@yield('code')</div>
        <div class="message">@yield('message')</div>
        <a href="{{ url('/') }}" class="back-link">← Zurück zur Startseite</a>
    </div>
</body>
</html>