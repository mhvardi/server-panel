@extends('layouts.app')

@section('title', 'Backup Log')

@section('content')
    <div class="container">
        <h1>Backup Log: {{ $task->name }}</h1>
        <pre style="background:#f8f9fa; padding:1rem; border:1px solid #dee2e6; white-space: pre-wrap;">{{ $log }}</pre>
        <a href="{{ route('backup_tasks.index') }}" class="btn btn-secondary">Back</a>
    </div>
@endsection