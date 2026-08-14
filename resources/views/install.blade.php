<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Server Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Server Panel Installation</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('install.post') }}" method="POST">
                            @csrf

                            <h4 class="mb-3">Database Configuration</h4>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="db_port" class="form-label">Database Port</label>
                                    <input type="text" class="form-control" id="db_port" name="db_port" value="{{ old('db_port', '3306') }}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="db_database" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_database" name="db_database" value="{{ old('db_database', 'server_panel') }}" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="db_username" class="form-label">Database Username</label>
                                    <input type="text" class="form-control" id="db_username" name="db_username" value="{{ old('db_username', 'root') }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="db_password" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_password" name="db_password">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h4 class="mb-3">Admin Account</h4>
                            <div class="mb-3">
                                <label for="admin_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">Install</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
