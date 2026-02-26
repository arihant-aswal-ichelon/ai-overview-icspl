<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GSC Data with Queue Processing</title>
    
    <!-- Your existing CSS links -->
    <link href="https://phpstack-1350015-6098825.cloudwaysapps.com/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="https://phpstack-1350015-6098825.cloudwaysapps.com/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="https://phpstack-1350015-6098825.cloudwaysapps.com/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" />
    
    <style>
        /* Status badges */
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }
        
        .status-processing {
            background-color: #cff4fc;
            color: #055160;
            animation: pulse 1.5s infinite;
        }
        
        .status-yes {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-no {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-error {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Sync button styles */
        .sync-btn {
            padding: 2px 8px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .sync-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .sync-btn.syncing {
            position: relative;
            color: transparent !important;
        }
        
        .sync-btn.syncing::after {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            top: 50%;
            left: 50%;
            margin: -6px 0 0 -6px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Stats bar */
        .stats-bar {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #0d6efd;
        }
        
        .stat-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 14px;
        }
        
        .stat-value {
            font-weight: bold;
            font-size: 16px;
        }
        
        /* Progress bar */
        .progress-container {
            margin-top: 10px;
        }
        
        .progress-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper">
        <div class="page-container">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- start page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Aayna Clinic Data</h4>
                                <div>
                                    <button id="syncAllBtn" class="btn btn-primary btn-sm">
                                        <i class="fas fa-sync-alt"></i> Re-process All
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="stats-bar">
                                <div class="progress-container">
                                    <div class="progress-label" id="progressText">Initializing...</div>
                                    <div class="progress" style="height: 8px;">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                                <div class="card-body">
                                    <table id="" class="table nowrap align-middle" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Keyword Query</th>
                                                <th scope="col">Click | Impression | CTR | Position</th>
                                                <th scope="col">AIO Status</th>
                                                <th scope="col">Client Mentioned Status</th>
                                                <th scope="col" width="100">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($processedData as $gsc)
                                            <tr id="row-{{ $gsc['index'] }}" 
                                                data-index="{{ $gsc['index'] }}" 
                                                data-keyword="{{ $gsc['query'] }}"
                                                data-processed="{{ $gsc['aio_status'] == 'Processing' ? 'false' : 'true' }}">
                                                <td>{{ $gsc['index'] + 1 }}</td>
                                                <td id="aio-keyword-{{ $gsc['index'] }}">{{ $gsc['query'] }}</td>
                                                <td data-clicks="{{ $gsc['clicks'] }}">
                                                    {{ $gsc['clicks'] . ' | ' . $gsc['impressions'] . ' | ' . $gsc['ctr'] . ' | ' . $gsc['position'] }}
                                                </td>
                                                <td>
                                                    <span id="aio-status-{{ $gsc['index'] }}" 
                                                          class="status-badge status-{{ strtolower($gsc['aio_status']) }}">
                                                        {{ $gsc['aio_status'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span id="client-status-{{ $gsc['index'] }}" 
                                                          class="status-badge status-{{ strtolower($gsc['client_mentioned']) }}">
                                                        {{ $gsc['client_mentioned'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary sync-btn sync-single" 
                                                            data-index="{{ $gsc['index'] }}" 
                                                            data-keyword="{{ $gsc['query'] }}">
                                                        <i class="fas fa-sync-alt"></i> Sync
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
    <script src="https://phpstack-1350015-6098825.cloudwaysapps.com/assets/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        let progressCompleteShown = false;
        let allKeywords = [];
        let csrfToken = $('meta[name="csrf-token"]').attr('content');
        let sessionId = '{{ $sessionId }}';
        let totalKeywords = parseInt('{{ count($processedData) }}');
        let pollInterval = null;
        let processedIndexes = new Set();
        
        console.log('Total keywords:', totalKeywords);
        
        // Populate the array with all data from the table
        function populateKeywordsArray() {
            allKeywords = [];
            
            @foreach($processedData as $gsc)
                allKeywords.push({
                    index: {{$gsc['index']}},
                    keyword: "{{addslashes($gsc['query'])}}"
                });
            @endforeach
            
            console.log('All keywords populated:', allKeywords.length);
        }
        
        populateKeywordsArray();
        
        // Function to update stats
        function updateStats() {
            let processedRows = 0;
            let processingRows = 0;
            
            allKeywords.forEach(item => {
                const index = item.index;
                
                if (processedIndexes.has(index)) {
                    processedRows++;
                } else {
                    processingRows++;
                }
            });
            
            const percent = totalKeywords > 0 ? Math.round((processedRows / totalKeywords) * 100) : 0;
            
            if (processingRows === 0) {
                $('#progressText').text(`${processedRows} of ${totalKeywords} processed (${percent}%) - All done!`);
                $('#progressBar').css('width', '100%');
                $('#progressBar').removeClass('bg-danger bg-warning').addClass('bg-success');
            } else {
                $('#progressText').text(`${processedRows} of ${totalKeywords} processed (${percent}%) - ${processingRows} still processing`);
                $('#progressBar').css('width', `${percent}%`);
                
                // Update progress bar color
                if (percent < 30) {
                    $('#progressBar').removeClass('bg-success bg-warning').addClass('bg-danger');
                } else if (percent < 70) {
                    $('#progressBar').removeClass('bg-danger bg-success').addClass('bg-warning');
                } else {
                    $('#progressBar').removeClass('bg-danger bg-warning').addClass('bg-success');
                }
            }
            
            return {
                processed: processedRows,
                processing: processingRows
            };
        }
        
        // Function to update a row
        function updateRow(index, data) {
            const row = $(`#row-${index}`);
            const aioBadge = $(`#aio-status-${index}`);
            const clientBadge = $(`#client-status-${index}`);
            
            // Determine AIO status text
            const aioStatus = data.has_aio ? 'Yes' : 'No';
            const aioStatusClass = data.has_aio ? 'yes' : 'no';
            
            // Determine client mentioned status
            let clientStatus = data.client_mentioned || 'No';
            let clientStatusClass = data.client_mentioned === 'Yes' ? 'yes' : 'no';
            
            // If no AIO, client can't be mentioned
            if (!data.has_aio) {
                clientStatus = 'No';
                clientStatusClass = 'no';
            }
            
            // Update AIO status
            aioBadge.removeClass('status-processing status-yes status-no status-error')
                    .addClass(`status-${aioStatusClass}`)
                    .text(aioStatus);
            
            // Update client mentioned status
            clientBadge.removeClass('status-processing status-yes status-no status-error')
                    .addClass(`status-${clientStatusClass}`)
                    .text(clientStatus);
            
            // Mark row as processed
            row.attr('data-processed', 'true');
            processedIndexes.add(parseInt(index));
            
            // Add visual feedback
            row.addClass('table-success');
            setTimeout(() => {
                row.removeClass('table-success');
            }, 1000);
        }
        
        // Function to poll for updates
        function pollForUpdates() {
            const indexesToCheck = [];
            
            // Check all rows that are still processing
            allKeywords.forEach(item => {
                const index = item.index;
                
                if (!processedIndexes.has(index)) {
                    indexesToCheck.push(index);
                }
            });
            
            if (indexesToCheck.length > 0) {
                console.log('Polling for ' + indexesToCheck.length + ' indexes:', indexesToCheck);
                
                $.ajax({
                    url: '{{ route("gsc.aio.get-cached") }}',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        session_id: sessionId,
                        indexes: indexesToCheck
                    },
                    success: function(response) {
                        if (response.success && response.results) {
                            let updated = false;
                            
                            Object.keys(response.results).forEach(index => {
                                const result = response.results[index];
                                if (result.processed) {
                                    updateRow(index, result);
                                    updated = true;
                                }
                            });
                            
                            if (updated) {
                                updateStats();
                            }
                        }
                    },
                    complete: function() {
                        showToast('Success', 'All keywords has been processed', 'success');
                        
                    },
                    error: function(xhr) {
                        console.error('Polling error:', xhr.responseText);
                    }
                });
            } else {
                // No more processing items, stop polling
                clearInterval(pollInterval);
                $('#progressBar').removeClass('progress-bar-animated');
                updateStats();
                
                if (!progressCompleteShown) {
                    showToast('Success', 'All keywords have been processed', 'success');
                    progressCompleteShown = true;
                }
            }
        }
        
        // Single keyword sync
        $('.sync-single').click(function() {
            const button = $(this);
            const index = button.data('index');
            const keyword = button.data('keyword');
            
            // Disable button and show loading
            button.prop('disabled', true).addClass('syncing');
            
            // Set status to processing
            $(`#aio-status-${index}`).removeClass('status-yes status-no status-error')
                                     .addClass('status-processing')
                                     .text('Processing');
            $(`#client-status-${index}`).removeClass('status-yes status-no status-error')
                                       .addClass('status-processing')
                                       .text('Processing');
            
            $(`#row-${index}`).attr('data-processed', 'false');
            processedIndexes.delete(parseInt(index));
            
            // Make AJAX request
            $.ajax({
                url: '{{ route("sync.gsc.aio") }}',
                method: 'POST',
                data: {
                    _token: csrfToken,
                    keyword: keyword,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        showToast('Success', `Queued "${keyword}" for processing`, 'success');
                        // Start polling for this specific index
                        setTimeout(() => pollForUpdates(), 2000);
                    } else {
                        showToast('Error', response.message, 'error');
                        resetButton(button, index);
                    }
                },
                error: function(xhr) {
                    showToast('Error', 'Failed to queue keyword', 'error');
                    resetButton(button, index);
                }
            });
        });
        
        function resetButton(button, index) {
            button.prop('disabled', false).removeClass('syncing');
            $(`#aio-status-${index}`).text('Error').addClass('status-error');
            $(`#client-status-${index}`).text('Error').addClass('status-error');
        }
        
        // Sync all keywords button
        $('#syncAllBtn').click(function() {
            if (confirm(`Are you sure you want to re-process all ${totalKeywords} keywords?`)) {
                // Set all to processing
                allKeywords.forEach(item => {
                    $(`#aio-status-${item.index}`).removeClass('status-yes status-no status-error')
                                                .addClass('status-processing')
                                                .text('Processing');
                    $(`#client-status-${item.index}`).removeClass('status-yes status-no status-error')
                                                .addClass('status-processing')
                                                .text('Processing');
                    $(`#row-${item.index}`).attr('data-processed', 'false');
                });
                
                // Clear all processed indexes
                processedIndexes.clear();
                
                // Show loading
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                // Make AJAX request to reprocess all
                const keywordsData = allKeywords.map(item => ({
                    index: item.index,
                    keyword: item.keyword
                }));
                
                $.ajax({
                    url: '{{ route("sync.gsc.aio") }}', // We'll use the same endpoint for batch
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        keywords: keywordsData,
                        batch: true
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('Success', `Queued ${keywordsData.length} keywords for processing`, 'success');
                            startPolling();
                        } else {
                            showToast('Error', response.message, 'error');
                        }
                        $('#syncAllBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Re-process All');
                    },
                    error: function(xhr) {
                        showToast('Error', 'Failed to queue keywords', 'error');
                        $('#syncAllBtn').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Re-process All');
                    }
                });
            }
        });
        
        // Function to start polling
        function startPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
            }
            pollInterval = setInterval(pollForUpdates, 3000); // Poll every 3 seconds
        }
        
        // Function to show toast
        function showToast(title, message, type = 'info') {
            // Create toast element
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'info'} border-0" 
                     style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}:</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            // Add to body and show
            $('body').append(toastHtml);
            const toastElement = $(`#${toastId}`);
            const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
            toast.show();
            
            // Remove after hiding
            toastElement.on('hidden.bs.toast', function() {
                $(this).remove();
            });
        }
        
        // Initial stats update
        updateStats();
        
        // Start polling for updates
        startPolling();
        
        // Initial check for already processed items
        setTimeout(() => {
            const indexesToCheck = allKeywords.map(item => item.index);
            
            if (indexesToCheck.length > 0) {
                $.ajax({
                    url: '{{ route("gsc.aio.get-cached") }}',
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        session_id: sessionId,
                        indexes: indexesToCheck
                    },
                    success: function(response) {
                        if (response.success && response.results) {
                            Object.keys(response.results).forEach(index => {
                                const result = response.results[index];
                                if (result.processed) {
                                    updateRow(index, result);
                                }
                            });
                            updateStats();
                        }
                    }
                });
            }
        }, 2000);
    });
</script>
</body>
</html>