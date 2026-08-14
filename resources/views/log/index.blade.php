@extends('layouts.app')


@section('content')
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Logs</h1>
            @if(session('status'))
                <div class="alert alert-success py-2 px-3 mb-0">{{ session('status') }}</div>
            @endif
        </div>

        <div class="row">
            <div class="col-lg-3 mb-3">
                <div class="card">
                    <div class="card-header">Log List</div>
                    <div class="list-group list-group-flush">
                        @foreach($logs as $key => $item)
                            <a
                                    class="list-group-item list-group-item-action {{ $activeKey === $key ? 'active' : '' }}"
                                    href="{{ route('admin.logs.index', ['key' => $key]) }}"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="mt-3">
                    <form method="POST" action="{{ route('admin.logs.clear', $activeKey) }}"
                          onsubmit="return confirm('Are you sure you want to clear this log?');">
                        @csrf
                        <button class="btn btn-danger w-100">
                            Clear Selected Log
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-9 mb-3">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Selected:</strong> {{ $logs[$activeKey]['label'] ?? $activeKey }}
                        </div>
                        <button class="btn btn-sm btn-outline-primary" id="btnRefresh">Refresh</button>
                    </div>

                    <div class="card-body">
                        <pre id="logBox" style="height: 70vh; overflow:auto; background:#0b1020; color:#e8e8e8; padding:12px; border-radius:10px; font-size:12px; white-space:pre-wrap;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const key = @json($activeKey);
            const logBox = document.getElementById('logBox');
            const btnRefresh = document.getElementById('btnRefresh');

            async function loadLog() {
                logBox.textContent = 'Loading...';
                try {
                    const res = await fetch(@json(route('admin.logs.view', $activeKey)));
                    const data = await res.json();
                    logBox.textContent = data.content || data.message || 'Empty';
                    logBox.scrollTop = logBox.scrollHeight;
                } catch (e) {
                    logBox.textContent = 'Error loading log';
                }
            }

            btnRefresh.addEventListener('click', loadLog);

            loadLog();
            setInterval(loadLog, 5000);
        })();
    </script>
@endsection
