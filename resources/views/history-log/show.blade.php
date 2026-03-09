@extends('layouts.page-app')

@section('content')
<div class="page-content">
    <div class="container-fluid">

        {{-- Page Header --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">History Log Detail</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('history.log', [$dmid, $cpid]) }}">History Logs</a>
                            </li>
                            <li class="breadcrumb-item active">
                                {{ $keywordPlanner->keyword_p ?? 'Detail' }}
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logs Table --}}
        <div class="card shadow-sm">
            <div class="card-body">
                    <table id="scroll-horizontal" class="table nowrap align-middle" style="width: 100%;">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">#</th>
                                <th scope="col">Keyword</th>
                                <th scope="col">AIO Status</th>
                                <th scope="col">Search Status</th>
                                <th scope="col">Created At</th>
                                <th scope="col" class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allLogs as $index => $log)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $log->keyword_p ?? '—' }}</strong> {{ $log->keyword_r ?? '—' }}</td>
                                <td>
                                    @if($log->aio_status == 1)
                                        <span class="badge bg-success">Done</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->search_status == 1)
                                        <span class="badge bg-success">Done</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, H:i') }}
                                </td>
                                <td class="text-end pe-4">
                                    <a target="_blank" href="{{ route('extracted-aio-result', [$log->keyword_planner_id, $log->id]) }}"
                                       class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No history logs found for this keyword planner.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>

    </div>
</div>
@endsection

@section('jscontent')
<script>
    // No JS needed — table is server-rendered, navigation via plain links.
</script>
@endsection