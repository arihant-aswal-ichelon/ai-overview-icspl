<style>
    :root {
        --pr-accent:      #4f6ef7;
        --pr-accent2:     #7c3aed;
        --pr-surface:     #ffffff;
        --pr-muted:       #64748b;
        --pr-border:      #e8ecf4;
        --pr-bg:          #f5f7fb;
        --pr-radius:      14px;
    }

    /* ── Shared card chrome ─────────────────────────────────────── */
    .aio-section-card {
        border-radius: var(--pr-radius) !important;
        border: 1px solid var(--pr-border);
        background: var(--pr-surface);
        overflow: hidden;
        margin-bottom: 28px;
    }
    .aio-section-header {
        background: linear-gradient(110deg, rgba(79,110,247,.08) 0%, rgba(124,58,237,.06) 100%);
        border-bottom: 1px solid var(--pr-border);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .aio-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px; height: 34px;
        border-radius: 9px;
        background: linear-gradient(135deg, var(--pr-accent), var(--pr-accent2));
        color: #fff;
        font-size: 15px;
        flex-shrink: 0;
    }
    .aio-section-title {
        font-size: .95rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        line-height: 1.2;
    }
    .aio-section-subtitle {
        font-size: .75rem;
        color: var(--pr-muted);
        margin: 1px 0 0;
    }

    /* ── Section A — Competitor card ────────────────────────────── */
    .competitor-summary {
        background: var(--pr-bg);
        border: 1px solid var(--pr-border);
        border-radius: 10px;
        padding: 14px 18px;
        font-size: .855rem;
        color: #334155;
        line-height: 1.7;
        margin-bottom: 20px;
    }
    .presence-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .presence-high   { background: #fef2f2; color: #dc2626; }
    .presence-medium { background: #fef9c3; color: #b45309; }
    .presence-low    { background: #f0fdf4; color: #15803d; }
    .dataset-chip {
        display: inline-flex; align-items: center;
        gap: 4px;
        background: rgba(79,110,247,.09);
        color: var(--pr-accent);
        padding: 2px 9px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
    }

    /* ── Tables ─────────────────────────────────────────────────── */
    .aio-table { margin: 0; }
    .aio-table thead tr th {
        background: var(--pr-bg);
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .07em;
        text-transform: uppercase;
        color: var(--pr-muted);
        border-bottom: 1px solid var(--pr-border);
        padding: 10px 16px;
        white-space: nowrap;
    }
    .aio-table tbody tr td {
        font-size: .845rem;
        color: #334155;
        vertical-align: middle;
        padding: 11px 16px;
        border-bottom: 1px solid var(--pr-border);
    }
    .aio-table tbody tr:last-child td { border-bottom: none; }
    .aio-table tbody tr:hover td { background: rgba(79,110,247,.03); }

    /* row number pill */
    .row-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px; height: 24px;
        border-radius: 6px;
        background: rgba(79,110,247,.10);
        color: var(--pr-accent);
        font-size: .72rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    /* prompt text truncate */
    .prompt-cell-text {
        max-width: 520px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
    .intent-chip {
        display: inline-flex; align-items: center;
        background: #ede9fe;
        color: #5b21b6;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .strategic-cell {
        max-width: 260px;
        font-size: .8rem;
        color: var(--pr-muted);
        line-height: 1.5;
    }
    .expected-cell {
        max-width: 240px;
        font-size: .8rem;
        color: var(--pr-muted);
        line-height: 1.5;
    }

    /* ── View button ─────────────────────────────────────────────── */
    .btn-view-prompt {
        padding: 3px 12px;
        font-size: .76rem;
        font-weight: 600;
        border-radius: 7px;
        border: 1px solid var(--pr-border);
        background: var(--pr-surface);
        color: var(--pr-accent);
        transition: background .15s, color .15s, border-color .15s;
        white-space: nowrap;
    }
    .btn-view-prompt:hover {
        background: var(--pr-accent);
        color: #fff;
        border-color: var(--pr-accent);
    }

    /* ── gemini Button ─────────────────────────────────────────── */
    .btn-gemini {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        font-size: .78rem;
        font-weight: 700;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, #10a37f, #0d8a6e);
        color: #fff;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
        white-space: nowrap;
    }
    .btn-gemini:hover   { opacity: .88; }
    .btn-gemini:active  { transform: scale(.97); }
    .btn-gemini:disabled {
        opacity: .6;
        cursor: not-allowed;
    }
    .btn-gemini .spinner-border {
        width: 14px; height: 14px;
        border-width: 2px;
    }

    /* ── ChatGPT Button (OpenAI) ────────────────────────────────── */
    .btn-chatgpt {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        font-size: .78rem;
        font-weight: 700;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, #1a1a1a, #3d3d3d);
        color: #fff;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
        white-space: nowrap;
    }
    .btn-chatgpt:hover  { opacity: .85; }
    .btn-chatgpt:active { transform: scale(.97); }
    .btn-chatgpt:disabled {
        opacity: .6;
        cursor: not-allowed;
    }
    .btn-chatgpt .spinner-border {
        width: 14px; height: 14px;
        border-width: 2px;
    }

    /* ── Search API Button ──────────────────────────────────────── */
    .btn-searchapi {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        font-size: .78rem;
        font-weight: 700;
        border-radius: 8px;
        border: none;
        background: linear-gradient(135deg, #7c3aed, #4f6ef7);
        color: #fff;
        cursor: pointer;
        transition: opacity .2s, transform .1s;
        white-space: nowrap;
    }
    .btn-searchapi:hover  { opacity: .88; }
    .btn-searchapi:active { transform: scale(.97); }
    .btn-searchapi:disabled {
        opacity: .6;
        cursor: not-allowed;
    }
    .btn-searchapi .spinner-border {
        width: 14px; height: 14px;
        border-width: 2px;
    }

    /* ── Client-mention percentage badge ───────────────────────── */
    .mention-pct-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .pct-high   { background: #f0fdf4; color: #15803d; border-color: #86efac; }
    .pct-medium { background: #fef9c3; color: #b45309; border-color: #fde68a; }
    .pct-low    { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    /* ── Fullscreen modals ──────────────────────────────────────── */
    .modal-fullscreen .modal-content {
        height: 100vh;
        border-radius: 0;
        display: flex;
        flex-direction: column;
    }
    .aio-modal-header {
        background: linear-gradient(110deg, rgba(79,110,247,.08) 0%, rgba(124,58,237,.06) 100%);
        border-bottom: 1px solid var(--pr-border);
        padding: 16px 24px;
        flex-shrink: 0;
    }
    .modal-fullscreen .modal-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 24px;
    }
    .prompt-full-textarea {
        width: 100%;
        height: calc(100vh - 140px);
        font-size: .9rem;
        line-height: 1.85;
        color: #1e293b;
        white-space: pre-wrap;
        word-break: break-word;
        background: #f8faff;
        border: 1px solid var(--pr-border);
        border-radius: 10px;
        padding: 22px 26px;
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        resize: none;
        outline: none;
        display: block;
        transition: border-color .2s, box-shadow .2s;
    }
    .prompt-full-textarea:focus {
        border-color: var(--pr-accent);
        box-shadow: 0 0 0 3px rgba(79,110,247,.12);
    }
    .copy-btn.copied {
        background: #d1fae5 !important;
        border-color: #6ee7b7 !important;
        color: #065f46 !important;
    }

    /* ── Analytics Tab Nav ──────────────────────────────────────── */
    .analytics-tab-nav {
        display: flex;
        gap: 6px;
        padding-bottom: 0;
    }
    .analytics-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 20px;
        font-size: .82rem;
        font-weight: 700;
        border-radius: 10px;
        border: 1px solid var(--pr-border);
        color: var(--pr-muted);
        background: transparent;
        cursor: pointer;
        transition: color .15s, background .15s, border-color .15s;
        position: relative;
        bottom: -2px;
    }
    .analytics-tab-btn:hover {
        color: var(--pr-accent);
        background: rgba(79,110,247,.05);
    }
    .analytics-tab-btn.active {
        color: var(--pr-accent);
        background: var(--pr-surface);
        border-color: #64748b;
    }
    .analytics-response-body {
        font-size: .875rem;
        color: #334155;
        line-height: 1.8;
    }
    .analytics-response-body p { margin-bottom: .6rem; }
    .analytics-response-body ul,
    .analytics-response-body ol { padding-left: 1.4rem; margin-bottom: .6rem; }

    /* ── Analytics Modal Styles ────────────────────────────────── */
    .analytics-card {
        border: 1px solid var(--pr-border);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 16px;
        background: var(--pr-surface);
    }
    .analytics-card .card-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--pr-muted);
        margin-bottom: 6px;
    }
    .analytics-card .card-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
    }
    .analytics-card .card-sub {
        font-size: .8rem;
        color: var(--pr-muted);
        margin-top: 2px;
    }
    .mention-status-yes {
        display: inline-flex; align-items: center; gap: 5px;
        background: #f0fdf4; color: #15803d;
        border: 1px solid #86efac;
        padding: 4px 12px; border-radius: 999px;
        font-size: .8rem; font-weight: 700;
    }
    .mention-status-no {
        display: inline-flex; align-items: center; gap: 5px;
        background: #fef2f2; color: #dc2626;
        border: 1px solid #fecaca;
        padding: 4px 12px; border-radius: 999px;
        font-size: .8rem; font-weight: 700;
    }
    .breakdown-table th {
        background: var(--pr-bg);
        font-size: .72rem;
        font-weight: 700;
        color: var(--pr-muted);
        text-transform: uppercase;
        letter-spacing: .05em;
        padding: 8px 14px;
    }
    .breakdown-table td {
        padding: 8px 14px;
        font-size: .845rem;
        vertical-align: middle;
    }
    .term-count-badge {
        display: inline-flex; align-items: center;
        background: rgba(79,110,247,.10);
        color: var(--pr-accent);
        padding: 2px 10px; border-radius: 999px;
        font-size: .78rem; font-weight: 700;
    }
    .prompt-preview-box {
        background: #f8faff;
        border: 1px solid var(--pr-border);
        border-radius: 10px;
        padding: 16px 18px;
        font-size: .855rem;
        color: #334155;
        line-height: 1.7;
        margin-bottom: 20px;
    }

    /* ── Toast ──────────────────────────────────────────────────── */
    #aio-toast {
        position: fixed; bottom: 28px; right: 28px;
        z-index: 9999; min-width: 280px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.13);
        display: none;
    }

    /* ── No competitor state ────────────────────────────────────── */
    .no-competitor-state {
        text-align: center;
        padding: 36px 24px;
        color: var(--pr-muted);
        font-size: .875rem;
    }
    .no-competitor-state i { font-size: 2rem; opacity: .45; display: block; margin-bottom: 8px; }

    /* ── Parse error banner ─────────────────────────────────────── */
    .parse-error-banner {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 10px;
        padding: 14px 18px;
        color: #b91c1c;
        font-size: .855rem;
    }

    /* ══════════════════════════════════════════════════════════════
       QUEUE & CHECKBOX STYLES
    ══════════════════════════════════════════════════════════════ */

    /* Checkbox column */
    .th-check, .td-check {
        width: 40px;
        text-align: center;
        vertical-align: middle !important;
        padding: 10px 8px !important;
    }

    /* Row state highlights */
    tr.row-queued   td { background: rgba(245,158,11,.05) !important; }
    tr.row-processing td { background: rgba(79,110,247,.06) !important; }
    tr.row-done     td { background: rgba(16,163,127,.05) !important; }
    tr.row-error    td { background: rgba(220,38,38,.05)  !important; }

    /* Queue status badge in action cell */
    .queue-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .qs-queued     { background: #fef9c3; border: 1px solid #fde68a; }
    .qs-processing { background: #eff6ff; border: 1px solid #bfdbfe; }
    .qs-error      { background: #fef2f2; border: 1px solid #fecaca; }

    .queue-spinner {
        width: 13px; height: 13px;
        border-width: 2px;
        flex-shrink: 0;
    }

    /* Counter badge on gemini button area */
    .queue-counter-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(255,255,255,.2);
        border-radius: 999px;
        padding: 2px 8px;
        font-size: .72rem;
        font-weight: 700;
    }

    /* Select-all controls bar */
    .select-controls-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        background: var(--pr-bg);
        border-bottom: 1px solid var(--pr-border);
        font-size: .78rem;
        color: var(--pr-muted);
    }
</style>

@php
    $rawPrompt = $results[0]['prompt'] ?? '';
    
    $secA = $rawPrompt['section_a_competitor_presence_analysis'] ?? null;
    $secB = $rawPrompt['section_b_20_ai_search_prompts_brand_neutral'] ?? [];
    $secC = $rawPrompt['section_c_top_10_recommended_prompts_for_ai_visibility_tracking'] ?? [];
    $secD = $rawPrompt['section_d_competitor_trigger_prompts'] ?? [];

    $clientString = $client ?? '';
@endphp

{{-- Hidden meta for JS ─────────────────────────────────────── --}}
<meta name="gemini-client" content="{{ e($clientString) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- ══════════════════════════════════════════════════════════════
     SECTION A — Competitor Presence Analysis
══════════════════════════════════════════════════════════════ --}}
@if($secA)
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <p class="aio-section-title">Section A — Competitor Presence Analysis</p>
            </div>
            <div class="card-body p-4">
                @if(!empty($secA['summary']))
                    <div class="alert alert-primary">
                        {{ $secA['summary'] }}
                    </div>
                @endif

                @if(!empty($secA['table']))
                    <div class="card-body table-responsive">
                        <table class="table nowrap align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Competitor Name</th>
                                    <th>Dataset</th>
                                    <th>Frequency</th>
                                    <th>Presence Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($secA['table'] as $i => $comp)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td><strong class="text-dark">{{ $comp['competitor_name'] ?? '—' }}</strong></td>
                                        <td>{{ $comp['dataset'] ?? '—' }}</td>
                                        <td>
                                            <strong>{{ $comp['frequency'] ?? 0 }}</strong>
                                            <small class="text-muted ms-1">mention{{ ($comp['frequency'] ?? 0) !== 1 ? 's' : '' }}</small>
                                        </td>
                                        <td>
                                            @php $lvl = $comp['presence_level'] ?? 'Low'; @endphp
                                            {{ $lvl }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="no-competitor-state">
                        No competitor brands detected in the input data.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════
     SECTION B — Brand-Neutral AI Search Prompts
══════════════════════════════════════════════════════════════ --}}
@if(!empty($secB))
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">Section B — Brand-Neutral AI Search Prompts</h5>
                    <span class="badge bg-warning p-2 fs-6 text-white pct-medium" id="secB-select-bar">
                        <span>Selected: <span id="secB-sel-count">0</span> of {{ count($secB) }}</span>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-chatgpt js-chatgpt-btn"  data-section="B" type="button">ChatGPT</button>
                    <button class="btn-gemini js-gemini-btn"   data-section="B" type="button">Gemini</button>
                    <button class="btn-searchapi js-searchapi-btn" data-section="B" type="button">Search API</button>
                </div>
            </div>
            
            <div class="card-body table-responsive p-0">
                <table class="table nowrap align-middle mb-0" id="secB-table">
                    <thead>
                        <tr>
                            <th class="th-check"><input class="form-check-input js-select-all" type="checkbox" id="secB-select-all" data-section="B"></th>
                            <th style="width:40px;">#</th>
                            <th>Search Prompt</th>
                            <th style="width:140px;" class="th-source-chatgpt th-source-B d-none">
                                ChatGPT<br>
                                <span id="secB-chatgpt-counter">
                                    <span id="secB-chatgpt-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-gemini th-source-B d-none">
                                Gemini<br>
                                <span id="secB-gemini-counter">
                                    <span id="secB-gemini-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-searchapi th-source-B d-none">
                                Search API<br>
                                <span id="secB-searchapi-counter">
                                    <span id="secB-searchapi-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($secB as $i => $prompt)
                            <tr data-secb-idx="{{ $i }}" id="secB-row-{{ $i }}">
                                <td class="td-check">
                                    <input
                                        class="form-check-input js-row-check"
                                        type="checkbox"
                                        data-section="B"
                                        data-idx="{{ $i }}"
                                        id="secB-chk-{{ $i }}"
                                    >
                                </td>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="prompt-cell-text" title="{{ $prompt }}">{{ $prompt }}</span>
                                </td>
                                <td class="chatgpt-cell-B d-none" id="secB-chatgpt-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="gemini-cell-B d-none" id="secB-gemini-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="searchapi-cell-B d-none" id="secB-searchapi-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="action-cell-B">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <button
                                            class="btn btn-primary btn-sm js-view-text"
                                            data-text="{{ e($prompt) }}"
                                            data-title="Search Prompt #{{ $i + 1 }}"
                                        >Show More</button>
                                        {{-- Analytics btn and queue status injected here by JS --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════
     SECTION C — Top 10 Prompts for AI Visibility Tracking
══════════════════════════════════════════════════════════════ --}}
@if(!empty($secC))
@php
    $secCPrompts = array_column($secC, 'prompt');
