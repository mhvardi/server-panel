@extends('layouts.app')

@section('title', 'New Cron Job')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 text-gray-800 mb-1">New Cron Job</h2>
            <div class="text-muted small">Create a scheduled task in the panel-managed cron file.</div>
        </div>
        <a href="{{ route('cronjobs.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li><pre class="mb-0">{{ $error }}</pre></li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><i class="fas fa-clock me-2"></i>Cron Job Details</div>
        <div class="card-body">
            <form method="POST" action="{{ route('cronjobs.store') }}">
                @csrf
                @include('cronjobs._form', ['config' => $config])

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
