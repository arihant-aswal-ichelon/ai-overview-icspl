@php
    $tableId = 'table-' . Str::slug($parent_keyword) . '-' . $parent_keyword_id . '-' . time();
@endphp

<table class="table " id="{{ $tableId }}">
    <thead>
        <tr>
            <th>#</th>
            <th width="25%">Child Keyword</th>
            <th>Monthly Searches</th>
            <th>Competition</th>
            <th>Low Bid</th>
            <th>High Bid</th>
            <th>Clicks</th>
            <th>CTR</th>
            <th>Impressions</th>
            <th>Position</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($keywords as $index => $keyword)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td><strong>{{ $keyword['keyword'] }}</strong></td>
            <td>{{ number_format($keyword['avg_monthly_searches']) }}</td>
            <td>{{ ucfirst($keyword['competition'] ?? 'N/A') }}</td>
            <td>${{ number_format($keyword['low_top_of_page_bid_micros'] / 1000000, 2) }}</td>
            <td>${{ number_format($keyword['high_top_of_page_bid_micros'] / 1000000, 2) }}</td>
            <td>{{ $keyword['clicks'] ?? '0' }}</td>
            <td>{{ $keyword['ctr'] ?? '0' }}</td>
            <td>{{ $keyword['impressions'] ?? '0' }}</td>
            <td>{{ $keyword['position'] ?? '0' }}</td>
            <td id="action-cell-{{ $keyword['child_keyword_id'] }}">
                <button class="btn btn-sm btn-info processing-btn" data-id="{{ $keyword['child_keyword_id'] }}" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Processing...
                </button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<script>
// Global object to store intervals
window.aiCheckIntervals = {};
let globalCheckInterval = null;

// Initialize DataTable after DOM is ready
$(document).ready(function() {
    function checkAllAIStatus() {
        // Get all processing buttons
        const processingButtons = $('.processing-btn');
        if (processingButtons.length === 0) {
            clearGlobalInterval();
            return;
        }
        
        // Collect all child_keyword_ids from processing buttons
        const childKeywordIds = [];
        processingButtons.each(function() {
            const childKeywordId = $(this).data('id');
            if (childKeywordId) {
                childKeywordIds.push(childKeywordId);
            }
        });
        
        if (childKeywordIds.length === 0) {
            return;
        }
        
        // Make single AJAX call for all IDs
        $.ajax({
            url: "{{ route('check-aistatus') }}",
            type: 'POST',
            dataType: 'json',
            data: {
                child_keyword_ids: childKeywordIds,
                _token: "{{ csrf_token() }}"
            },
            success: function(responses) {
                console.log("Bulk AI status response: ", responses);
                
                // Process each response
                $.each(responses, function(childKeywordId, response) {
                    if (response.success === 1 || response.success === 2) {
                        const buttonHtml = (response.hasAIOverview === 1)
                            ? `<a target="_blank" href="{{ url('extracted-aio-result/') }}/${childKeywordId}" 
                               class="btn btn-sm btn-primary">AIO Insights</a>`
                            : `<a target="_blank" href="{{ url('extracted-aio-result/') }}/${childKeywordId}" 
                               class="btn btn-sm btn-warning">Search Insights</a>`;
                        
                        $('#action-cell-' + childKeywordId).html(buttonHtml);
                        
                        // Remove the processing button from DOM
                        $('#action-cell-' + childKeywordId + ' .processing-btn').remove();
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
        const tableSelector = '#{{ $tableId }}';
        
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
                    order: [[7, 'desc']], // Sort by CTR descending
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
});
</script>