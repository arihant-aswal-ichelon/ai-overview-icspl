@extends('layouts.page-app')
@section("content")
<style>
    :root {
        --primary-color: #1a73e8;
        --secondary-color: #5f6368;
        --light-gray: #f8f9fa;
        --border-color: #dadce0;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        color: #202124;
        background-color: #fff;
        font-size: 14px;
    }

    .search-header {
        border-bottom: 1px solid var(--border-color);
        padding: 20px 0;
    }

    .search-form-container {
        max-width: 692px;
    }

    .logo {
        font-size: 1.8rem;
        font-weight: 500;
        color: var(--primary-color);
    }

    .search-input-group {
        border: 1px solid var(--border-color);
        border-radius: 24px;
        padding: 8px 16px;
        transition: box-shadow 0.2s;
    }

    .search-input-group:focus-within {
        box-shadow: 0 1px 6px rgba(32, 33, 36, 0.28);
    }

    .search-input {
        border: none;
        outline: none;
        flex: 1;
    }

    .search-input:focus {
        box-shadow: none;
    }

    .search-button {
        background-color: var(--primary-color);
        color: white;
        border-radius: 4px;
        padding: 8px 16px;
        font-size: 14px;
        border: none;
    }

    .search-tabs {
        border-bottom: 1px solid var(--border-color);
    }

    .search-tab {
        color: var(--secondary-color);
        padding: 12px 16px;
        text-decoration: none;
        border-bottom: 3px solid transparent;
    }

    .search-tab.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .search-tab:hover {
        color: var(--primary-color);
    }

    .search-results-container {
        max-width: 692px;
        margin: 0 auto;
    }

    .result-stats {
        color: #70757a;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .search-result {
        margin-bottom: 26px;
    }

    .result-url {
        color: #202124;
        font-size: 14px;
        display: flex;
        align-items: center;
        margin-bottom: 4px;
    }

    .result-title {
        color: var(--primary-color);
        font-size: 20px;
        font-weight: 400;
        line-height: 1.3;
        margin-bottom: 8px;
        text-decoration: none;
    }

    .result-title:hover {
        text-decoration: underline;
    }

    .result-snippet {
        color: #4d5156;
        line-height: 1.58;
        font-size: 14px;
    }

    .result-meta {
        color: #70757a;
        font-size: 12px;
    }

    .ai-overview {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 3px solid var(--primary-color);
    }

    .ai-overview h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: #202124;
    }

    .people-ask-section {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 30px;
    }

    .people-ask-title {
        padding: 16px 20px;
        background-color: var(--light-gray);
        font-weight: 500;
        margin: 0;
    }

    .people-ask-item {
        padding: 16px 20px;
        border-top: 1px solid var(--border-color);
    }

    .people-ask-question {
        font-weight: 500;
        margin-bottom: 8px;
        color: #202124;
    }

    .people-ask-answer {
        color: #4d5156;
        margin-bottom: 0;
    }

    .related-searches {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }

    .related-searches h4 {
        font-size: 16px;
        margin-bottom: 15px;
        color: #202124;
    }

    .related-search-item {
        display: block;
        color: var(--primary-color);
        text-decoration: none;
        padding: 6px 0;
    }

    .related-search-item:hover {
        text-decoration: underline;
    }

    .footer {
        background-color: var(--light-gray);
        padding: 20px 0;
        border-top: 1px solid var(--border-color);
        margin-top: 40px;
    }

    .pagination-container {
        margin-top: 40px;
    }

    .page-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        color: var(--primary-color);
        text-decoration: none;
        margin-right: 8px;
        border-radius: 4px;
    }

    .page-number.active {
        background-color: var(--primary-color);
        color: white;
    }

    .page-number:hover {
        background-color: rgba(26, 115, 232, 0.1);
    }

    @media (max-width: 768px) {
        .search-form-container {
            padding: 0 15px;
        }

        .search-results-container {
            padding: 0 15px;
        }

        .search-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
        }
    }

    .ai-overview {
        border: 1px solid #ddd;
        border-radius: 8px;
        background: #fff;
    }

    .ai-overview .card-title {
        color: #1a0dab;
        font-size: 1.5rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
    }

    .ai-overview h4 {
        color: #1a0dab;
        font-size: 1.2rem;
        font-weight: 500;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    .ai-overview p {
        color: #4d5156;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .ai-overview ul {
        margin-bottom: 1.5rem;
    }

    .ai-overview li {
        color: #4d5156;
        line-height: 1.6;
        margin-bottom: 0.5rem;
    }

    .ai-overview .bi-dot {
        color: #70757a;
    }

    .references-section {
        border-top: 1px solid #ddd;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }

    .references-section h5 {
        color: #70757a;
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .reference-item a {
        color: #1a0dab;
        font-size: 0.9rem;
    }

    .reference-item a:hover {
        text-decoration: underline;
    }

    .reference-item .badge {
        font-size: 0.75rem;
        padding: 0.2em 0.4em;
    }

    .paragraph-with-thumbnail img {
        border-radius: 4px;
    }

    .related-searches .badge {
        padding: 0.35em 0.65em;
        font-weight: normal;
        border: 1px solid #dadce0;
    }

    .related-searches .badge:hover {
        background-color: #f1f3f4 !important;
    }

    .result-stats {
        color: #70757a;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }


    /* Add a loading spinner style */
    .sync-loading {
        display: none;
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #1a73e8;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .btn-sync-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #sync_now i {
        margin-right: 5px;
    }

    /* AI Overview selector styles */
    .ai-selector-wrapper {
        position: relative;
    }

    .ai-selector-loading {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
</style>

<div class="page-content">
    <div class="container-fluid">
        <!-- Header with Search Bar -->
        <header class="search-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="panel panel-default">
                        <div class="panel-body">{{ $keywordRequest->keyword." > ".$keywordplanner->keyword_p}}</div>
                    </div>
                    <div class="btn-sync-wrapper">
                        <div class="sync-loading" id="sync_loading"></div>
                        <button type="button" id="sync_now" class="btn btn-primary ms-3">
                            <i class="bi bi-arrow-clockwise"></i> Sync Now
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-4">
            <div class="container-fluid">
                <div class="row">
                    <!-- Search Results -->

                    <div class="col-lg-12">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Keyword</th>
                                    @if(!filter_var($keywordRequest->keyword, FILTER_VALIDATE_URL))
                                    <th scope="col">Monthly Search</th>
                                    <th scope="col">Competition</th>
                                    <th scope="col">Low Bid (₹)</th>
                                    <th scope="col">High Bid (₹)</th>
                                    @else
                                    <th scope="col">Clicks</th>
                                    <th scope="col">CTR</th>
                                    <th scope="col">Impressions</th>
                                    <th scope="col">Position</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if($keywordplanner)
                                <tr>
                                    <th scope="row">{{$keywordplanner->keyword_p}}</th>
                                    @if(!filter_var($keywordRequest->keyword, FILTER_VALIDATE_URL))
                                    <td>{{$keywordplanner->monthlysearch_p}}</td>
                                    <td>{{$keywordplanner->competition_p}}</td>
                                    <td>{{$keywordplanner->low_bid_p}}</td>
                                    <td>{{$keywordplanner->high_bid_p}}</td>
                                    @else
                                    <td>{{$keywordplanner->clicks_p}}</td>
                                    <td>{{$keywordplanner->ctr_p}}</td>
                                    <td>{{$keywordplanner->impressions_p}}</td>
                                    <td>{{$keywordplanner->position_p}}</td>
                                    @endif
                                </tr>
                                @endif

                            </tbody>
                        </table>
                    </div>
                    <div class="col-lg-8">
                        <div class="search-results-container">
                            @php
                            // Parse the JSON data if it exists
                            $jsonData = null;
                            if (!empty($keywordRequest->json)) {
                            $jsonData = json_decode($keywordRequest->json, true);
                            }

                            // Get text blocks if available
                            $textBlocks = [];
                            if (!empty($keywordRequest->text_blocks)) {
                            $textBlocks = json_decode($keywordRequest->text_blocks, true);
                            }

                            // Get markdown if available
                            $markdown = $aiOverview[0]->markdown ?? null;
                            @endphp

                            <!-- Result Stats -->
                            @if($jsonData && isset($jsonData['search_metadata']))
                            <div class="result-stats">
                                Request completed in {{ $jsonData['search_metadata']['total_time_taken'] ?? '0.00' }} seconds
                            </div>
                            @endif

                            <!-- AI Overview Section -->
                            @if($markdown || $textBlocks)
                            <div id="current_ai_overview" data-aioverview_id="{{$aiOverview[0]->id}}" class="ai-overview card mb-4">
                                <div class="card-body">
                                    <h3 class="card-title">AI Overview</h3>

                                    <!-- Display markdown if available -->
                                    @if($markdown)
                                    <div class="markdown-content">
                                        @php
                                        $sections = preg_split('/\n\n+/', $aiOverview[0]->markdown);
                                        @endphp

                                        @foreach($sections as $section)
                                        @if(str_starts_with(trim($section), 'While') || str_starts_with(trim($section), 'Doctors in India') || str_starts_with(trim($section), 'Clinics and other specialists'))
                                        <!-- Paragraph section -->
                                        <div class="mb-3">
                                            @if(str_starts_with(trim($section), 'Doctors in India') || str_starts_with(trim($section), 'Clinics and other specialists'))
                                            <h4 class="mb-2">{{ trim($section) }}</h4>
                                            @else
                                            <p class="mb-2">{{ trim($section) }}</p>
                                            @endif
                                        </div>
                                        @elseif(str_starts_with(trim($section), '- '))
                                        <!-- List section -->
                                        <ul class="list-unstyled ms-3">
                                            @foreach(explode("\n", $section) as $line)
                                            @if(str_starts_with(trim($line), '- '))
                                            <li class="mb-1">
                                                <i class="bi bi-dot me-1"></i>
                                                {{ str_replace('- ', '', trim($line)) }}
                                            </li>
                                            @endif
                                            @endforeach
                                        </ul>
                                        @elseif(preg_match('/^\[\[\d+\] - /', trim($section)))
                                        <!-- References section -->
                                        <div class="references-section mt-4">
                                            <h5>References</h5>
                                            <div class="references-list">
                                                @foreach(explode("\n", $section) as $line)
                                                @if(!empty(trim($line)))
                                                @php
                                                // Extract reference number and link
                                                preg_match('/\[\[(\d+)\] - (.+?)\]\((https?:\/\/.+?)\)/', trim($line), $matches);
                                                @endphp
                                                @if(count($matches) === 4)
                                                <div class="reference-item mb-1">
                                                    <a href="{{ $matches[3] }}" target="_blank" class="text-decoration-none">
                                                        <span class="badge bg-secondary me-2">[{{ $matches[1] }}]</span>
                                                        {{ $matches[2] }}
                                                    </a>
                                                </div>
                                                @endif
                                                @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        @endforeach
                                    </div>
                                    @endif

                                    <!-- Display text_blocks if available -->
                                    @if($textBlocks)
                                    <div class="text-blocks-content">
                                        @foreach($textBlocks as $block)
                                        @if($block['type'] == 'paragraph' && isset($block['answer']) && !isset($block['items']))
                                        <!-- Regular paragraph -->
                                        @if(isset($block['thumbnail']))
                                        <div class="paragraph-with-thumbnail mb-3 d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <img src="{{ $block['thumbnail'] }}" alt="Thumbnail" class="img-thumbnail" style="max-width: 100px;">
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="mb-2">{{ $block['answer'] }}</p>
                                                @if(isset($block['link']))
                                                <a href="{{ $block['link'] }}" target="_blank" class="text-decoration-none small">
                                                    Source
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                        @else
                                        <!-- Regular paragraph without thumbnail -->
                                        <div class="mb-3">
                                            @if(isset($block['link']) && str_contains($block['answer'], 'Doctors in India') || str_contains($block['answer'], 'Clinics and other specialists'))
                                            <h4 class="mb-2">{{ $block['answer'] }}</h4>
                                            @else
                                            <p class="mb-2">{{ $block['answer'] }}</p>
                                            @if(isset($block['link']))
                                            <a href="{{ $block['link'] }}" target="_blank" class="text-decoration-none small">
                                                Source
                                            </a>
                                            @endif
                                            @endif
                                        </div>
                                        @endif
                                        @elseif($block['type'] == 'unordered_list')
                                        <!-- Unordered list -->
                                        <ul class="list-unstyled ms-3">
                                            @foreach($block['items'] as $item)
                                            <li class="mb-3">
                                                <div class="d-flex align-items-start">
                                                    <i class="bi bi-dot me-2 mt-1"></i>
                                                    <div class="flex-grow-1">
                                                        <p class="mb-1">{{ $item['answer'] }}</p>

                                                        <!-- Related searches -->
                                                        @if(isset($item['related_searches']))
                                                        <div class="related-searches mt-2">
                                                            <small class="text-muted d-block mb-1">Related searches:</small>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                @foreach($item['related_searches'] as $search)
                                                                <a href="{{ $search['link'] }}" target="_blank" class="badge bg-light text-dark text-decoration-none">
                                                                    {{ $search['query'] }}
                                                                </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        @endif

                                                        <!-- Source link -->
                                                        @if(isset($item['link']))
                                                        <a href="{{ $item['link'] }}" target="_blank" class="text-decoration-none small">
                                                            Source
                                                        </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </li>
                                            @endforeach
                                        </ul>
                                        @endif
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <!-- People Also Ask Section -->
                            @if($relatedQuestions)

                            <div class="people-ask-section">
                                <h4 class="people-ask-title">People also ask</h4>

                                <!-- Main Question -->
                                @foreach($relatedQuestions as $question)
                                <div class="people-ask-item">
                                    <div class="people-ask-question" data-bs-toggle="collapse" data-bs-target="#mainAnswer{{$question->id}}" aria-expanded="false" aria-controls="mainAnswer{{$question->id}}">
                                        <span>{{ $question->question }}</span>
                                        <i class="bi bi-chevron-down question-icon"></i>
                                    </div>
                                    <div class="collapse" id="mainAnswer{{$question->id}}">
                                        <p class="people-ask-answer">{{ $question->answer }}</p>
                                        @if($question->source_title && $question->source_link)
                                        <div class="source-info mt-2">
                                            <small>
                                                Source:
                                                <a href="{{ $question->source_link }}" target="_blank" class="text-decoration-none">
                                                    {{ $question->source_title }}
                                                </a>
                                            </small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            <!-- Main Organic Result -->
                            @if($organicResults)
                            @foreach($organicResults as $result)
                            <div class="search-result">
                                <div class="result-url">
                                    <span class="text-success me-1">
                                        <i class="bi bi-globe"></i>
                                    </span>
                                    {{ $result->domain ?? 'N/A' }}
                                </div>
                                <a href="{{ $result->link }}" class="result-title" target="_blank">
                                    {{ $result->title }}
                                </a>
                                <p class="result-snippet">{{ $result->snippet }}</p>
                                <div class="result-meta">
                                    {{ $result->displayed_link }}
                                </div>

                                <!-- Sitelinks -->
                                @if($result->sitelinks)
                                @php
                                $sitelinks = json_decode($result->sitelinks, true);
                                @endphp
                                @if(isset($sitelinks['list']) && count($sitelinks['list']) > 0)
                                <div class="sitelinks mt-3">
                                    @foreach($sitelinks['list'] as $sitelink)
                                    <div class="sitelink-item mb-2">
                                        <a href="{{ $sitelink['link'] }}" class="sitelink-title" target="_blank">
                                            {{ $sitelink['title'] }}
                                        </a>
                                        <div class="sitelink-meta">
                                            {{ $sitelink['answer_count'] ?? 0 }} answers · {{ $sitelink['date'] ?? '' }}
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                @endif
                            </div>
                            @endforeach
                            @endif


                            <!-- Related Searches -->
                            @if($relatedSearches)
                            <div class="related-search">
                                <div class="related-search-title">Related searches</div>
                                <div>
                                    @foreach($relatedSearches as $relatedSearch)
                                    <a href="{{ $relatedSearch->link }}" class="related-search-item"><i class="bi bi-search"></i> {{ $relatedSearch->query }}</a>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                    <!-- Sidebar (optional for additional data) -->
                    <div class="col-lg-4">
                        <div class="search-sidebar">
                            <!-- Keywords stats or other info can go here -->
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Keyword Information</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <small class="text-muted">Keyword:</small><br>
                                            <strong>{{ $keywordRequest->keyword }}</strong>
                                        </li>
                                        <li class="mb-2">
                                            <small class="text-muted">Position:</small><br>
                                            <strong>#{{ $keywordplanner->position_p ?? '' }}</strong>
                                        </li>
                                        <li class="mb-2">
                                            <small class="text-muted">Requested:</small><br>
                                            <strong>{{ \Carbon\Carbon::parse($keywordRequest->created_at)->format('M d, Y H:i') }}</strong>
                                        </li>
                                        @if($jsonData && isset($jsonData['search_metadata']))
                                        <li class="mb-2">
                                            <small class="text-muted">Request Time:</small><br>
                                            <strong>{{ $jsonData['search_metadata']['total_time_taken'] }}s</strong>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <label for="sync_ai_overview_data" class="form-label">Sync AI Overview Data</label>
                                    <select class="form-select" id="sync_ai_overview_data" required="">
                                        <option selected="" value="">Choose...</option>
                                        <?php foreach ($aiOverview as $aiOverview_data) { ?>
                                            <option value="{{ $aiOverview_data['id']}}">{{ \Carbon\Carbon::parse($aiOverview_data->created_at)->format('M d, Y H:i') }}</option>
                                        <?php } ?>
                                    </select>
                                    <div class="spinner-border spinner-border-sm ai-selector-loading" role="status"><span class="visually-hidden">Loading...</span></div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    <i class="bi bi-info-circle"></i> Select a version to preview different AI overview data
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection

@section("jscontent")
<script>
    $(document).ready(function() {
        // People Also Ask toggle functionality
        $('.people-ask-question').on('click', function() {
            const answer = $(this).next('.collapse');
            answer.collapse('toggle');
        });

        // Sync Now button click handler
        $('#sync_now').on('click', function() {
            const button = $(this);
            const loadingSpinner = $('#sync_loading');

            // Disable button and show loading
            button.prop('disabled', true);
            button.html('<i class="bi bi-arrow-clockwise"></i> Syncing...');
            loadingSpinner.show();

            // Get the keyword request ID from URL
            const urlParts = window.location.pathname.split('/');
            const keywordPlannerId = {{$keywordplanner->id}};
            console.log("keywordPlannerId: ", keywordPlannerId);

            // Send AJAX request
            $.ajax({
                url: '{{ route("sync-aio-data") }}',
                type: 'POST',
                data: {
                    keyword_planner_id: keywordPlannerId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        if(response.ai_status){
                            showAlert('success', "Sync completed successfully! We'll reload the page. ");
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        }else{
                            showAlert('warning', response.message);
                            resetButton(); 
                        }
                    } else {
                        console.log("error",response);
                        showAlert('error', response.message || 'Sync failed!');
                        resetButton();
                    }
                },
                error: function(xhr) {
                    console.error('Sync error:', xhr);
                    showAlert('error', 'An error occurred during sync. Please try again.');
                    resetButton();
                }
            });

            function resetButton() {
                button.prop('disabled', false);
                button.html('<i class="bi bi-arrow-clockwise"></i> Sync Now');
                loadingSpinner.hide();
            }


        });
        hideCurrentAiOverviewOption();


        // Sync AI Overview dropdown change handler
        $('#sync_ai_overview_data').on('change', function() {
            const aiOverviewId = $(this).val();
            if (!aiOverviewId) return;

            $('#current_ai_overview').attr('data-aioverview_id', aiOverviewId).data('aioverview_id', aiOverviewId);

            hideCurrentAiOverviewOption();

            // (optional) also update via jQuery data cache
            // $('#current_ai_overview').data('aioverview_id', aiOverviewId);
            console.log("aiOverviewId", aiOverviewId);

            const selectElement = $(this);
            const loadingSpinner = selectElement.closest('.ai-selector-wrapper').find('.ai-selector-loading');

            // Disable select and show loading
            // selectElement.prop('disabled', true);
            loadingSpinner.show();

            // Send AJAX request to get AI overview data
            $.ajax({
                url: '{{ route("sync-ai-overview") }}',
                type: 'POST',
                data: {
                    ai_overview_id: aiOverviewId,
                    keyword_planner_id: '{{ $keywordplanner->id }}',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // Update the AI Overview section with new data
                        updateAiOverviewSection(response.data);
                        console.log(response.data);
                        // Show success message
                        showAlert('success', 'AI Overview data loaded successfully!');

                        // Update the selected option to show it's loaded
                        selectElement.find('option').removeClass('text-primary fw-bold');
                        selectElement.find('option[value="' + aiOverviewId + '"]').addClass('text-primary fw-bold');
                    } else {
                        showAlert('error', response.message || 'Failed to load AI overview data');
                        // Reset to previous selection
                        selectElement.val(selectElement.data('previous-value') || '');
                    }
                },
                error: function(xhr) {
                    console.error('AI Overview load error:', xhr);
                    showAlert('error', 'An error occurred while loading AI overview data');
                    selectElement.val(selectElement.data('previous-value') || '');
                },
                complete: function() {
                    // Re-enable select and hide loading
                    // selectElement.prop('disabled', false);
                    loadingSpinner.hide();

                    // Store current value for next time
                    selectElement.data('previous-value', aiOverviewId);
                }
            });
        });

        // Store initial value
        $('#sync_ai_overview_data').data('previous-value', $('#sync_ai_overview_data').val());

        function updateAiOverviewSection(data) {
            const aiOverviewSection = $('#current_ai_overview');

            // Create the HTML for the AI Overview section
            let html = `
            <div class="card-body">
                <h3 class="card-title">AI Overview</h3>
                <div class="ai-version-badge mb-3">
                    <span class="badge bg-info">
                        <i class="bi bi-calendar-check me-1"></i>
                        Version: ${data.created_at_formatted}
                    </span>
                </div>
    `;

            // Handle markdown content
            if (data.markdown) {
                html += '<div class="markdown-content">';
                const sections = data.markdown.split('\n\n');

                sections.forEach(section => {
                    const trimmedSection = section.trim();
                    if (trimmedSection) {
                        if (trimmedSection.startsWith('While') ||
                            trimmedSection.startsWith('Doctors in India') ||
                            trimmedSection.startsWith('Clinics and other specialists')) {
                            if (trimmedSection.startsWith('Doctors in India') ||
                                trimmedSection.startsWith('Clinics and other specialists')) {
                                html += `<h4 class="mb-2">${trimmedSection}</h4>`;
                            } else {
                                html += `<p class="mb-2">${trimmedSection}</p>`;
                            }
                        } else if (trimmedSection.startsWith('- ')) {
                            html += '<ul class="list-unstyled ms-3">';
                            trimmedSection.split('\n').forEach(line => {
                                if (line.trim().startsWith('- ')) {
                                    html += `
                                <li class="mb-1">
                                    <i class="bi bi-dot me-1"></i>
                                    ${escapeHtml(line.trim().substring(2))}
                                </li>
                            `;
                                }
                            });
                            html += '</ul>';
                        } else if (trimmedSection.match(/^\[\[\d+\] - /)) {
                            html += `
                        <div class="references-section mt-4">
                            <h5>References</h5>
                            <div class="references-list">
                    `;

                            trimmedSection.split('\n').forEach(line => {
                                const trimmedLine = line.trim();
                                if (trimmedLine) {
                                    const match = trimmedLine.match(/\[\[(\d+)\] - (.+?)\]\((https?:\/\/.+?)\)/);
                                    if (match) {
                                        html += `
                                    <div class="reference-item mb-1">
                                        <a href="${match[3]}" target="_blank" class="text-decoration-none">
                                            <span class="badge bg-secondary me-2">[${match[1]}]</span>
                                            ${escapeHtml(match[2])}
                                        </a>
                                    </div>
                                `;
                                    }
                                }
                            });

                            html += `
                            </div>
                        </div>
                    `;
                        } else {
                            // Handle any other content that doesn't match the patterns
                            html += `<p class="mb-2">${escapeHtml(trimmedSection)}</p>`;
                        }
                    }
                });

                html += '</div>';
            }

            // Handle text blocks - FIXED: Check if it's an array and handle parsing
            let textBlocks = data.text_blocks;

            // If text_blocks is a string, try to parse it as JSON
            if (typeof textBlocks === 'string') {
                try {
                    textBlocks = JSON.parse(textBlocks);
                } catch (e) {
                    console.error('Failed to parse text_blocks JSON:', e);
                    textBlocks = null;
                }
            }

            // Check if textBlocks is an array and has items
            if (Array.isArray(textBlocks) && textBlocks.length > 0) {
                html += '<div class="text-blocks-content">';

                textBlocks.forEach(block => {
                    // Check if block exists and has the expected properties
                    if (!block) return;

                    const blockType = block.type || 'paragraph';
                    const blockAnswer = block.answer || '';
                    const blockItems = block.items || [];
                    const blockThumbnail = block.thumbnail || null;
                    const blockLink = block.link || null;

                    if (blockType === 'paragraph' && blockAnswer && blockItems.length === 0) {
                        if (blockThumbnail) {
                            html += `
                        <div class="paragraph-with-thumbnail mb-3 d-flex">
                            <div class="flex-shrink-0 me-3">
                                <img src="${blockThumbnail}" alt="Thumbnail" class="img-thumbnail" style="max-width: 100px;">
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-2">${escapeHtml(blockAnswer)}</p>
                    `;
                            if (blockLink) {
                                html += `<a href="${blockLink}" target="_blank" class="text-decoration-none small">Source</a>`;
                            }
                            html += '</div></div>';
                        } else {
                            html += '<div class="mb-3">';
                            if (blockLink && (blockAnswer.includes('Doctors in India') ||
                                    blockAnswer.includes('Clinics and other specialists'))) {
                                html += `<h4 class="mb-2">${escapeHtml(blockAnswer)}</h4>`;
                            } else {
                                html += `<p class="mb-2">${escapeHtml(blockAnswer)}</p>`;
                                if (blockLink) {
                                    html += `<a href="${blockLink}" target="_blank" class="text-decoration-none small">Source</a>`;
                                }
                            }
                            html += '</div>';
                        }
                    } else if (blockType === 'unordered_list' && Array.isArray(blockItems) && blockItems.length > 0) {
                        html += '<ul class="list-unstyled ms-3">';
                        blockItems.forEach(item => {
                            if (!item) return;

                            const itemAnswer = item.answer || '';
                            const itemLink = item.link || null;
                            const relatedSearches = item.related_searches || [];

                            html += `
                        <li class="mb-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-dot me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <p class="mb-1">${escapeHtml(itemAnswer)}</p>
                    `;

                            // Related searches
                            if (Array.isArray(relatedSearches) && relatedSearches.length > 0) {
                                html += `
                            <div class="related-searches mt-2">
                                <small class="text-muted d-block mb-1">Related searches:</small>
                                <div class="d-flex flex-wrap gap-2">
                        `;
                                relatedSearches.forEach(search => {
                                    if (search && search.link && search.query) {
                                        html += `
                                    <a href="${search.link}" target="_blank" class="badge bg-light text-dark text-decoration-none">
                                        ${escapeHtml(search.query)}
                                    </a>
                                `;
                                    }
                                });
                                html += '</div></div>';
                            }

                            // Source link
                            if (itemLink) {
                                html += `<a href="${itemLink}" target="_blank" class="text-decoration-none small">Source</a>`;
                            }

                            html += '</div></div></li>';
                        });
                        html += '</ul>';
                    }
                });

                html += '</div>';
            } else if (textBlocks && typeof textBlocks === 'object' && !Array.isArray(textBlocks)) {
                // If text_blocks is an object but not an array, try to handle it
                html += '<div class="text-blocks-content">';
                html += `<p>${escapeHtml(JSON.stringify(textBlocks))}</p>`;
                html += '</div>';
            }

            html += '</div>';

            // Update the section
            aiOverviewSection.html(html);
        }

        // Helper function to escape HTML to prevent XSS
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

    function showAlert(type, message) {
        // Remove any existing alerts
        let alertClass = '';

        switch (type) {
            case 'success':
                alertClass = 'alert-success';
                break;
            case 'error':
                alertClass = 'alert-danger';
                break;
            case 'warning':
                alertClass = 'alert-warning';
                break;
            default:
                alertClass = 'alert-info'; 
        }
        $('.sync-alert').remove();

        // Create alert element
        const alertHtml = `
                        <div class="alert ${alertClass} sync-alert alert-dismissible fade show position-fixed" 
                             style="top: 20px; right: 20px; z-index: 1050;">
                            ${message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;

        $('body').append(alertHtml);

        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.sync-alert').alert('close');
        }, 5000);
    }

    function hideCurrentAiOverviewOption() {
        const currentId = $('#current_ai_overview').attr('data-aioverview_id');

        $('#sync_ai_overview_data option').each(function() {
            const optionValue = $(this).val();

            // Skip placeholder
            if (!optionValue) return;

            if (optionValue == currentId) {
                $(this).hide(); // hide matching option
            } else {
                $(this).show(); // show others
            }
        });
    }
</script>
@endsection