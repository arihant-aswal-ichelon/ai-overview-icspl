@extends('layouts.page-app')
@section("content")

<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">History Log List</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">History Log</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Keyword</th>
                            <th scope="col">Total Logs</th>
                            <th scope="col">AIO Status</th>
                            <th scope="col">Search Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($keywordPlanners as $index => $planner)
                        @php
                            $plannerLogs = $allLogs->get($planner->planner_id, collect());
                            $keywordLabel = $plannerLogs->first()->keyword_r ?? $planner->keyword_p ?? 'Keyword Planner #' . $planner->planner_id;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><b>{{ $planner->keyword_p }}</b> ({{$keywordLabel }})</td>
                            <td>{{ $planner->total_logs }}</td>
                            <td>
                                @if($planner->aio_status == 1)
                                    <span class="badge bg-success">Done</span>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($planner->search_status == 1)
                                    <span class="badge bg-success">Done</span>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($planner->total_logs > 0)
                                    <a href="{{ route('history.log.show', [$dmid, $cpid, $planner->planner_id]) }}"
                                    class="btn btn-sm btn-primary">
                                        Show Logs
                                    </a>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled>No Logs</button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No data available in table</td>
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

</script>
@endsection