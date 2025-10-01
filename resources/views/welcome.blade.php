<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'WP-POS') }}</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                text-align: center;
                max-width: 600px;
            }
            .logo {
                font-size: 48px;
                margin-bottom: 20px;
            }
            h1 {
                color: #1f2937;
                margin-bottom: 10px;
                font-size: 32px;
            }
            p {
                color: #6b7280;
                margin-bottom: 30px;
                font-size: 16px;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: #3b82f6;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 10px;
                transition: background 0.3s;
            }
            .btn:hover {
                background: #2563eb;
            }
            .btn-success {
                background: #10b981;
            }
            .btn-success:hover {
                background: #059669;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="logo">🚀</div>
            <h1>Welcome to WP-POS</h1>
            <p>Complete Point of Sale System - Ready to Install!</p>
            
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-success">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn">Register</a>
                    @endif
                @endauth
            @else
                <a href="{{ url('/install') }}" class="btn btn-success">Start Installation</a>
            @endif
        </div>
    </body>
</html>
