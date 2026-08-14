@extends('layouts.app')

@section('title', 'Edit Backup Task')

@section('content')
    <div class="container">
        <h1>Edit Backup Task</h1>
        <form method="POST" action="{{ route('backup_tasks.update', $task->id) }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label class="form-label">Task Name</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name', $task->name) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Service (Subdomain Folder)</label>
                <select name="service_path" class="form-select" required>
                    @foreach($services as $srv)
                        <option value="{{ $srv }}" {{ old('service_path', $task->service_path) === $srv ? 'selected' : '' }}>{{ $srv }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Name (optional)</label>
                <input type="text" name="db_name" class="form-control" value="{{ old('db_name', $task->db_name) }}">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="files_enabled" id="files_enabled" class="form-check-input" {{ old('files_enabled', $task->files_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="files_enabled">Backup Files</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="db_enabled" id="db_enabled" class="form-check-input" {{ old('db_enabled', $task->db_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="db_enabled">Backup Database</label>
            </div>
            <div class="mb-3">
                <label class="form-label">Cron Expression (optional)</label>
                <input type="text" name="cron_expression" class="form-control" value="{{ old('cron_expression', $task->cron_expression) }}">
            </div>
            <hr>
            <div class="mb-3 form-check">
                <input type="checkbox" name="remote_enabled" id="remote_enabled" class="form-check-input" {{ old('remote_enabled', $task->remote_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="remote_enabled">Enable Remote FTP Upload</label>
            </div>
            <div id="remote_settings" style="{{ old('remote_enabled', $task->remote_enabled) ? '' : 'display:none;' }}">
                <div class="mb-3">
                    <label class="form-label">FTP Host</label>
                    <input type="text" name="remote_host" class="form-control" value="{{ old('remote_host', $task->remote_host) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">FTP User</label>
                    <input type="text" name="remote_user" class="form-control" value="{{ old('remote_user', $task->remote_user) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">FTP Password</label>
                    <input type="password" name="remote_password" class="form-control" value="{{ old('remote_password', $task->remote_password) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remote Path</label>
                    <input type="text" name="remote_path" class="form-control" value="{{ old('remote_path', $task->remote_path) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remote Retention Days</label>
                    <input type="number" name="remote_retention_days" class="form-control" value="{{ old('remote_retention_days', $task->remote_retention_days) }}">
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label">Local Retention Days</label>
                <input type="number" name="local_retention_days" class="form-control" value="{{ old('local_retention_days', $task->local_retention_days) }}">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('backup_tasks.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script>
        document.getElementById('remote_enabled').addEventListener('change', function() {
            document.getElementById('remote_settings').style.display = this.checked ? '' : 'none';
        });
    </script>
@endsection