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
                    <h4 class="mb-sm-0">Generate AIO Prompt Analysis</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item">Analysed Keywords</li>
                            <li class="breadcrumb-item active">Keyword Request</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
        <div id="prompt_result_box"></div>
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        Keyword Results
                        <div class="d-flex align-items-center gap-3">
                            <a class="btn btn-warning btn-sm" target="_blank"
                            href="{{ route('display.aio.prompt', [$domainmanagement_id, $client_property_id]) }}">
                                Display Prompt List
                            </a>
                            <div id="bulk-action-bar" class="d-none">
                                <span id="selected-count" class="text-muted fw-medium">0 selected</span>
                                <button id="btn-send-analysis" class="btn btn-primary btn-sm">
                                    <i class="ri-send-plane-fill me-1"></i> Send for Analysis
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                    @php
                        $grouped = $medianResults->groupBy(function($item) {
                            return \Carbon\Carbon::parse($item->mf_updated_at)->format('d M Y');
                        });
                    @endphp

                    @forelse ($grouped as $date => $keywords)
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <span class="badge bg-light text-dark border ms-2" style="font-size:.8rem;">
                                    {{ $date }}
                                </span>
                                <span class="badge bg-primary ms-1" style="font-size:.75rem;">{{ count($keywords) }} keywords</span>
                            </h5>
                        </div>
                        <table class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th style="width:40px;">
                                        <input type="checkbox" class="form-check-input select-group" title="Select all in group">
                                    </th>
                                    <th>Keyword</th>
                                    <th>Monthly Search</th>
                                    <th>Competition</th>
                                    <th>Low Bid (₹)</th>
                                    <th>High Bid (₹)</th>
                                    <th>Clicks</th>
                                    <th>CTR (%)</th>
                                    <th>Impressions</th>
                                    <th>Position</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($keywords as $keyword)
                                <tr
                                    data-keyword_id="{{ $keyword->mf_keyword_p }}"
                                    data-keyword="{{ $keyword->kp_keyword }}"
                                    data-questions-json='@json($keyword->related_questions)'
                                    data-searches-json='@json($keyword->related_searches)'
                                >
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox">
                                    </td>
                                    <td>{{ $keyword->kp_keyword }}</td>
                                    <td>{{ $keyword->mf_monthlysearch_p }}</td>
                                    <td>{{ $keyword->mf_competition_p }}</td>
                                    <td>{{ $keyword->mf_low_bid_p }}</td>
                                    <td>{{ $keyword->mf_high_bid_p }}</td>
                                    <td>{{ $keyword->mf_clicks_p }}</td>
                                    <td>{{ $keyword->mf_ctr_p }}</td>
                                    <td>{{ $keyword->mf_impressions_p }}</td>
                                    <td>{{ $keyword->mf_position_p }}</td>
                                    <td>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-soft-secondary btn-sm" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ri-more-fill align-middle"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a href="{{ url('extracted-aio-result/'.$keyword->mf_keyword_p) }}"
                                                    target="_blank" class="dropdown-item">
                                                        <i class="mdi mdi-delete-circle-outline text-muted"></i> Analysis
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @empty
                        
                    <div class="card-body text-center py-5 text-muted">No Records Found!</div>
                
        @endforelse
                    </div>
        
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="promptModal" tabindex="-1" aria-labelledby="promptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content prompt-modal-content">

            <div class="modal-header prompt-modal-header" style="padding-bottom: 1rem !important;">
                <div class="d-flex align-items-center gap-2 overflow-hidden flex-grow-1">
                    <span class="keyword-badge-sm"><i class="ri-key-2-line"></i></span>
                    <h5 class="modal-title text-truncate" id="promptModalLabel">Prompt Preview</h5>
                </div>
                <div class="d-flex align-items-center gap-2 ms-3 flex-shrink-0">
                    <span id="modal-word-count" class="word-count-badge"></span>
                    <button id="btn-copy-prompt" class="btn btn-sm btn-outline-secondary copy-btn" title="Copy prompt">
                        <i class="ri-file-copy-line"></i> Copy
                    </button>
                    <button id="btn-save-prompt" class="btn btn-sm btn-success" title="Save prompt">
                        <i class="ri-save-line"></i> Save
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            {{-- Editable prompt body --}}
            <div class="modal-body overflow-y-auto p-4">
                <textarea
                    id="modal-prompt-body"
                    class="prompt-full-text"
                    spellcheck="false"
                    placeholder="Prompt will appear here…"
                ></textarea>
            </div>

        </div>
    </div>
</div>

<div id="prompt-save-toast" class="alert mb-0" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i id="toast-icon" class="fs-5"></i>
        <span id="toast-message"></span>
    </div>
</div>
@endsection

