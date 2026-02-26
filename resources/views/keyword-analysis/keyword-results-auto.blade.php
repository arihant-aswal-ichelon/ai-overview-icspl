
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            Auto-Fetched Keywords Analysis
        </h5>
        <div class="text-muted">
            Keywords: {{ $keywords_count }}
        </div>
    </div>
        
    @if($cluster_request_status)
    <div class="card-body">
        <div class="alert alert-info mb-0">
            Showing the result of {{ date('j M, Y', strtotime($cluster_request->date_from)) }} to {{ date('j M, Y', strtotime($cluster_request->date_to)) }}. As its already save in database. Please re-submit the request to fetch updated data.
        </div>
    </div>
    @endif

</div>
<div class="card">

    <div class="card-body">
        <table class="table" id="keywordsTable">
            <thead>
                <tr>
                    <th scope="col">Keyword</th>
                    <th scope="col">Monthly Search</th>
                    <th scope="col">Competition</th>
                    <th scope="col">Low Bid (₹)</th>
                    <th scope="col">High Bid (₹)</th>
                    <th>Clicks</th>
                    <th>CTR (%)</th>
                    <th>Impressions</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($keywords)
                @foreach($keywords as $keyword_item)
                <tr>
                    <th scope="row">{{$keyword_item['keyword'] ?? $keyword_item['query']}}</th>
                    <td data-monthlysearch="{{$keyword_item['monthlysearch'] ?? 0 }}">{{$keyword_item['monthlysearch'] ?? 0 }}</td>
                    <td>{{$keyword_item['competition'] ?? 0 }}</td>
                    <td>{{$keyword_item['low_bid'] ? round($keyword_item['low_bid'] / 1000000, 2) : 0 }}</td>
                    <td>{{$keyword_item['high_bid'] ? round($keyword_item['high_bid'] / 1000000, 2) : 0 ?? 0 }}</td>
                    <td class="text-end">{{ $keyword_item['clicks'] ?? 0 }}</td>
                    <td class="text-end">
                        <span class="{{ $keyword_item['ctr'] > 5 ? 'text-success' : ($keyword_item['ctr'] > 2 ? 'text-warning' : 'text-danger') }}">
                            {{ $keyword_item['ctr'] }}%
                        </span>
                    </td>
                    <td class="text-end">{{ $keyword_item['impressions'] }}</td>
                    <td class="text-end">
                        <span class="{{ $keyword_item['position'] <= 3 ? 'text-success' : ($keyword_item['position'] <= 10 ? 'text-warning' : 'text-danger') }}">
                            {{ $keyword_item['position'] }}
                        </span>
                    </td>
                    <td>
                        @php
                        $keywordExists = \App\Models\KeywordPlanner::where('keyword_p', $keyword_item['keyword'] ?? $keyword_item['query'])
                        ->where('cluster_request_id', $cluster_request->id)
                        ->where('client_property_id', $client_property_id)
                        ->where('domainmanagement_id', $domainmanagement_id)
                        ->where('keyword_request_id', $keyword_request_id)
                        ->when(!empty($cluster_request->id), function ($q) use ($cluster_request) {
                            $q->where('cluster_request_id', $cluster_request->id);
                        })->first();
                        $aioExists = null;

                        if ($keywordExists) {
                        $aioExists = \App\Models\AiOverview::where('keyword_planner_id', $keywordExists->id)
                        ->where('client_property_id', $client_property_id)
                        ->where('domainmanagement_id', $domainmanagement_id)
                        ->where('keyword_request_id', $keyword_request_id)
                        ->when(!empty($cluster_request->id), function ($q) use ($cluster_request) {
                            $q->where('cluster_request_id', $cluster_request->id);
                        })->first();

                        $organicresultsExists = \App\Models\OrganicResult::where('keyword_planner_id', $keywordExists->id)
                        ->where('client_property_id', $client_property_id)
                        ->where('domainmanagement_id', $domainmanagement_id)
                        ->where('keyword_request_id', $keyword_request_id)
                        ->when(!empty($cluster_request->id), function ($q) use ($cluster_request) {
                            $q->where('cluster_request_id', $cluster_request->id);
                        })->first();
                        }

                        @endphp

                        @if($keywordExists && $organicresultsExists)
                        <a href="{{ url('extracted-aio-result/'.$keywordExists->id) }}" target="_blank" class="btn {{ $aioExists ? 'btn-primary' : 'btn-warning' }}">{{ $aioExists ? 'AIO Insights' : 'Search Insights' }}</a>
                        @else
                        <button class="btn btn-success get-aio-btn"
                            onclick="fetchAioResult(this,`{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}`)"
                            data-keyword="{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}"
                            id="aio-btn-{{ md5($keyword_item['keyword'] ?? $keyword_item['query']) }}">
                            Get Search Result
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endif

            </tbody>
        </table>
    </div>
