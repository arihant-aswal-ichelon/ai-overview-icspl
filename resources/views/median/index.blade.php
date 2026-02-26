@extends('layouts.page-app')

@section("content")
<style>
    
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
    .search-container {
        display: flex;
        gap: 20px;
        margin-top: 30px;
    }

    .left-results {
        width: 65%;
    }

    .right-panel {
        width: 30%;
        background: #fafafa;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #eee;
    }

    .result-item {
        margin-bottom: 25px;
    }

    .result-item a {
        font-size: 20px;
        color: #1a0dab;
        text-decoration: none;
    }

    .result-item .result-link {
        font-size: 14px;
        color: #006621;
    }

    .knowledge-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .knowledge-field {
        margin-bottom: 8px;
        font-size: 14px;
    }
</style>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Median Results for client: {{$client_name}}</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Median</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="live-preview">
                            <form id="keywordFilterForm" class="row g-3">
                                <div class="col-md-12" id="median_result_id">
                                    <label class="form-label">Select Median Result</label>
                                    <select name="median_result_id" class="form-select">
                                        <option value="">Select Median Name</option>

                                        @foreach($dropdownData as $data)
                                            <option value="{{ $data['median_id'] }}"> {{$data['median_name']}} -
                                                {{ \Carbon\Carbon::parse($data['date_from'])->format('d M Y') }}
                                                -
                                                {{ \Carbon\Carbon::parse($data['date_to'])->format('d M Y') }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" id="show_result">Show the Result</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            <div class="col-md-12">
                <div id="result_box"></div>
            </div>
            <!-- <div id="result_box"></div> -->

        </div>
        <!-- end row -->
    </div> <!-- container-fluid -->
<!-- Modal for fetching more keywords -->
<div class="modal fade" id="fetchMoreModal" tabindex="-1" aria-labelledby="fetchMoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fetchMoreModalLabel">Fetch More Keywords</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>We only retrieved <span id="currentKeywordCount">0</span> keywords out of 1000.</p>
                <p>Would you like to fetch the remaining <span id="remainingKeywordCount">0</span> keywords from Google Keyword Planner?</p>
                <div id="fetchProgress" class="d-none mt-3">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p class="text-center mt-2" id="progressText">Fetching keywords...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Current Results</button>
                <button type="button" class="btn btn-primary" id="confirmFetchMore">Yes, Fetch More Keywords</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section("jscontent")

<script>
    $(document).ready(function() {
    const masterKeywordInput = $('input[name="master_keyword"]');
    const showResultButton = $('#show_result');
    const filterTypeRadios = $('input[name="filter_type"]');
    
    // Function to check and update button state
    const keywordOnlyFilters = $('#monthly_searches, #competition, #bid_range, #ctr, #impressions, #clicks');

    function toggleFilters() {
            const checkedRadio = $('input[name="filter_type"]:checked');
            const isDomainMode = checkedRadio.val() === 'domain';
            const isKeywordMode = checkedRadio.val() === 'keyword';
            
            // Toggle keyword input visibility
            if (isDomainMode) {
                $('#domainInputGroup').show();
                $('#keywordInputGroup').hide();
                
                // Hide keyword-only filters
                keywordOnlyFilters.hide();
                
                // Clear keyword-only filter values
                $('input[name="min_searches"], input[name="max_searches"]').val('');
                $('select[name="competition"]').val('');
                $('input[name="min_bid"], input[name="max_bid"]').val('');
                $('input[name="min_ctr"], input[name="max_ctr"]').val('');
                $('input[name="min_clicks"], input[name="max_clicks"]').val('');
                $('input[name="min_impressions"], input[name="max_impressions"]').val('');
                
                // Show basic filters
                $('.filter-item:not(#monthly_searches, #competition, #bid_range, #ctr, #impressions, #clicks)').show();
                
            } else if (isKeywordMode) {
                $('#domainInputGroup').hide();
                $('#keywordInputGroup').show();
                
                // Show all filters
                $('.filter-item').show();
                keywordOnlyFilters.show();
            }
        }


    function checkButtonState() {
        const checkedRadio = $('input[name="filter_type"]:checked');
        let shouldDisable = false;
        
        if (checkedRadio.length > 0 && checkedRadio.val() === 'keyword') {
            const keywordValue = masterKeywordInput.val().trim();
            if (keywordValue === '') {
                shouldDisable = true;
            }
        }
        
        showResultButton.prop('disabled', shouldDisable);
        
        // Visual feedback
        if (shouldDisable) {
            showResultButton.css({
                'opacity': '0.6',
                'cursor': 'not-allowed'
            });
        } else {
            showResultButton.css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        }
    }
    
    // Event listeners
    toggleFilters();
    checkButtonState();

    masterKeywordInput.on('input', checkButtonState);
    filterTypeRadios.on('change', checkButtonState);

    filterTypeRadios.on('change', function() {
            toggleFilters();
            checkButtonState();
            
            // Clear results when switching modes
            $("#result_box").html('');
            
            // Clear all filter values
            if ($(this).val() === 'domain') {
                // Keep only domain-specific filters
                $('input[name="master_keyword"]').val('');
            } else if ($(this).val() === 'keyword') {
                // Keep all filters
                // Optionally clear domain-specific filters if needed
            }
        });

    
    // // Also check when the input group visibility changes
    // $('input[name="filter_type"]').on('change', function() {
    //     setTimeout(checkButtonState, 100); // Small delay to ensure DOM has updated
    // });
    
    // // Initial check
    // checkButtonState();
    
    // Prevent click when disabled
    showResultButton.on('click', function(e) {
            if ($(this).prop('disabled')) {
                e.preventDefault();
                e.stopPropagation();
                
                if ($('input[name="filter_type"]:checked').val() === 'keyword') {
                    alert('Please enter a keyword to search');
                    masterKeywordInput.focus();
                }
                return false;
            }
        });

});
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized');
    });
    $("#show_result").on("click", function(e) {
        e.preventDefault();
        
        let formData = $("#keywordFilterForm").serialize();
        const mode = $('input[name="filter_type"]:checked').val();
        formData += '&mode=' + mode;

        $.ajax({
            url: "{{ route('median-display') }}",
            type: "POST",
            data: formData,
            beforeSend: function() {
                $("#result_box").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3">
                            <h5>Loading Keywords...</h5>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 30%"></div>
                            </div>
                            <p class="text-muted mt-2">Fetching keywords and metrics...</p>
                        </div>
                    </div>
                `);
            },
            success: function(response) {
                $("#result_box").html(response.html);
                
                // Store keyword_request_id for later use
                console.log('response',response);
                if (response.data && response.data.keyword_request_id) {
                    sessionStorage.setItem('keyword_request_id', response.data.keyword_request_id);
                }

                if (response.total_keywords && response.total_keywords < 1000 && response.is_full_results === false) {
                    setTimeout(() => {
                        showFetchMoreModal(response.total_keywords, response.remaining_keywords);
                    }, 1000);
                }
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                $("#result_box").html("<p class='text-danger'>Something went wrong!</p>");
            }
        });
    });
    function showFetchMoreModal(currentCount, remainingCount) {
    $('#currentKeywordCount').text(currentCount);
    $('#remainingKeywordCount').text(remainingCount);
    
    const modal = new bootstrap.Modal(document.getElementById('fetchMoreModal'));
    modal.show();
}

// Handle the fetch more action
$('#confirmFetchMore').on('click', function() {
    const btn = $(this);
    const originalText = btn.text();
    
    // Show progress
    $('#fetchProgress').removeClass('d-none');
    btn.prop('disabled', true).text('Fetching...');
    
    // Get form data
    let formData = $("#keywordFilterForm").serialize();
    const mode = $('input[name="filter_type"]:checked').val();
    
    // Add flag to indicate we want more keywords
    formData += '&mode=' + mode + '&fetch_more=true&remaining_limit=' + $('#remainingKeywordCount').text()+'&current_extracted=' + $('#currentKeywordCount').text();
    
    $.ajax({
        url: "{{ route('keyword-store-more') }}", // You'll need to create this route
        type: "POST",
        data: formData,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = evt.loaded / evt.total * 100;
                    $('.progress-bar').css('width', percentComplete + '%');
                    $('#progressText').text('Fetching: ' + Math.round(percentComplete) + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            // Append the new keywords to existing results
            $("#result_box").append(response.html);
            
            // Hide modal and reset
            bootstrap.Modal.getInstance(document.getElementById('fetchMoreModal')).hide();
            btn.prop('disabled', false).text(originalText);
            $('#fetchProgress').addClass('d-none');
            $('.progress-bar').css('width', '0%');
            
            // Show success message
            showToast('Successfully fetched ' + response.additional_keywords + ' more keywords!');
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            btn.prop('disabled', false).text(originalText);
            $('#fetchProgress').addClass('d-none');
            alert('Failed to fetch more keywords. Please try again.');
        }
    });
});


    function fetchAioResult(clickedButton, keyword) {
        console.log("Fetching AIO for:", keyword);
        
        // Get the row data
        let keyword_planner_id = null;

        const row = clickedButton.closest('tr');
        const positionCell = row.cells[8];
        const isSet = positionCell && positionCell.textContent.trim() !== '';

        let keywordData;

        if (isSet) {
            // SET (full columns exist)
            keywordData = {
                keyword: keyword,
                monthly_search: row.cells[1].textContent.trim(),
                competition: row.cells[2].textContent.trim(),
                low_bid: row.cells[3].textContent.trim(),
                high_bid: row.cells[4].textContent.trim(),
                clicks: row.cells[5].textContent.trim(),
                ctr: row.cells[6].textContent.trim().replace('%', ''),
                impressions: row.cells[7].textContent.trim(),
                position: positionCell.textContent.trim()
            };
        } else {
            // NOT SET (limited columns)
            keywordData = {
                keyword: keyword,
                clicks: row.cells[1].textContent.trim(),
                ctr: row.cells[2].textContent.trim().replace('%', ''),
                impressions: row.cells[3].textContent.trim(),
                position: row.cells[4].textContent.trim(),
            };
        }
        
        console.log("Keyword data:", keywordData);
        
        // Get form data
        const client_property_id = $("input[name='client_property_id']").val();
        const domainmanagement_id = $("input[name='domainmanagement_id']").val();
        
        // Get keyword_request_id from sessionStorage
        const keyword_request_id = @json(session('keyword_request_id'));

        // Disable button
        clickedButton.disabled = true;
        clickedButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...';
        
        $.ajax({
            url: "{{ route('get.aio.result') }}", // Create this route
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                keyword: keyword,
                keyword_data: keywordData,
                keyword_request_id: keyword_request_id,
                client_property_id: client_property_id,
                domainmanagement_id: domainmanagement_id
            },
            success: function(response) {
                if (response.success) {
                    keyword_planner_id = response.keyword_planner_id;
                    console.log("output: ", response.keyword_planner_id, keyword_planner_id);
                    showAioModalDirect(response.data);

                } else {
                    alert('Error: ' + response.message);
                    resetButton(clickedButton);
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Failed to fetch AIO result');
                resetButton(clickedButton);
            }
        });
    }
    function showAioModalDirect(data) {
        console.log("data: ",data);
        // Create and show modal with data
        const modalHtml = `
        <div class="modal fade" id="aioDirectModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${data.ai_overview ? 'AIO Insights' : 'Search Insights'} for: "${data.keyword}"</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${data.ai_overview ? 
                            `<div class="p-3 bg-light rounded border">
                                ${data.ai_overview.markdown ? data.ai_overview.markdown : 'No AI Overview available'}
                            </div>` 
                            : 
                            '<p class="text-muted">No AI Overview available for this keyword. Showing search insights instead.</p>'
                        }
                        <div class="mt-3">
                            <p><strong>Keyword:</strong> ${data.keyword}</p>
                            <p><strong>Status:</strong> ${data.ai_overview ? 'AIO Found ✓' : 'AIO Not Found'}</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a target="_blank" href="/extracted-aio-result/${data.keyword_planner_id}" class="btn ${data.ai_overview ? 'btn-success' : 'btn-warning'}">
                            ${data.ai_overview ? 'View AIO Insights' : 'View Search Insights'}
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

        
        // Remove existing modal
        $('#aioDirectModal').remove();
        
        // Add new modal
        $('body').append(modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('aioDirectModal'));
        modal.show();
        
        // Reset button when modal is hidden
        keyword_planner_id = data.keyword_planner_id;

        $('#aioDirectModal').on('hidden.bs.modal', function () {
            
            // console.log("inside keyword_planner_id: ", keyword_planner_id);
            document.querySelectorAll('.get-aio-btn[disabled]').forEach(btn => {
                // Create a new link element
                const link = document.createElement('a');
                link.href = `/extracted-aio-result/${keyword_planner_id}`;
                link.className = `btn ${data.ai_overview ? 'btn-primary' : 'btn-warning'}`;
                link.textContent = data.ai_overview ? 'AIO Insights' : 'Search Insights';
        
                link.target = '_blank';
                
                // Replace the button with the link
                btn.parentNode.replaceChild(link, btn);
        
            });

            $(this).remove();
        });
    }

function resetButton(button) {
    button.disabled = false;
    button.innerHTML = 'Get AIO Result';
}

    
document.addEventListener('livewire:init', () => {
    // console.log(data);
    Livewire.on('aioModalClosed', (data) => {
        const keyword = data.keyword || data;
        resetButtonState(keyword);
    });
    
    Livewire.on('aioModalError', (data) => {
        const keyword = data.keyword || data;
        resetButtonState(keyword);
    });
});
    function resetButtonState(keyword) {
    const button = document.querySelector(`button[onclick*="${keyword}"]`);
    if (button) {
        button.disabled = false;
        button.innerHTML = 'Get AIO Result';
        button.classList.remove('disabled');
    }
}

    document.addEventListener('hidden.bs.modal', function (event) {
        if (event.target.id === 'aioModal') {
            // Find all loading buttons and reset them
            document.querySelectorAll('.get-aio-btn[disabled]').forEach(button => {
                button.disabled = false;
                button.innerHTML = 'Get AIO Result';
                button.classList.remove('disabled');
            });
        }
    });
    function showAioModal(status, data = null) {
        // Implement modal display logic here
        console.log(status, data);
        const modalEl = document.getElementById('aioModal');
        const modal = new bootstrap.Modal(modalEl, {
            backdrop: 'static', // optional
            keyboard: true
        });

        modal.show();
            // document.addEventListener('livewire:init', () => {
            //     // Listen for modal show event
            //     Livewire.on('showAioModal', () => {
            //         $('#aioModal').show();
            //     });
                
            //     // Listen for modal hide event
            //     Livewire.on('hideAioModal', () => {
            //         const modal = $('#aioModal');
            //         if (modal) {
            //             $('#aioModal').hide();
            //         }
            //     });
            // });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const domainInputGroup = document.getElementById('domainInputGroup');
        const keywordInputGroup = document.getElementById('keywordInputGroup');
        const domainInput = document.querySelector('input[name="domain_name"]');
        const keywordInput = document.querySelector('input[name="master_keyword"]');

        const filterTypeRadios = document.querySelectorAll('input[name="filter_type"]');

        function toggleInputs() {
            const checkedRadio = document.querySelector('input[name="filter_type"]:checked');

            if (checkedRadio && checkedRadio.value === 'domain') {
                domainInputGroup.style.display = 'block';
                keywordInputGroup.style.display = 'none';
                
                // Enable and make domain required
                domainInput.disabled = false;
                domainInput.required = true;
                domainInput.readOnly = true;
                
                // Disable and remove requirement from keyword
                keywordInput.disabled = true;
                keywordInput.required = false;
                $("#result_box").html('');
                // keywordInput.value = '';
                
            } else if (checkedRadio && checkedRadio.value === 'keyword') {
                domainInputGroup.style.display = 'none';
                keywordInputGroup.style.display = 'block';
                
                // Enable and make keyword required
                keywordInput.disabled = false;
                keywordInput.required = true;
                keywordInput.readOnly = false;
                
                // Disable and remove requirement from domain
                domainInput.disabled = true;
                domainInput.required = false;
                domainInput.readOnly = true;
                $("#result_box").html('');

                // domainInput.value = '';
            }
        }

        filterTypeRadios.forEach(radio => {
            radio.addEventListener('change', toggleInputs);
        });

        toggleInputs();
    });
</script>

@endsection