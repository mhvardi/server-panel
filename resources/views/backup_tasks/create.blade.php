@extends('layouts.app')

@section('title', 'Create Backup Task')

@section('content')
    <div class="container">
        <h1>Create Backup Task</h1>
        <form method="POST" action="{{ route('backup_tasks.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Task Name</label>
                <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Service (Subdomain Folder)</label>
                <select name="service_path" class="form-select" required>
                    @foreach($services as $srv)
                        <option value="{{ $srv }}" {{ old('service_path') === $srv ? 'selected' : '' }}>{{ $srv }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Database Name (optional)</label>
                <input type="text" name="db_name" class="form-control" value="{{ old('db_name') }}">
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="files_enabled" id="files_enabled" class="form-check-input" {{ old('files_enabled', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="files_enabled">Backup Files</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" name="db_enabled" id="db_enabled" class="form-check-input" {{ old('db_enabled') ? 'checked' : '' }}>
                <label class="form-check-label" for="db_enabled">Backup Database</label>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Interval</label>
                    <input type="number" name="interval_value" class="form-control" min="1" value="{{ old('interval_value', 1) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Unit</label>
                    <select name="interval_unit" class="form-control" required>
                        <option value="minute" {{ old('interval_unit')=='minute' ? 'selected' : '' }}>Minute</option>
                        <option value="hour" {{ old('interval_unit')=='hour' ? 'selected' : '' }}>Hour</option>
                        <option value="day" {{ old('interval_unit')=='day' ? 'selected' : '' }}>Day</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Preview</label>
                    <input type="text" class="form-control" id="cron_preview" readonly>
                </div>
            </div>

            <script>
                function buildCronPreview() {
                    const v = parseInt(document.querySelector('[name="interval_value"]').value || '1', 10);
                    const u = document.querySelector('[name="interval_unit"]').value;
                    let cron = '* * * * *';

                    if (u === 'minute') cron = `*/${v} * * * *`;
                    if (u === 'hour')   cron = `0 */${v} * * *`;
                    if (u === 'day')    cron = `0 0 */${v} *`;

                    document.getElementById('cron_preview').value = cron;
                }
                document.addEventListener('DOMContentLoaded', () => {
                    document.querySelector('[name="interval_value"]').addEventListener('input', buildCronPreview);
                    document.querySelector('[name="interval_unit"]').addEventListener('change', buildCronPreview);
                    buildCronPreview();
                });
            </script>
            <div class="mb-3">
                <label class="form-label">Cron Expression (optional)</label>
                <input type="text" name="cron_expression" class="form-control" placeholder="e.g. */6 * * * *" value="{{ old('cron_expression') }}">
                <small class="form-text text-muted">Leave blank if you don't want automatic scheduling.</small>
            </div>

            <hr>
            <div class="mb-3 form-check">
                <input type="checkbox" name="remote_enabled" id="remote_enabled" class="form-check-input" {{ old('remote_enabled') ? 'checked' : '' }}>
                <label class="form-check-label" for="remote_enabled">Enable Remote FTP Upload</label>
            </div>
            <div id="remote_settings" style="{{ old('remote_enabled') ? '' : 'display:none;' }}">
                <div class="mb-3">
                    <label class="form-label">FTP Host</label>
                    <input type="text" name="remote_host" class="form-control" value="{{ old('remote_host') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">FTP User</label>
                    <input type="text" name="remote_user" class="form-control" value="{{ old('remote_user') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">FTP Password</label>
                    <input type="password" name="remote_password" class="form-control" value="{{ old('remote_password') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remote Path</label>
                    <input type="text" name="remote_path" class="form-control" placeholder="/backups" value="{{ old('remote_path') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remote Retention Days</label>
                    <input type="number" name="remote_retention_days" class="form-control" value="{{ old('remote_retention_days', 30) }}">
                </div>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label">Local Retention Days</label>
                <input type="number" name="local_retention_days" class="form-control" value="{{ old('local_retention_days', 7) }}">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('backup_tasks.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <script>
        document.getElementById('remote_enabled').addEventListener('change', function() {
            document.getElementById('remote_settings').style.display = this.checked ? '' : 'none';
        });
    </script>
@endsection