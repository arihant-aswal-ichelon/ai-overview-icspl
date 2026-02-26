@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">AI Overview Result for: {{ $keyword_planner->keyword_p }}</h4>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        @if($ai_overview)
                            <h5>AI Overview</h5>
                            @if($ai_overview->markdown)
                                <div class="p-3 bg-light rounded border">
                                    {!! Str::markdown($ai_overview->markdown) !!}
                                </div>
                            @endif
                            
                            @if($ai_overview->text_blocks)
                                <h6 class="mt-4">Text Blocks:</h6>
                                @foreach(json_decode($ai_overview->text_blocks, true) as $block)
                                    <div class="p-3 border rounded mb-2">
                                        <p>{{ $block['text'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            @endif
                        @else
                            <div class="alert alert-info">
                                No AI Overview data available for this keyword.
                            </div>
                        @endif
                        
                        <div class="mt-3">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection