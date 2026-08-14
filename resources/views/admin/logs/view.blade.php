@extends('layouts.app')

@section('title', 'Log Viewer: ' . $channel['label'])

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-gray-800 mb-1">Log Viewer: {{ $channel['label'] }}</h2>
            <div class="text-muted small"><code class="font-monospace">{{ $channel['path'] }}</code></div>
        </div>
        <div>
            <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-alt me-2"></i>Log Content</span>
            <div class="btn-group">
                <a href="{{ route('admin.logs.view', $key) }}" class="btn btn-sm btn-outline-secondary" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </a>
                <a href="{{ route('admin.logs.download', $key) }}" class="btn btn-sm btn-outline-secondary" title="Download Full Log">
                    <i class="fas fa-download"></i>
                </a>
                <form action="{{ route('admin.logs.clear', $key) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to clear this log file? This action cannot be undone.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Clear Log">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body bg-dark text-white font-monospace p-3" style="max-height: 600px; overflow-y: auto;">
            <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{!! e($content) !!}</pre>
        </div>
    </div>
</div>
@endsection
