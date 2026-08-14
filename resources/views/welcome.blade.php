<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Server Panel</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .welcome-container {
                text-align: center;
            }
            .btn-light-custom {
                background-color: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.4);
                color: white;
                padding: 10px 30px;
                font-size: 1.2rem;
                transition: all 0.3s;
            }
            .btn-light-custom:hover {
                background-color: white;
                color: #764ba2;
            }
        </style>
    </head>
    <body>
        <div class="welcome-container">
            <h1 class="display-1 fw-bold mb-4">Server Panel</h1>
            <p class="lead mb-5">Manage your server with ease and style.</p>

            @if (Route::has('login'))
                <div>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-light-custom rounded-pill">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-light-custom rounded-pill">Login</a>
                    @endauth
                </div>
            @endif
        </div>
    </body>
</html>