</div>
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
@if(isset($lazy_load) && $lazy_load)
<script>
// Move this function to global scope
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
        url: "{{ route('get.aio.result') }}",
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

// Also move the resetButton function to global scope
function resetButton(button) {
    button.disabled = false;
    button.innerHTML = '<i class="fas fa-robot me-2"></i>Get Search Result';
}

function showAioModalDirect(data) {
    console.log("data: ", data);
    // Create and show modal with data
    const modalHtml = `
    <div class="modal fade" id="aioDirectModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-robot me-2"></i>
                        ${data.ai_overview ? 'AIO Insights' : 'Search Insights'} for: "${data.keyword}"
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ${data.ai_overview ? 
                        `<div class="p-3 bg-light rounded border">
                            ${data.ai_overview.markdown ? data.ai_overview.markdown : 'No AI Overview available'}
                        </div>` 
                        : 
                        '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No AI Overview available for this keyword. Showing search insights instead.</div>'
                    }
                    <div class="mt-3 p-3 border rounded">
                        <h6><i class="fas fa-key me-2"></i>Keyword Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Keyword:</strong> ${data.keyword}</p>
                                <p><strong>Status:</strong> ${data.ai_overview ? '<span class="badge bg-success">AIO Found ✓</span>' : '<span class="badge bg-warning">AIO Not Found</span>'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Keyword Planner ID:</strong> ${data.keyword_planner_id}</p>
                                <p><strong>AI Overview:</strong> ${data.ai_overview ? 'Available' : 'Not Available'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a target="_blank" href="/extracted-aio-result/${data.keyword_planner_id}" class="btn ${data.ai_overview ? 'btn-success' : 'btn-warning'}">
                        <i class="fas fa-external-link-alt me-2"></i>
                        ${data.ai_overview ? 'View AIO Insights' : 'View Search Insights'}
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
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
    const keyword_planner_id = data.keyword_planner_id;

    $('#aioDirectModal').on('hidden.bs.modal', function() {
        document.querySelectorAll('.get-aio-btn[disabled]').forEach(btn => {
            // Create a new link element
            const link = document.createElement('a');
            link.href = `/extracted-aio-result/${keyword_planner_id}`;
            link.className = `btn ${data.ai_overview ? 'btn-primary' : 'btn-warning'}`;
            link.innerHTML = data.ai_overview ?
                '<i class="fas fa-robot me-2"></i>AIO Insights' :
                '<i class="fas fa-search me-2"></i>Search Insights';
            link.target = '_blank';

            // Replace the button with the link
            btn.parentNode.replaceChild(link, btn);
        });

        $(this).remove();
    });
}

</script>
@endif
@if(isset($lazy_load) && $lazy_load)
<script>

window.aiCheckIntervals = {};
let globalCheckInterval = null;

// Initialize DataTable after DOM is ready
$(document).ready(function() {
    
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
    function checkAllAIStatus() {
        // Get all processing buttons
        const processingButtons = $('.processing-btn');
        if (processingButtons.length === 0) {
            clearGlobalInterval();
            return;
        }
        
        // Collect all child_keyword_ids from processing buttons
        const keywordids = [];
        processingButtons.each(function() {
            const keywordid = $(this).data('id');
            if (keywordid) {
                keywordids.push(keywordid);
            }
        });
        
        if (keywordids.length === 0) {
            return;
        }
        
        // Make single AJAX call for all IDs
        $.ajax({
            url: "{{ route('check-aistatus') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                child_keyword_ids: keywordids,
                _token: "{{ csrf_token() }}"
            },
            success: function(responses) {
                console.log("Bulk AI status response: ", responses);
                
                // Process each response
                $.each(responses, function(keywordid, response) {
                    if (response.success === 1 || response.success === 2) {
                        const buttonHtml = (response.hasAIOverview === 1)
                            ? `<a target="_blank" href="{{ url('extracted-aio-result/') }}/${keywordid}" 
                               class="btn btn-sm btn-primary">AIO Insights</a>`
                            : `<a target="_blank" href="{{ url('extracted-aio-result/') }}/${keywordid}" 
                               class="btn btn-sm btn-warning">Search Insights</a>`;
                        
                        $('#action-cell-' + keywordid).html(buttonHtml);
                        
                        // Remove the processing button from DOM
                        $('#action-cell-' + keywordid + ' .processing-btn').remove();
                    }
                });
                
                // Check if there are any processing buttons left
                if ($('.processing-btn').length === 0) {
                    clearGlobalInterval();
                }
            },
            error: function(xhr) {
                console.error('Error checking AI status:', xhr.responseText);
            }
        });
    }
    function clearGlobalInterval() {
        if (globalCheckInterval) {
            clearInterval(globalCheckInterval);
            globalCheckInterval = null;
            console.log('Cleared global interval');
        }
    }
    
    function clearAllAIIntervals() {
        clearGlobalInterval();
        
        // Clear individual intervals if any
        Object.keys(window.aiCheckIntervals).forEach(childKeywordId => {
            clearInterval(window.aiCheckIntervals[childKeywordId]);
            delete window.aiCheckIntervals[childKeywordId];
        });
        console.log('Cleared all intervals');
    }

    function clearAllAIIntervals() {
        clearGlobalInterval();
        
        // Clear individual intervals if any
        Object.keys(window.aiCheckIntervals).forEach(childKeywordId => {
            clearInterval(window.aiCheckIntervals[childKeywordId]);
            delete window.aiCheckIntervals[childKeywordId];
        });
        console.log('Cleared all intervals');
    }
    
    function initializeProcessingChecks() {
        // Clear any existing intervals first
        clearAllAIIntervals();
        
        // Check if processing buttons exist
        const processingButtons = $('.processing-btn');
        
        if (processingButtons.length > 0) {
            // Initial check
            checkAllAIStatus();
            
            // Set global interval for repeated checks
            if (!globalCheckInterval) {
                globalCheckInterval = setInterval(() => {
                    checkAllAIStatus();
                }, 60000);
                
                console.log('Started global interval');
            }
        } else {
            clearGlobalInterval();
        }
    }

    // Use a timeout to ensure table is in DOM
    setTimeout(function() {
        const tableSelector = '#keywordsTable';
        
        // Check if table exists
        if ($(tableSelector).length) {
            // Destroy existing instance if any
            if ($.fn.DataTable.isDataTable(tableSelector)) {
                $(tableSelector).DataTable().destroy();
                // Clear intervals when DataTable is destroyed
                clearAllAIIntervals();
            }
            
            // Initialize new DataTable
            try {
                const dataTable = $(tableSelector).DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[5, 'desc']], // Sort by CTR descending
                    autoWidth: false,
                    responsive: false,
                    scrollX: true, // Enable horizontal scrolling
                    scrollCollapse: true,
                    fixedColumns: false,
                    initComplete: function() {
                        // Initialize processing checks after table is fully initialized
                        setTimeout(initializeProcessingChecks, 500);
                    },
                    drawCallback: function() {
                        // Fix any column issues
                        this.api().columns.adjust();
                        
                        // Re-initialize processing checks after each draw
                        initializeProcessingChecks();
                    },
                    destroyCallback: function() {
                        clearAllAIIntervals();
                    }
                });
                
                // Also initialize checks initially
                initializeProcessingChecks();
                
            } catch (e) {
                console.error('DataTable initialization error:', e);
                // Keep basic table styling if DataTables fails
                initializeProcessingChecks();
            }
        } else {
            // If no DataTable, just initialize checks normally
            initializeProcessingChecks();
        }
    }, 100);

    // Clean up on page unload/refresh
    $(window).on('beforeunload', function() {
        clearAllAIIntervals();
    });
    
    // Optional: Clean up when navigating away via Turbolinks/Pjax
    $(document).on('pjax:beforeReplace', function() {
        clearAllAIIntervals();
    });

    function showToast(type, message) {
        const toastId = 'toast-' + Date.now();
        const icon = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        }[type] || 'info-circle';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${icon} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        $('#toastContainer').append(toastHtml);
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();

        // Remove toast after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function() {
            this.remove();
        });
    }
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
        formData += '&mode=' + mode + '&fetch_more=true&remaining_limit=' + $('#remainingKeywordCount').text();
        
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
});

</script>
@endif