@section("jscontent")
<script>
$(document).ready(function () {

    // ── Toolbar helpers ───────────────────────────────────────────────────
    function refreshToolbar() {
        const $checked = $('.row-checkbox:checked');
        const count    = $checked.length;
        const total    = $('.row-checkbox').length;

        $('#selected-count').text(count + ' selected');
        $('#bulk-action-bar').toggleClass('d-none', count === 0);
        $('#select-all').prop('indeterminate', count > 0 && count < total);
        $('#select-all').prop('checked',       count > 0 && count === total);
    }

    $(document).on('change', '.select-group', function () {
        const $group = $(this).closest('.card-body');
        $group.find('.row-checkbox').prop('checked', $(this).is(':checked'));
        refreshToolbar();
    });

    $(document).on('change', '.row-checkbox', function () {
        refreshToolbar();
    });

    // ── Send for Analysis ─────────────────────────────────────────────────
    $('#btn-send-analysis').on('click', function () {
        const $checkedBoxes = $('.row-checkbox:checked');

        if ($checkedBoxes.length === 0) {
            alert('Please select at least one keyword.');
            return;
        }

        const payload = {
            keyword_id: [],
            keyword: [],
            questions: [],
            searches: []
        };
        $checkedBoxes.each(function () {
            const $row = $(this).closest('tr');

            payload.keyword_id.push($row.data('keyword_id'));
            payload.keyword.push($row.data('keyword'));
            payload.questions.push($row.data('questions-json'));
            payload.searches.push($row.data('searches-json'));
        });
        const sorted = payload.keyword_id
            .map((id, i) => ({
                keyword_id: id,
                keyword:    payload.keyword[i],
                questions:  payload.questions[i],
                searches:   payload.searches[i],
            }))
            .sort((a, b) => a.keyword_id - b.keyword_id);

        // Unpack back into payload
        payload.keyword_id = sorted.map(r => r.keyword_id);
        payload.keyword    = sorted.map(r => r.keyword);
        payload.questions  = sorted.map(r => r.questions);
        payload.searches   = sorted.map(r => r.searches);

        const $btn = $(this);

        $.ajax({
            url         : '{{ url("generate-prompt") }}',
            method      : 'POST',
            headers     : {
                'X-CSRF-TOKEN' : '{{ csrf_token() }}',
                // No 'Accept: application/json' — controller returns rendered HTML
            },
            contentType : 'application/json',
            data: JSON.stringify({
                client_property_id: {{ $client_property_id }},
                domainmanagement_id: {{ $domainmanagement_id }},
                keyword_request_id: {{ $keyword_request_id }},
                selected_keywords: payload
            }),

            // ── beforeSend: show spinner card in the result box ───────────
            beforeSend: function () {
                $btn.prop('disabled', true)
                    .html('<i class="ri-loader-4-line me-1 aio-spin"></i> Generating…');

                $('#prompt_result_box').html(`
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <div class="spinner-border text-primary mb-3"
                                         style="width:3rem;height:3rem;" role="status">
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

                $('html, body').animate({
                    scrollTop: $('#prompt_result_box').offset().top - 80
                }, 400);
            },

            // ── success: inject the rendered blade HTML ────────────────────
            // The controller returns view('...prompt-table', compact('results'))
            // so `html` is the fully rendered HTML string including the
            // window.__promptStore <script> block.
            success: function (html) {
                $('#prompt_result_box').html(html);

                $('html, body').animate({
                    scrollTop: $('#prompt_result_box').offset().top - 80
                }, 300);
            },

            // ── error ─────────────────────────────────────────────────────
            error: function (xhr) {
                $('#prompt_result_box').html(`
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="alert alert-danger d-flex align-items-center gap-2">
                                <i class="ri-error-warning-line fs-5"></i>
                                <span>Something went wrong while generating prompts. Please try again.</span>
                            </div>
                        </div>
                    </div>
                `);
                console.error('AJAX error:', xhr.responseText);
            },

            // ── complete: restore button ───────────────────────────────────
            complete: function () {
                $btn.prop('disabled', false)
                    .html('<i class="ri-send-plane-fill me-1"></i> Send for Analysis');
            }
        });
    });

    // ── "Show More" — delegated ───────────────────────────────────────────
    $(document).on('click', '.js-show-prompt', function () {
        const index     = $(this).data('prompt-index');
        const keyword   = $(this).data('keyword');
        const keywordId = $(this).data('keyword-id');
        const prompt    = (window.__promptStore && window.__promptStore[index] !== undefined)
                              ? window.__promptStore[index]
                              : '';

        if (!prompt) {
            console.warn('Prompt not found in __promptStore for index:', index);
            return;
        }

        const wordCount = prompt.trim().split(/\s+/).filter(Boolean).length;

        $('#promptModalLabel').text(keyword);
        $('#modal-prompt-body').val(prompt);            // textarea uses .val()
        $('#modal-word-count').text(wordCount + ' words');

        // Store keyword + id on Save button so the save handler can read them
        $('#btn-save-prompt')
            .data('keyword-id', keywordId)
            .data('keyword', keyword)
            .removeClass('saving btn-success btn-danger')
            .addClass('btn-success')
            .html('<i class="ri-save-line"></i> Save');

        // Update copy button
        $('#btn-copy-prompt')
            .removeClass('copied')
            .html('<i class="ri-file-copy-line"></i> Copy');

        // Live word count as user types
        $('#modal-prompt-body').off('input.wordcount').on('input.wordcount', function () {
            const wc = $(this).val().trim().split(/\s+/).filter(Boolean).length;
            $('#modal-word-count').text(wc + ' words');
        });

        var modalEl = document.getElementById('promptModal');
        if (modalEl) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        }
    });

    // ── Copy to clipboard — reads live textarea value ─────────────────────
    $(document).on('click', '#btn-copy-prompt', function () {
        const text = $('#modal-prompt-body').val();
        if (!text) return;

        const $btn = $(this);

        navigator.clipboard.writeText(text)
            .then(function () {
                $btn.addClass('copied').html('<i class="ri-check-line"></i> Copied!');
                setTimeout(function () {
                    $btn.removeClass('copied').html('<i class="ri-file-copy-line"></i> Copy');
                }, 2000);
            })
            .catch(function () {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.cssText = 'position:fixed;opacity:0;';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                $btn.addClass('copied').html('<i class="ri-check-line"></i> Copied!');
                setTimeout(function () {
                    $btn.removeClass('copied').html('<i class="ri-file-copy-line"></i> Copy');
                }, 2000);
            });
    });

    // ── Save edited prompt ────────────────────────────────────────────────
    $(document).on('click', '#btn-save-prompt', function () {
        const $btn      = $(this);
        const keywordId = $btn.data('keyword-id');
        const keyword   = $btn.data('keyword');
        const prompt    = $('#modal-prompt-body').val().trim();

        if (!prompt) {
            showToast('danger', 'ri-error-warning-line', 'Prompt cannot be empty.');
            return;
        }

        // Loading state
        $btn.addClass('saving').html('<i class="ri-loader-4-line aio-spin me-1"></i> Saving…');

        $.ajax({
            url         : '{{ url("update-prompt") }}',
            method      : 'POST',
            headers     : {
                'X-CSRF-TOKEN' : '{{ csrf_token() }}',
                'Accept'       : 'application/json',
            },
            contentType : 'application/json',
            data        : JSON.stringify({
                keyword_id : keywordId,
                keyword    : keyword,
                prompt     : prompt,
            }),
            success: function (response) {
                $btn.removeClass('saving btn-danger')
                    .addClass('btn-success')
                    .html('<i class="ri-check-line me-1"></i> Saved');

                showToast('success', 'ri-check-double-line', response.message || 'Prompt saved successfully.');

                // Reset button label after 3s
                setTimeout(function () {
                    $btn.html('<i class="ri-save-line"></i> Save');
                }, 3000);
            },
            error: function (xhr) {
                $btn.removeClass('saving btn-success')
                    .addClass('btn-danger')
                    .html('<i class="ri-close-line me-1"></i> Failed');

                const msg = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Failed to save prompt. Please try again.';
                showToast('danger', 'ri-error-warning-line', msg);

                setTimeout(function () {
                    $btn.removeClass('btn-danger')
                        .addClass('btn-success')
                        .html('<i class="ri-save-line"></i> Save');
                }, 3000);

                console.error('Save error:', xhr.responseText);
            },
            complete: function () {
                $btn.removeClass('saving');
            }
        });
    });

    // ── Toast helper ──────────────────────────────────────────────────────
    function showToast(type, icon, message) {
        const $toast = $('#prompt-save-toast');

        // Reset classes
        $toast.removeClass('alert-success alert-danger alert-warning alert-info');
        $toast.addClass('alert-' + type);

        $('#toast-icon').attr('class', 'fs-5 ' + icon);
        $('#toast-message').text(message);

        $toast.stop(true).fadeIn(250);

        clearTimeout($toast.data('hideTimer'));
        $toast.data('hideTimer', setTimeout(function () {
            $toast.fadeOut(400);
        }, 4000));
    }

    // ── Spinner keyframe ──────────────────────────────────────────────────
    $('<style>' +
        '@keyframes aio-spin { to { transform: rotate(360deg); } }' +
        '.aio-spin { display:inline-block; animation: aio-spin .7s linear infinite; }' +
    '</style>').appendTo('head');
});
</script>
@endsection