@endphp
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">Section C — Top {{ count($secC) }} Prompts for AI Visibility Tracking</h5>
                    <span class="badge bg-success fs-6 p-2 text-white d-none" id="secC-pct-badge">
                        <span id="secC-pct-text">—</span>
                    </span>
                    <span class="badge bg-warning p-2 fs-6 text-white pct-medium" id="secC-select-bar">
                        <span>Selected: <span id="secC-sel-count">0</span> of {{ count($secC) }}</span>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-chatgpt js-chatgpt-btn"  data-section="C" type="button">ChatGPT</button>
                    <button class="btn-gemini js-gemini-btn"   data-section="C" type="button">Gemini</button>
                    <button class="btn-searchapi js-searchapi-btn" data-section="C" type="button">Search API</button>
                </div>
            </div>
            
            <div class="card-body table-responsive p-0">
                <table class="table nowrap align-middle mb-0" id="secC-table">
                    <thead>
                        <tr>
                            <th class="th-check"><input class="form-check-input js-select-all" type="checkbox" id="secC-select-all" data-section="C"></th>
                            <th style="width:40px;">#</th>
                            <th>Prompt</th>
                            <th>Intent Category</th>
                            <th>Strategic Value</th>
                            <th>Expected AI Output Type</th>
                            <th style="width:140px;" class="th-source-chatgpt th-source-C d-none">
                                ChatGPT<br>
                                <span id="secC-chatgpt-counter">
                                    <span id="secC-chatgpt-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-gemini th-source-C d-none">
                                Gemini<br>
                                <span id="secC-gemini-counter">
                                    <span id="secC-gemini-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-searchapi th-source-C d-none">
                                Search API<br>
                                <span id="secC-searchapi-counter">
                                    <span id="secC-searchapi-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($secC as $i => $item)
                            <tr data-secc-idx="{{ $i }}" id="secC-row-{{ $i }}">
                                <td class="td-check">
                                    <input
                                        class="form-check-input js-row-check"
                                        type="checkbox"
                                        data-section="C"
                                        data-idx="{{ $i }}"
                                        id="secC-chk-{{ $i }}"
                                    >
                                </td>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="prompt-cell-text" style="max-width:300px;" title="{{ $item['prompt'] ?? '' }}">
                                        {{ $item['prompt'] ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $item['intent_category'] ?? '—' }}</td>
                                <td>
                                    <span class="strategic-cell">{{ Str::limit($item['strategic_value'] ?? '—', 90) }}</span>
                                </td>
                                <td>
                                    <span class="expected-cell">{{ Str::limit($item['expected_ai_output_type'] ?? '—', 80) }}</span>
                                </td>
                                <td class="chatgpt-cell-C d-none" id="secC-chatgpt-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="gemini-cell-C d-none" id="secC-gemini-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="searchapi-cell-C d-none" id="secC-searchapi-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="action-cell-C">
                                    @php
                                        $fullText = implode("\n\n", array_filter([
                                            "PROMPT:\n" . ($item['prompt'] ?? ''),
                                            "INTENT CATEGORY:\n" . ($item['intent_category'] ?? ''),
                                            "STRATEGIC VALUE:\n" . ($item['strategic_value'] ?? ''),
                                            "EXPECTED AI OUTPUT TYPE:\n" . ($item['expected_ai_output_type'] ?? ''),
                                        ]));
                                    @endphp
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <button
                                            class="btn btn-primary btn-sm js-view-text"
                                            data-text="{{ e($fullText) }}"
                                            data-title="Recommended Prompt #{{ $i + 1 }}"
                                        >Show More</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════
     SECTION D — Competitor Trigger Prompts
