<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Verification - Server Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .login-header {
            background-color: #212529;
            color: white;
            padding: 20px;
            text-align: center;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <h3 class="mb-0">Two-Factor Authentication</h3>
            <small>Please enter your code</small>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('2fa.verify.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input type="text" class="form-control" id="code" name="code" required autofocus autocomplete="off">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Verify</button>
                </div>
            </form>

            <div class="mt-3 text-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-link text-muted">Logout</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
