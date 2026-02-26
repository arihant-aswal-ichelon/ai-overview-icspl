@extends('layouts.page-app')
@section("content")

<style>
    /* keyword badge used in modal header */
    .keyword-badge-sm {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px; height: 26px;
        border-radius: 6px;
        background: linear-gradient(135deg, #4f6ef7, #7c3aed);
        color: #fff;
        font-size: 13px;
        flex-shrink: 0;
    }
    .word-count-badge {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        background: #f5f7fb;
        padding: 3px 9px;
        border-radius: 999px;
        border: 1px solid #e8ecf4;
    }
    .copy-btn.copied {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #065f46 !important;
    }
    .prompt-modal-header {
        background: linear-gradient(110deg, rgba(79,110,247,.07) 0%, rgba(124,58,237,.05) 100%);
        border-bottom: 1px solid #e8ecf4;
        flex-shrink: 0;
    }
    .modal-fullscreen .modal-content {
        height: 100vh;
        border-radius: 0;
        display: flex;
        flex-direction: column;
    }
    .modal-fullscreen .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 24px;
    }
    /* Editable textarea */
    .prompt-full-text {
        width: 100%;
        height: 100%;
        min-height: calc(100vh - 80px);
        font-size: .9rem;
        line-height: 1.85;
        color: #1e293b;
        white-space: pre-wrap;
        word-break: break-word;
        background: #f8faff;
        border: 1px solid #e8ecf4;
        border-radius: 10px;
        padding: 24px 28px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        margin: 0;
        resize: none;
        outline: none;
        display: block;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .prompt-full-text:focus {
        border-color: #4f6ef7;
        box-shadow: 0 0 0 3px rgba(79,110,247,.12);
    }
    #btn-save-prompt.saving {
        opacity: .75;
        pointer-events: none;
    }
    /* Toast */
    #prompt-save-toast {
        position: fixed;
        bottom: 28px;
        right: 28px;
        z-index: 9999;
        min-width: 300px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        display: none;
    }
</style>
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Display Generated Prompts</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item">Analysed Keywords</li>
                            <li class="breadcrumb-item active">Prompt List</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Generated Prompt List</h5>
                    </div> 
                    <div class="card-body">
                        <div class="live-preview">
                            <form id="generated_prompt_result_form" class="row g-3">
                                <div class="col-md-12 input-group-custom" id="domainClusterGroup">
                                    <select class="form-control" id="generate_prompt_results" name="generate_prompt_results">
                                        <option value="">Select a Option</option>
                                        @if(isset($generate_prompt_results) && count($generate_prompt_results) > 0)
                                        @foreach($generate_prompt_results as $result)
                                        <option value="{{ $result['id'] }}">
                                        {{$result['median_name']}} -     
                                        {{ date('M d, Y', strtotime($result['created_at'])) }} to
                                            {{ date('M d, Y', strtotime($result['updated_at'])) }}
                                            @if(isset($result['keyword_ids']) && !empty($result['keyword_ids']))
                                            - {{ $result['keyword_ids'] }}
                                            @endif
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="" disabled>No Data found</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-primary" >
                                        Show Result
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="prompt_result_box"></div>
    </div>
</div>
@endsection

@section("jscontent")
<script>
(function ($) {
    'use strict';

    $('#generated_prompt_result_form').on('submit', function (e) {
        e.preventDefault();
        const id = $('#generate_prompt_results').val();
        if (!id) {
            alert('Please select a prompt list first.');
            return;
        }
        const $box = $('#prompt_result_box');
        $box.html(`
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <h5 class="mt-2">Generating Prompts…</h5>
                            <p class="text-muted mb-0">
                                Building AIO prompts for your selected keywords, please wait.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $.ajax({
            url: '/display-specific-aio-prompt/' + id,
            method: 'GET',
            success: function (html) {
                $box.html(html);
            },
            error: function (xhr) {
                $box.html(`
                    <div class="alert alert-danger mt-3">
                        Failed to load results. Please try again.
                        <br><small class="text-muted">${xhr.status}: ${xhr.statusText}</small>
                    </div>
                `);
            }
        });
    });

}(jQuery));
</script>
@endsection