══════════════════════════════════════════════════════════════ --}}
@if(!empty($secD))
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0">Section D - Competitor Trigger Prompts</h5>
                    <span class="badge bg-success fs-6 p-2 text-white d-none" id="secD-pct-badge">
                        <span id="secD-pct-text">—</span>
                    </span>
                    <span class="badge bg-warning p-2 fs-6 text-white" id="secD-select-bar">
                        <span>Selected: <span class="selected-count" id="secD-sel-count">0</span> of {{ count($secD) }}</span>
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn-chatgpt js-chatgpt-btn"  data-section="D" type="button">ChatGPT</button>
                    <button class="btn-gemini js-gemini-btn"   data-section="D" type="button">Gemini</button>
                    <button class="btn-searchapi js-searchapi-btn" data-section="D" type="button">Search API</button>
                </div>
            </div>
            {{-- Select controls bar --}}
            <div class="card-body table-responsive p-0">
                <table class="table nowrap align-middle mb-0" id="secD-table">
                    <thead>
                        <tr>
                            <th class="th-check"><input class="form-check-input js-select-all" type="checkbox" id="secD-select-all" data-section="D"></th>
                            <th style="width:40px;">#</th>
                            <th>Competition Trigger Prompt</th>
                            <th style="width:140px;" class="th-source-chatgpt th-source-D d-none">
                                ChatGPT<br>
                                <span id="secD-chatgpt-counter">
                                    <span id="secD-chatgpt-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-gemini th-source-D d-none">
                                Gemini<br>
                                <span id="secD-gemini-counter">
                                    <span id="secD-gemini-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th style="width:140px;" class="th-source-searchapi th-source-D d-none">
                                Search API<br>
                                <span id="secD-searchapi-counter">
                                    <span id="secD-searchapi-text" class="small fw-normal text-muted"></span>
                                </span>
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($secD as $i => $prompt)
                            <tr data-secd-idx="{{ $i }}" id="secD-row-{{ $i }}">
                                <td class="td-check">
                                    <input
                                        class="form-check-input js-row-check"
                                        type="checkbox"
                                        data-section="D"
                                        data-idx="{{ $i }}"
                                        id="secD-chk-{{ $i }}"
                                    >
                                </td>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <span class="prompt-cell-text" title="{{ $prompt }}">{{ $prompt }}</span>
                                </td>
                                <td class="chatgpt-cell-D d-none" id="secD-chatgpt-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="gemini-cell-D d-none" id="secD-gemini-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="searchapi-cell-D d-none" id="secD-searchapi-{{ $i }}">
                                    <span class="text-muted small">—</span>
                                </td>
                                <td class="action-cell-D">
                                    <div class="d-flex align-items-center gap-1 flex-wrap">
                                        <button
                                            class="btn btn-primary btn-sm js-view-text"
                                            data-text="{{ e($prompt) }}"
                                            data-title="Competitor Trigger Prompt #{{ $i + 1 }}"
                                        >Show More</button>
                                        {{-- Analytics btn and queue status injected here by JS --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══════════════════════════════════════════════════════════════
     MODAL — Show More (full prompt text)
══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="aioPromptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="aio-modal-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="aio-section-icon" style="width:30px;height:30px;font-size:13px;border-radius:8px;">
                        <i class="ri-file-text-line"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-700 text-dark" id="aioModalTitle">Prompt</h6>
                        <small class="text-muted" id="aioModalWordCount"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary copy-btn" id="btn-aio-copy">
                        <i class="ri-file-copy-line me-1"></i>Copy
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <textarea
                    id="aio-modal-text"
                    class="prompt-full-textarea"
                    spellcheck="false"
                    readonly
                ></textarea>
            </div>
        </div>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════
     MODAL — Analytics
══════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="analyticsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(110deg,rgba(79,110,247,.08),rgba(124,58,237,.06));border-bottom:1px solid var(--pr-border);">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h6 class="mb-0 fw-700 text-dark" id="analyticsModalTitle">Prompt Analytics</h6>
                        <small class="text-muted" id="analyticsModalSub"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="analyticsModalBody">
                {{-- Populated by JS --}}
            </div>
        </div>
    </div>
</div>


<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1056"></div>


{{-- ── Safe JS prompt variables ── --}}
<script>
    window._aioPrompts = {
        B: {!! json_encode($secB, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        C: {!! json_encode(array_column($secC ?? [], 'prompt'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
        D: {!! json_encode($secD, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!},
    };
    window._generatedPromptId = {!! json_encode($generated_prompt_id ?? null) !!};
    window._savedResponses    = {!! json_encode($savedResponses ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};
</script>

<script>
(function ($) {
    'use strict';

    // ── Config ────────────────────────────────────────────────────
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CLIENT = "{{ $keyword_mentioned }}";

    // Single-prompt analysis routes
    const SINGLE_PROMPT_ROUTE      = '{{ route("analyze.single.prompt.gemini") }}';
    const CHATGPT_PROMPT_ROUTE     = '{{ route("analyze.single.prompt.chatgpt") }}';
    const SEARCH_API_PROMPT_ROUTE  = '{{ route("analyze.single.prompt.search.api") }}';

    // Stores analytics keyed by section: { B: { per_prompt: {}, summary: {} }, ... }
    const sectionAnalytics = {};

    // ══════════════════════════════════════════════════════════════
    //  RESTORE SAVED RESPONSES FROM DB ON PAGE LOAD
    // ══════════════════════════════════════════════════════════════
    (function replaySavedResponses() {
        const saved = window._savedResponses || {};
        if (!Object.keys(saved).length) return;

        // Map DB source names → JS source keys and response field keys
        const sourceMap = {
            gemini    : { jsKey: 'gemini',    responseField: 'gemini_response'  },
            chatgpt   : { jsKey: 'chatgpt',   responseField: 'chatgpt_response' },
            searchapi : { jsKey: 'searchapi', responseField: 'gemini_response'  },
        };

        Object.entries(saved).forEach(([dbSource, rows]) => {
            const map = sourceMap[dbSource];
            if (!map) return;
            const source = map.jsKey;

            rows.forEach(row => {
                const idx     = row.prompt_index;
                const section = row.section || _guessSection(idx);
                if (!section) return;

                const storeKey = section + '_' + source;

                // Init store if needed
                if (!sectionAnalytics[storeKey]) {
                    sectionAnalytics[storeKey] = {
                        per_prompt  : {},
                        client_terms: [],
                        summary     : { total_prompts: 0, mentioned_count: 0, percentage: 0, client_terms: [], term_totals: {} },
                    };
                }

                // Build per_prompt object in the same shape processQueue expects
                const perPrompt = {
                    prompt              : row.prompt,
                    ai_response         : row.ai_response   || '',
                    is_mentioned        : row.is_mentioned,
                    total_count         : row.total_count   || 0,
                    breakdown           : row.breakdown     || {},
                };
                perPrompt[map.responseField] = row.formatted_response || '';

                sectionAnalytics[storeKey].per_prompt[idx] = perPrompt;

                // Accumulate summary
                const terms = row.client_terms || [];
                if (sectionAnalytics[storeKey].client_terms.length === 0 && terms.length) {
                    sectionAnalytics[storeKey].client_terms = terms;
                    sectionAnalytics[storeKey].summary.client_terms = terms;
                    terms.forEach(t => {
                        sectionAnalytics[storeKey].summary.term_totals[t] =
                            sectionAnalytics[storeKey].summary.term_totals[t] || 0;
                    });
                }

                sectionAnalytics[storeKey].summary.total_prompts++;
                if (row.is_mentioned) sectionAnalytics[storeKey].summary.mentioned_count++;
                Object.entries(row.breakdown || {}).forEach(([term, count]) => {
                    sectionAnalytics[storeKey].summary.term_totals[term] =
                        (sectionAnalytics[storeKey].summary.term_totals[term] || 0) + count;
                });
                const tp = sectionAnalytics[storeKey].summary.total_prompts;
                const mc = sectionAnalytics[storeKey].summary.mentioned_count;
                sectionAnalytics[storeKey].summary.percentage =
                    tp > 0 ? Math.round((mc / tp) * 1000) / 10 : 0;
            });
        });

        // After all rows are loaded → update UI for each section+source that has data
        Object.keys(sectionAnalytics).forEach(storeKey => {
            const [section, source] = storeKey.split('_');
            const store = sectionAnalytics[storeKey];

            // Reveal column headers + cells
            if (source === 'chatgpt') {
                $(`.th-source-chatgpt.th-source-${section}`).removeClass('d-none');
                $(`.chatgpt-cell-${section}`).removeClass('d-none');
            } else if (source === 'gemini') {
                $(`.th-source-gemini.th-source-${section}`).removeClass('d-none');
                $(`.gemini-cell-${section}`).removeClass('d-none');
            } else if (source === 'searchapi') {
                $(`.th-source-searchapi.th-source-${section}`).removeClass('d-none');
                $(`.searchapi-cell-${section}`).removeClass('d-none');
            }

            // Fill each row's Yes/No cell + inject Analytics button
            Object.entries(store.per_prompt).forEach(([idx, perPrompt]) => {
                injectAnalyticsButton(section, source, parseInt(idx), perPrompt.is_mentioned);
                // Mark row as done (green tint)
                $(`#sec${section}-row-${idx}`).addClass('row-done');
            });

            // Update counter badge + pct badge in card header
            const totalRows = Object.keys(store.per_prompt).length;
            $(`#sec${section}-${source}-text`).text(
                `${store.summary.mentioned_count}/${totalRows} mentions (${store.summary.percentage}%)`
            );
            updatePctBadge(section, store.summary);
        });
    })();

    // Guess which section an index belongs to (fallback — uses prompt counts from window._aioPrompts)
    function _guessSection(idx) {
        const p = window._aioPrompts || {};
        if (Array.isArray(p.B) && idx < p.B.length) return 'B';
        if (Array.isArray(p.C) && idx < p.C.length) return 'C';
        if (Array.isArray(p.D) && idx < p.D.length) return 'D';
        return null;
    }

    // ══════════════════════════════════════════════════════════════
    //  CHECKBOX LOGIC
    // ══════════════════════════════════════════════════════════════

    // Update selected count display
    function updateSelCount(section) {
        const checked = $(`input.js-row-check[data-section="${section}"]:checked`).length;
        $(`#sec${section}-sel-count`).text(checked);
        // Sync select-all state
        const total = $(`input.js-row-check[data-section="${section}"]`).length;
        $(`#sec${section}-select-all`).prop('indeterminate', checked > 0 && checked < total);
        $(`#sec${section}-select-all`).prop('checked', checked === total && total > 0);
    }

    // Individual checkbox change
    $(document).on('change', '.js-row-check', function () {
        updateSelCount($(this).data('section'));
    });

    // Select All checkbox
    $(document).on('change', '.js-select-all', function () {
        const section = $(this).data('section');
        const checked = $(this).is(':checked');
        $(`input.js-row-check[data-section="${section}"]`).prop('checked', checked);
        updateSelCount(section);
    });


    // ══════════════════════════════════════════════════════════════
    //  SHARED QUEUE LAUNCHER — called by both button handlers
    // ══════════════════════════════════════════════════════════════
    function launchQueue(section, source, $btn) {
        // source: 'gemini' | 'searchapi'
        const selectedIndices = [];
        $(`input.js-row-check[data-section="${section}"]:checked`).each(function () {
            selectedIndices.push(parseInt($(this).data('idx')));
        });

        if (selectedIndices.length === 0) {
            showToast('warning', 'No rows selected', 'Please check at least one prompt row first.');
            return;
        }

        const allPrompts = (window._aioPrompts && window._aioPrompts[section]) || [];

        // Init analytics store for this section+source
        const storeKey = section + '_' + source;
        if (!sectionAnalytics[storeKey]) {
            sectionAnalytics[storeKey] = {
                per_prompt  : {},
                client_terms: [],
                summary     : { total_prompts: 0, mentioned_count: 0, percentage: 0, client_terms: [], term_totals: {} },
            };
        }

        // Reveal the chatgpt column (header + all body cells) on first click — others always visible
        if (source === 'chatgpt') {
            $(`.th-source-chatgpt.th-source-${section}`).removeClass('d-none');
            $(`.chatgpt-cell-${section}`).removeClass('d-none');
        }
        if (source === 'gemini') {
            $(`.th-source-gemini.th-source-${section}`).removeClass('d-none');
            $(`.gemini-cell-${section}`).removeClass('d-none');
        }
        if (source === 'searchapi') {
            $(`.th-source-searchapi.th-source-${section}`).removeClass('d-none');
            $(`.searchapi-cell-${section}`).removeClass('d-none');
        }

        // Mark all selected rows queued
        selectedIndices.forEach(idx => markRowQueued(section, source, idx));

        // Show the right counter badge
        const $counter = $(`#sec${section}-${source}-counter`);
        $counter.removeClass('d-none bg-secondary');
        $(`#sec${section}-${source}-text`).text(`0 / ${selectedIndices.length}`);

        // Disable this button + checkboxes
        $btn.prop('disabled', true).html(
            `<span class="spinner-border" role="status" style="width:14px;height:14px;border-width:2px;"></span> Running…`
        );
        $(`input.js-row-check[data-section="${section}"], #sec${section}-select-all`).prop('disabled', true);

        processQueue(section, source, selectedIndices, allPrompts, 0, $btn);
    }

    // ── ChatGPT button ────────────────────────────────────────────
    $(document).on('click', '.js-chatgpt-btn', function () {
        launchQueue($(this).data('section'), 'chatgpt', $(this));
    });

    // ── gemini button ────────────────────────────────────────────
    $(document).on('click', '.js-gemini-btn', function () {
        launchQueue($(this).data('section'), 'gemini', $(this));
    });

    // ── Search API button ─────────────────────────────────────────
    $(document).on('click', '.js-searchapi-btn', function () {
        launchQueue($(this).data('section'), 'searchapi', $(this));
    });

    // ── Sequential queue processor ────────────────────────────────
    function processQueue(section, source, indices, allPrompts, pos, $btn) {
        if (pos >= indices.length) {
            finishQueue(section, source, indices.length, $btn);
            return;
        }

        const idx    = indices[pos];
        const prompt = allPrompts[idx];

        const _lsk  = section + '_' + source;
        const _lmc  = sectionAnalytics[_lsk]?.summary?.mentioned_count ?? 0;
        const _lpct = sectionAnalytics[_lsk]?.summary?.percentage      ?? 0;
        $(`#sec${section}-${source}-text`).text(
            `${_lmc}/${indices.length} mentions (${_lpct}%)`
        );
        markRowProcessing(section, source, idx);

        const route = source === 'gemini'
            ? SINGLE_PROMPT_ROUTE
            : source === 'chatgpt'
                ? CHATGPT_PROMPT_ROUTE
                : SEARCH_API_PROMPT_ROUTE;

        $.ajax({
            url        : route,
            method     : 'POST',
            headers    : { 'X-CSRF-TOKEN': CSRF },
            data       : JSON.stringify({
                prompt              : prompt,
                client              : CLIENT,
                index               : idx,
                section             : section,
                generated_prompt_id : window._generatedPromptId || null,
            }),
            contentType: 'application/json',
            dataType   : 'json',
            timeout    : 180000,
        })
        .done(function (res) {
            if (res.success) {
                const data     = res.data;
                const storeKey = section + '_' + source;

                // Store per-prompt result
                sectionAnalytics[storeKey].per_prompt[idx] = data.per_prompt;

                // Store client terms on first success
                if (sectionAnalytics[storeKey].client_terms.length === 0 && data.client_terms) {
                    sectionAnalytics[storeKey].client_terms = data.client_terms;
                    sectionAnalytics[storeKey].summary.client_terms = data.client_terms;
                    data.client_terms.forEach(t => {
                        if (!sectionAnalytics[storeKey].summary.term_totals[t]) {
                            sectionAnalytics[storeKey].summary.term_totals[t] = 0;
                        }
                    });
                }

                // Accumulate summary
                sectionAnalytics[storeKey].summary.total_prompts++;
                if (data.is_mentioned) sectionAnalytics[storeKey].summary.mentioned_count++;
                const breakdown = data.per_prompt.breakdown || {};
                Object.keys(breakdown).forEach(term => {
                    sectionAnalytics[storeKey].summary.term_totals[term] =
                        (sectionAnalytics[storeKey].summary.term_totals[term] || 0) + (breakdown[term] || 0);
                });
                const tp = sectionAnalytics[storeKey].summary.total_prompts;
                const mc = sectionAnalytics[storeKey].summary.mentioned_count;
                sectionAnalytics[storeKey].summary.percentage = tp > 0 ? Math.round((mc / tp) * 1000) / 10 : 0;

                markRowDone(section, source, idx);
                injectAnalyticsButton(section, source, idx, data.is_mentioned);
                updatePctBadge(section, sectionAnalytics[storeKey].summary);

                // Update column counter badge live after each result
                const _cmc  = sectionAnalytics[storeKey].summary.mentioned_count;
                const _ctp  = sectionAnalytics[storeKey].summary.total_prompts;
                const _cpct = sectionAnalytics[storeKey].summary.percentage;
                $(`#sec${section}-${source}-text`).text(
                    `${_cmc}/${indices.length} mentions (${_cpct}%)`
                );

            } else {
                markRowError(section, source, idx, res.error || 'Server error');
            }
        })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || `Request failed (${xhr.status})`;
            markRowError(section, source, idx, msg);
        })
        .always(function () {
            processQueue(section, source, indices, allPrompts, pos + 1, $btn);
        });
    }

    // ── Queue finished ────────────────────────────────────────────
    function finishQueue(section, source, total, $btn) {
        const label = source === 'gemini' ? 'Gemini' : source === 'chatgpt' ? 'ChatGPT' : 'Search API';

        $btn.prop('disabled', false).html(label);
        $(`input.js-row-check[data-section="${section}"], #sec${section}-select-all`).prop('disabled', false);

        $(`#sec${section}-${source}-counter`)
            .removeClass('bg-warning text-dark bg-primary');

        const storeKey = section + '_' + source;
        const mc  = sectionAnalytics[storeKey]?.summary?.mentioned_count ?? 0;
        const pct = sectionAnalytics[storeKey]?.summary?.percentage ?? 0;
        $(`#sec${section}-${source}-text`).text(
            `${mc}/${total} mentions (${pct}%)`
        );

        showToast('success', 'Analysis complete!',
            `${mc} of ${total} prompts mention the client (${pct}%).`);
    }

    // ══════════════════════════════════════════════════════════════
    //  ROW STATE HELPERS  (now source-aware)
    // ══════════════════════════════════════════════════════════════

    function getActionCell(section, idx) {
        return $(`#sec${section}-row-${idx} .action-cell-${section} .d-flex`);
    }

    function markRowQueued(section, source, idx) {
        const $row = $(`#sec${section}-row-${idx}`);
        $row.removeClass('row-processing row-done row-error').addClass('row-queued');
        getActionCell(section, idx).find(`.queue-status-badge-${source}`).remove();
        getActionCell(section, idx).append(
            `<span class="queue-status-badge queue-status-badge-${source} qs-queued">Queued</span>`
        );
        // Reset only the relevant source column
        $(`#sec${section}-${source}-${idx}`).html('<span class="text-muted small">—</span>');
    }

    function markRowProcessing(section, source, idx) {
        $(`#sec${section}-row-${idx}`)
            .removeClass('row-queued row-done row-error').addClass('row-processing');
        getActionCell(section, idx).find(`.queue-status-badge-${source}`).remove();
        getActionCell(section, idx).append(
            `<span class="queue-status-badge queue-status-badge-${source} qs-processing">Analysing…</span>`
        );
    }

    function markRowDone(section, source, idx) {
        $(`#sec${section}-row-${idx}`)
            .removeClass('row-queued row-processing row-error').addClass('row-done');
        getActionCell(section, idx).find(`.queue-status-badge-${source}`).remove();
    }

    function markRowError(section, source, idx, msg) {
        $(`#sec${section}-row-${idx}`)
            .removeClass('row-queued row-processing row-done').addClass('row-error');
        getActionCell(section, idx).find(`.queue-status-badge-${source}`).remove();
        getActionCell(section, idx).append(
            `<span class="queue-status-badge queue-status-badge-${source} qs-error" title="${escHtml(msg)}">Error</span>`
        );
        $(`#sec${section}-${source}-${idx}`).html('<span class="text-muted small text-danger">Error</span>');
    }

    // ── Update queue counter badge ────────────────────────────────
    function updateQueueCounter(section, source, done, total) {
        $(`#sec${section}-${source}-text`).text(`${done} / ${total}`);
    }

    // ── Inject/update Analytics button + fill source column cell ─
    function injectAnalyticsButton(section, source, idx, isMentioned) {
        const $cell = getActionCell(section, idx);

        // Only add Analytics button once (shared by both sources)
        if ($cell.find('.btn-analytics').length === 0) {
            const $analyticsBtn = $('<button>')
                .addClass('btn btn-primary btn-sm btn-analytics')
                .attr({ 'data-section': section, 'data-idx': idx })
                .html('<i class="ri-bar-chart-2-line me-1"></i>Analytics');
            $cell.append($analyticsBtn);
        }

        // Fill the correct source column with Yes / No badge
        const yesHtml = `<span class="badge rounded-pill text-bg-success px-2 py-1">Yes</span>`;
        const noHtml  = `<span class="badge rounded-pill text-bg-danger  px-2 py-1">No</span>`;
        $(`#sec${section}-${source}-${idx}`).html(isMentioned ? yesHtml : noHtml);
    }

    // ── Update percentage badge on card header ────────────────────
    function updatePctBadge(section, summary) {
        const pct     = summary.percentage;
        const badgeId = `#sec${section}-pct-badge`;
        const textId  = `#sec${section}-pct-text`;

        let cls = 'pct-low';
        if (pct >= 75)      cls = 'pct-high';
        else if (pct >= 40) cls = 'pct-medium';

        $(badgeId)
            .removeClass('d-none pct-high pct-medium pct-low')
            .addClass(cls);
        $(textId).text(
            `${summary.mentioned_count}/${summary.total_prompts} mentions (${pct}%)`
        );
    }

    // ══════════════════════════════════════════════════════════════
    //  ANALYTICS BUTTON — open modal with per-prompt details (tabbed)
    // ══════════════════════════════════════════════════════════════
    $(document).on('click', '.btn-analytics', function () {
        const section = $(this).data('section');
        const idx     = parseInt($(this).data('idx'));

        // Gather data from whichever sources have run
        const chatgptData   = sectionAnalytics[section + '_chatgpt']   || null;
        const geminiData    = sectionAnalytics[section + '_gemini']     || null;
        const searchapiData = sectionAnalytics[section + '_searchapi']  || null;

        // At least one must exist
        if (!chatgptData && !geminiData && !searchapiData) return;

        const chatgptInfo   = chatgptData?.per_prompt[idx]   || null;
        const geminiInfo    = geminiData?.per_prompt[idx]    || null;
        const searchapiInfo = searchapiData?.per_prompt[idx] || null;

        if (!chatgptInfo && !geminiInfo && !searchapiInfo) return;

        // Use whichever is available for prompt preview
        const info = chatgptInfo || geminiInfo || searchapiInfo;

        const sectionLabel = { B: 'Brand-Neutral', C: 'Visibility Tracking', D: 'Competitor Trigger' };
        $('#analyticsModalTitle').text('Analytics — ' + (sectionLabel[section] || section) + ' Prompt #' + (idx + 1));
        $('#analyticsModalSub').text('Client mention analysis');

        // ── Determine which tabs to render ─────────────────────
        const tabs   = [];  // { id, label, icon, info, terms, responseKey }

        if (chatgptInfo !== null) {
            const terms = (chatgptData.summary.client_terms || []);
            tabs.push({ id: 'tab-chatgpt', label: 'ChatGPT', icon: 'ri-chat-ai-line', info: chatgptInfo, terms, responseKey: 'chatgpt_response' });
        }
        if (geminiInfo !== null) {
            const terms = (geminiData.summary.client_terms || []);
            tabs.push({ id: 'tab-gemini', label: 'Gemini', icon: 'ri-google-line', info: geminiInfo, terms, responseKey: 'gemini_response' });
        }
        if (searchapiInfo !== null) {
            const terms = (searchapiData.summary.client_terms || []);
            tabs.push({ id: 'tab-searchapi', label: 'Search API', icon: 'ri-search-eye-line', info: searchapiInfo, terms, responseKey: 'gemini_response' });
        }

        // ── Build tab nav ───────────────────────────────────────
        const firstTabId = tabs[0].id;

        const navItems = tabs.map((t, i) => {
            const isActive   = i === 0 ? 'active' : '';
            const ariaSelected = i === 0 ? 'true' : 'false';
            const mentionBadge = t.info.is_mentioned
                ? `<span class="badge rounded-pill text-bg-success ms-2" style="font-size:.65rem;">Mentioned</span>`
                : `<span class="badge rounded-pill text-bg-danger  ms-2" style="font-size:.65rem;">Not mentioned</span>`;
            return `
                <li class="nav-item" role="presentation">
                    <button
                        class="analytics-tab-btn nav-link ${isActive}"
                        id="${t.id}-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#${t.id}-pane"
                        type="button"
                        role="tab"
                        aria-controls="${t.id}-pane"
                        aria-selected="${ariaSelected}"
                    >${t.label}${mentionBadge}</button>
                </li>`;
        }).join('');

        // ── Build tab panes ─────────────────────────────────────
        const paneItems = tabs.map((t, i) => {
            const isActive = i === 0 ? 'show active' : '';
            const mentionStatus = t.info.is_mentioned
                ? `<span class="mention-status-yes">Yes — Mentioned</span>`
                : `<span class="mention-status-no">No — Not mentioned</span>`;

            return `
                <div
                    class="tab-pane fade ${isActive}"
                    id="${t.id}-pane"
                    role="tabpanel"
                    aria-labelledby="${t.id}-tab"
                >
                    {{-- Mention status + breakdown --}}
                    <div class="analytics-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="card-label mb-0">${t.label} — Client Mention Status</div>
                            ${mentionStatus}
                        </div>
                        ${buildBreakdownTable(t.terms, t.info.breakdown || {}, t.info.total_count)}
                    </div>

                    {{-- AI Response --}}
                    <div class="analytics-card">
                        <div class="card-label mb-3">${t.label} Response</div>
                        <div class="analytics-response-body">
                            ${t.info[t.responseKey] || t.info.gemini_response || t.info.chatgpt_response || '<em class="text-muted">No response available.</em>'}
                        </div>
                    </div>
                </div>`;
        }).join('');

        // ── Assemble full modal body ────────────────────────────
        const html = `
            {{-- Prompt preview (always shown above tabs) --}}
            <div class="prompt-preview-box mb-4">${escHtml(info.prompt)}</div>

            {{-- Tab nav --}}
            <ul class="nav analytics-tab-nav mb-3" id="analyticsTabNav" role="tablist">
                ${navItems}
            </ul>

            {{-- Tab content --}}
            <div class="tab-content" id="analyticsTabContent">
                ${paneItems}
            </div>
        `;

        $('#analyticsModalBody').html(html);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('analyticsModal')).show();
    });

    // Helper: build per-term breakdown table
    function buildBreakdownTable(clientTerms, breakdown, totalCount) {
        if (!clientTerms.length) return '';
        let rows = '';
        clientTerms.forEach(term => {
            const count = breakdown[term] ?? 0;
            rows += `<tr>
                <td><span class="fw-600">${escHtml(term)}</span></td>
                <td>${count > 0
                    ? `<span class="badge bg-success" style="font-size:.8rem;">${count} mention${count !== 1 ? 's' : ''}</span>`
                    : `<span class="text-muted">—</span>`}
                </td>
            </tr>`;
        });
        return `
            <div class="table-responsive mt-2">
                <table class="table table-sm breakdown-table mb-1">
                    <thead><tr><th>Term</th><th>Mentions</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
                <small class="text-muted">${totalCount} total mention${totalCount !== 1 ? 's' : ''}</small>
            </div>
        `;
    }

    // ══════════════════════════════════════════════════════════════
    //  SHOW MORE MODAL
    // ══════════════════════════════════════════════════════════════
    $(document).on('click', '.js-view-text', function () {
        const text      = $(this).data('text') || '';
        const title     = $(this).data('title') || 'Prompt';
        const wordCount = text.trim().split(/\s+/).filter(Boolean).length;

        $('#aioModalTitle').text(title);
        $('#aioModalWordCount').text(wordCount + ' words');
        $('#aio-modal-text').val(text);
        $('#btn-aio-copy').removeClass('copied').html('<i class="ri-file-copy-line me-1"></i>Copy');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('aioPromptModal')).show();
    });

    // ── Copy to clipboard ─────────────────────────────────────────
    $(document).on('click', '#btn-aio-copy', function () {
        const text = $('#aio-modal-text').val();
        if (!text) return;

        const $btn = $(this);
        const doFeedback = () => {
            $btn.addClass('copied').html('<i class="ri-check-line me-1"></i>Copied!');
            setTimeout(() => $btn.removeClass('copied').html('<i class="ri-file-copy-line me-1"></i>Copy'), 2200);
        };

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(doFeedback).catch(() => { fallbackCopy(text); doFeedback(); });
        } else {
            fallbackCopy(text); doFeedback();
        }
    });

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0;';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    // ══════════════════════════════════════════════════════════════
    //  TOAST HELPER
    // ══════════════════════════════════════════════════════════════
    function showToast(type, title, message) {
        const toastId = 'toast-' + Date.now();
        const bgMap = { success: 'success', error: 'danger', warning: 'warning', info: 'info' };
        const bg = bgMap[type] || 'secondary';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${bg} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong> ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>`;

        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060"></div>');
        }

        $('#toastContainer').append(toastHtml);
        const el    = document.getElementById(toastId);
        const toast = new bootstrap.Toast(el, { autohide: true, delay: 5000 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    // ── Helpers ───────────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

}(jQuery));
</script>