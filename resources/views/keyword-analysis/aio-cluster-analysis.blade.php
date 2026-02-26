@extends('layouts.page-app')

@section("content")
<style>

    .aio-sub-tab-btn {
        margin-right: 5px;
        font-size: 0.8rem;
        padding: 4px 8px;
    }

    .aio-sub-tab-btn.active {
        font-weight: bold;
    }

    .child-keyword.mentioned-client {
        border-left: 4px solid #28a745;
    }

    .child-keyword.not-mentioned {
        border-left: 4px solid #ffc107;
    }
    /* Keep only essential styles for cluster analysis page */
    .keyword-tree {
        margin-left: 20px;
        border-left: 2px solid #e9ecef;
        padding-left: 15px;
    }

    .parent-keyword {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
        border-left: 4px solid #0d6efd;
    }

    .child-keyword {
        background-color: white;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        padding: 8px;
        margin-bottom: 5px;
        margin-left: 20px;
    }

    .keyword-badge {
        font-size: 0.75em;
        padding: 2px 6px;
    }

    .parent-keyword-badge {
        background-color: #0d6efd;
        color: white;
    }

    .child-keyword-badge {
        background-color: #6c757d;
        color: white;
    }

    /* Loading animations */
    .parent-keyword-card {
        position: relative;
        overflow: hidden;
    }

    .children-container {
        position: relative;
        min-height: 100px;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Success animation */
    @keyframes successPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    .parent-keyword-card .loaded {
        animation: successPulse 1.5s ease;
    }

    .hide-after::after {
        display: none !important;
        animation: none !important;
        content: '' !important;
    }

    /* Form styling */
    .form-check-input:checked {
        background-color: #1a73e8;
        border-color: #1a73e8;
    }

    .form-check-label {
        font-weight: 500;
        color: #333;
    }

    #domainClusterGroup,
    #keywordClusterGroup {
        margin-top: 15px;
    }

    select.form-control:disabled {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    .form-check-inline {
        margin-right: 20px;
    }

    .input-group-custom {
        margin-top: 10px;
    }

    /* Global AIO Tabs Styling */
    .global-aio-tabs {
        padding-bottom: 0px;
    }

    .global-aio-tab-btn {
        background: none;
        border: none;
        padding: 10px 20px;
        font-weight: 500;
        color: #6c757d;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        font-size: 1.1rem;
    }

    .global-aio-tab-btn:hover {
        color: #495057;
    }

    .global-aio-tab-btn.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
    }

    .global-aio-tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .global-aio-tab-content.active {
        display: block;
    }

    .global-aio-badge {
        font-size: 0.8em;
        padding: 4px 8px;
        border-radius: 10px;
        margin-left: 8px;
    }

    .aio-true {
        background-color: #198754;
        color: white;
    }

    .aio-false {
        background-color: #6c757d;
        color: white;
    }

    .child-keyword.aio-true {
        border-left: 4px solid #198754;
    }

    .child-keyword.aio-false {
        border-left: 4px solid #6c757d;
    }
</style>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">AIO Cluster Analysis</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('view-client/' . $id)}}">Keyword List</a></li>
                            <li class="breadcrumb-item active">AIO Cluster Analysis</li>
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
                            <form id="clusterRequestForm" class="row g-3">
                                <input type="hidden" name="client_property_id" value="{{ $id }}">
                                <input type="hidden" name="domainmanagement_id" value="{{ $domainmanagement_id }}">
                                <input type="hidden" name="domain_name" id="cluster_domain_name" value="{{ $domain_name }}">
                                <input type="hidden" name="master_keyword" id="cluster_master_keyword" value="">
                                <input type="hidden" name="date_from" id="cluster_date_from">
                                <input type="hidden" name="date_to" id="cluster_date_to">

                                <!-- Radio buttons for cluster request type -->
                                <div class="col-md-12 filter-type-group">
                                    <h6>Cluster Request Type</h6>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="cluster_filter_type"
                                            id="clusterFilterDomain" value="domain" checked>
                                        <label class="form-check-label" for="clusterFilterDomain">Domain Cluster</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="cluster_filter_type"
                                            id="clusterFilterKeyword" value="keyword">
                                        <label class="form-check-label" for="clusterFilterKeyword">Keyword Cluster</label>
                                    </div>
                                </div>

                                <!-- Domain Cluster Request -->
                                <div class="col-md-12 input-group-custom" id="domainClusterGroup">
                                    <label class="form-label">Domain Cluster Request</label>
                                    <select class="form-control" id="domain_cluster_request" name="domain_cluster_request">
                                        <option value="">Select a Domain Cluster Request</option>
                                        @if(isset($domain_cluster_request) && count($domain_cluster_request) > 0)
                                        @foreach($domain_cluster_request as $domain_cluster)
                                        <option value="{{ $domain_cluster['id'] }}"
                                            data-date-from="{{ $domain_cluster['date_from'] }}"
                                            data-date-to="{{ $domain_cluster['date_to'] }}"
                                            data-filter-type="domain"
                                            data-value="{{ $domain_cluster['keyword'] ?? '' }}">
                                            {{ date('M d, Y', strtotime($domain_cluster['date_from'])) }} to
                                            {{ date('M d, Y', strtotime($domain_cluster['date_to'])) }}
                                            @if(isset($domain_cluster['keyword']) && !empty($domain_cluster['keyword']))
                                            - {{ Str::limit($domain_cluster['keyword'], 50) }}
                                            @endif
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="" disabled>No domain cluster requests found</option>
                                        @endif
                                    </select>
                                </div>

                                <!-- Keyword Cluster Request -->
                                <div class="col-md-12 input-group-custom" id="keywordClusterGroup" style="display: none;">
                                    <label class="form-label">Keyword Cluster Request</label>
                                    <select class="form-control" id="keyword_cluster_request" name="keyword_cluster_request">
                                        <option value="">Select a Keyword Cluster Request</option>
                                        @if(isset($keyword_cluster_request) && count($keyword_cluster_request) > 0)
                                        @foreach($keyword_cluster_request as $keyword_cluster)
                                        <option value="{{ $keyword_cluster['id'] }}"
                                            data-date-from="{{ $keyword_cluster['date_from'] }}"
                                            data-date-to="{{ $keyword_cluster['date_to'] }}"
                                            data-filter-type="keyword"
                                            data-value="{{ $keyword_cluster['keyword'] ?? '' }}">
                                            {{ date('M d, Y', strtotime($keyword_cluster['date_from'])) }} to
                                            {{ date('M d, Y', strtotime($keyword_cluster['date_to'])) }}
                                            @if(isset($keyword_cluster['keyword']) && !empty($keyword_cluster['keyword']))
                                            - {{ Str::limit($keyword_cluster['keyword'], 50) }}
                                            @endif
                                        </option>
                                        @endforeach
                                        @else
                                        <option value="" disabled>No keyword cluster requests found</option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-primary" id="show_cluster_result" disabled>
                                        <i class="fas fa-search me-2"></i>Show Cluster Result
                                    </button>
                                    <button class="btn btn-danger" disabled id="reset_button" style="display: none;">
                                        <i class="fas fa-times me-2"></i>Reset
                                    </button>
                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                        Get the keyword cluster analysis result for the selected request based on date range.
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div id="result_box" class="mt-4"></div>
            </div>

        </div>
        <!-- end row -->
    </div> <!-- container-fluid -->

</div>

<!-- Toast Container for Notifications -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1056"></div>
@endsection

@section("jscontent")
<script>
    $(document).ready(function() {
        // Store all child keywords data globally
        let allChildKeywords = {};
        let currentAIOFilter = 'all'; // 'all', 'with-aio', 'without-aio'

        // Toggle between domain and keyword cluster request dropdowns
        function toggleClusterRequestFields() {
            const selectedValue = $('input[name="cluster_filter_type"]:checked').val();

            if (selectedValue === 'domain') {
                // Show domain cluster, hide keyword cluster
                $('#domainClusterGroup').show();
                $('#keywordClusterGroup').hide();

                // Reset and disable keyword cluster dropdown
                $('#keyword_cluster_request').val('').prop('disabled', true);
                $('#domain_cluster_request').prop('disabled', false);

            } else if (selectedValue === 'keyword') {
                // Show keyword cluster, hide domain cluster
                $('#keywordClusterGroup').show();
                $('#domainClusterGroup').hide();

                // Reset and disable domain cluster dropdown
                $('#domain_cluster_request').val('').prop('disabled', true);
                $('#keyword_cluster_request').prop('disabled', false);
            }

            // Update button state
            toggleClusterSubmitButton();
        }

        // Set initial state
        toggleClusterRequestFields();

        // Add change event listeners to radio buttons
        $('input[name="cluster_filter_type"]').change(function() {
            toggleClusterRequestFields();
        });

        // Disable cluster submit button initially
        toggleClusterSubmitButton();

        // Check selection on change for cluster dropdowns
        $('#domain_cluster_request, #keyword_cluster_request').change(function() {
            const selectedOption = $(this).find('option:selected');
            const filterType = selectedOption.data('filter-type');
            const value = selectedOption.data('value');

            // Update the appropriate input based on filter type
            if (filterType === 'domain') {
                $('#cluster_domain_name').val(value);
                $('#cluster_master_keyword').val('');
            } else if (filterType === 'keyword') {
                $('#cluster_master_keyword').val(value);
                $('#cluster_domain_name').val('');
            }

            // Update hidden date fields
            $('#cluster_date_from').val(selectedOption.data('date-from'));
            $('#cluster_date_to').val(selectedOption.data('date-to'));

            toggleClusterSubmitButton();
        });

        function toggleClusterSubmitButton() {
            const selectedType = $('input[name="cluster_filter_type"]:checked').val();
            let selectedValue = '';

            if (selectedType === 'domain') {
                selectedValue = $('#domain_cluster_request').val();
            } else if (selectedType === 'keyword') {
                selectedValue = $('#keyword_cluster_request').val();
            }

            // Disable button if empty value is selected
            if (!selectedValue || selectedValue === '') {
                $('#show_cluster_result').prop('disabled', true);
            } else {
                $('#show_cluster_result').prop('disabled', false);
            }
        }

        // Reset button handler
        $("#reset_button").on("click", function(e) {
            e.preventDefault();
            const resetResultBtn = $('#reset_button').prop('disabled', true);
            $("#result_box").html('');

            // Reset cluster request dropdowns
            $('#domain_cluster_request').val('');
            $('#keyword_cluster_request').val('');
            toggleClusterSubmitButton();
        });

        // Update the cluster result button click handler
        $("#show_cluster_result").on("click", function(e) {
            e.preventDefault();

            const selectedType = $('input[name="cluster_filter_type"]:checked').val();
            let selectedId = '';
            let selectedValue = '';

            if (selectedType === 'domain') {
                selectedId = $('#domain_cluster_request option:selected').val();
                selectedValue = $('#domain_cluster_request option:selected').data('value');
            } else if (selectedType === 'keyword') {
                selectedId = $('#keyword_cluster_request option:selected').val();
                selectedValue = $('#keyword_cluster_request option:selected').data('value');
            }

            if (!selectedId) {
                showToast('warning', 'Please select a cluster request');
                return;
            }

            // Show loading state
            const button = $(this);
            let clusterResponseData = null;
            button.prop('disabled', true);
            button.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

            let formData = $('#clusterRequestForm').serialize();

            // Add the selected cluster request type and ID to form data
            formData += '&cluster_type=' + selectedType + '&cluster_request_id=' + selectedId;

            $("#result_box").html('');

            $.ajax({
                url: "{{ route('aio-keyword-fetch') }}",
                type: "POST",
                data: formData,
                beforeSend: function() {
                    $("#result_box").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3">
                            <h5>Loading ${selectedType === 'domain' ? 'Domain' : 'Keyword'} Cluster Keywords...</h5>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 30%"></div>
                            </div>
                            <p class="text-muted mt-2">Fetching ${selectedType} cluster data for "${selectedValue}"...</p>
                        </div>
                    </div>
                `);
                },
                success: function(response) {
                    if (response.success) {
                        allChildKeywords = response.child_keywords;
                        clusterResponseData = response;

                        // Generate HTML from the JSON response
                        const html = generateClusterResultHTML(response);
                        $("#result_box").html(html);

                        setTimeout(() => {
                            initializeChildDataTables();
                        }, 100);
                        // Initialize with all keywords shown
                        currentAIOFilter = 'all';

                        showToast('success', response.message);
                    } else {
                        $("#result_box").html(`
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message}
                        </div>
                    `);
                        showToast('warning', response.message);
                    }
                },
                complete: function() {
                    // Reset button state
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-search me-2"></i>Show Cluster Result');

                    $('#reset_button').show().prop('disabled', false);
                },
                error: function(xhr) {
                    console.error('Error:', xhr.responseText);

                    let errorMessage = 'Something went wrong! Please try again.';
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        // Keep default message
                    }

                    $("#result_box").html(`
                    <div class="alert alert-danger">
                        ${errorMessage}
                    </div>
                `);

                    showToast('error', errorMessage);
                    button.prop('disabled', false);
                    button.html('<i class="fas fa-search me-2"></i>Show Cluster Result');
                }
            });
        });

        // Function to generate HTML from the JSON response
        function generateClusterResultHTML(response) {
            // Calculate totals for global tabs
            let allChildren = [];
            let withAIOChildren = [];
            let withoutAIOChildren = [];
            
            // For "With AIO" tab, track domain_available status
            let withAIOMentionedClient = [];
            let withAIONotMentioned = [];

            // Process all children
            Object.values(response.child_keywords).forEach(children => {
                children.forEach(child => {
                    allChildren.push(child);
                    
                    if (child.has_ai_overview) {
                        withAIOChildren.push(child);
                        
                        // Check domain_available for "With AIO" children
                        if (child.domain_available === true) {
                            withAIOMentionedClient.push(child);
                        } else {
                            withAIONotMentioned.push(child);
                        }
                    } else {
                        withoutAIOChildren.push(child);
                    }
                });
            });

            let html = '<div class="cluster-results-container">';

            // Add summary section
            html += `<div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Cluster Analysis Results</h5>
                    <p class="card-text">Found ${response.parent_keywords.length} parent keyword(s) with their child keywords.</p>
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Total Parent Keywords:</strong> ${response.parent_keywords.length}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Total Child Keywords:</strong> ${allChildren.length}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>With AI Overview:</strong> ${withAIOChildren.length}</p>
                        </div>
                    </div>
                </div>
            </div>`;

            // Global AIO Tabs
            html += `<div class="card">
            <div class="card-body py-2">
            <div class="global-aio-tabs">
                <button class="global-aio-tab-btn active" data-filter="all">
                    All Keywords <span class="badge global-aio-badge bg-primary">${allChildren.length}</span>
                </button>
                <button class="global-aio-tab-btn" data-filter="with-aio">
                    With AIO <span class="badge global-aio-badge aio-true">${withAIOChildren.length}</span>
                </button>
                <button class="global-aio-tab-btn" data-filter="without-aio">
                    Without AIO <span class="badge global-aio-badge aio-false">${withoutAIOChildren.length}</span>
                </button>
            </div>`;

            html += `<div class="aio-sub-tabs-container mt-3" style="display: none;">
                            <div class="aio-sub-tabs">
                                <button class="btn btn-sm btn-outline-primary aio-sub-tab-btn active" data-sub-filter="all-aio">
                                    All With AIO
                                </button>
                                <button class="btn btn-sm btn-outline-success aio-sub-tab-btn" data-sub-filter="mentioned-client">
                                    Mentioned Client
                                </button>
                                <button class="btn btn-sm btn-outline-warning aio-sub-tab-btn" data-sub-filter="not-mentioned">
                                    Not Mentioned Client
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;


            // Tab content container
            html += `<div class="global-aio-tab-content-container">`;

            // Process each parent keyword
            response.parent_keywords.forEach(parentKeyword => {
                const parentId = parentKeyword.id;
                const children = response.child_keywords[parentId] || [];
                if(children.length === 0){
                    return;
                }
                // const hasAIOCount = children.filter(child => child.has_ai_overview === true).length;
                // if(hasAIOCount === 0) {
                //     return;
                // }

                html += `<div class="card parent-keyword-card mb-4" id="parent-${parentId}" data-parent-id="${parentId}">
                    <div class="card-header" style="padding: 10px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <i class="fas fa-level-up-alt text-primary me-2"></i>
                                <strong>Parent Keyword:</strong> 
                                <span class="text-primary">${parentKeyword.keyword}</span>
                                <span class="badge bg-info ms-2" id="child-count-84">
                                    ${parentKeyword.child_count} children
                                </span>
                            </h6>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Clicks: ${parentKeyword.clicks} | 
                                Impressions: ${parentKeyword.impressions} | 
                                CTR: ${parentKeyword.ctr}% | 
                                Position: ${parentKeyword.position}
                            </small>
                        </div>
                    </div>
                    <div class="card-body">       
                        <!-- Children Container -->
                        <div class="children-container" id="children-${parentId}">
                            ${generateChildKeywordsHTML(children, parentId)}
                        </div>
                    </div>
                </div>`;
            });

            html += `</div></div>`;

            // Add initial event handlers
            setTimeout(() => {
                initializeEventHandlers();
            }, 100);

            return html;
        }

        // Function to generate child keywords HTML
        function generateChildKeywordsHTML(children, parentId) {

            if (children.length === 0) {
                return `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No child keywords found for this parent.
                    </div>
                `;
            }

            let rows = '';
            children.forEach(child => {
                const domainAvailable = child.domain_available === true;
                console.log(`${child.keyword}: ${child.domain_available}`);
                rows += `
                <tr data-has-aio="${child.has_ai_overview}" data-domain_available="${domainAvailable}">
                <td>${child.keyword}</td>
                <td>${child.monthlysearch_p || 'N/A'}</td>
                <td>${child.competition_p || 'N/A'}</td>
                <td>${child.low_bid_p || 0} - ${child.high_bid_p || 0}</td>
                <td>${child.clicks}</td>
                <td>${child.ctr}</td>
                <td>${child.impressions}</td>
                <td>${child.position}</td>
                <td>
                    ${child.has_ai_overview 
                        ? '<span class="badge bg-success">Yes</span>' 
                        : '<span class="badge bg-secondary">No</span>'}
                </td>
            </tr>
        `;
            });

            return `
        <table class="table child-datatable"
               id="child-table-${parentId}"
               data-parent-id="${parentId}">
            <thead>
                <tr>
                    <th>Keyword</th>
                    <th>Monthly Searches</th>
                    <th>Competition</th>
                    <th>Bid Range</th>
                    <th>Clicks</th>
                    <th>CTR</th>
                    <th>Impressions</th>
                    <th>Position</th>
                    <th>AIO</th>
                </tr>
            </thead>
            <tbody>
                ${rows}
            </tbody>
        </table>
    `;
        }


        // Function to filter keywords based on AIO status and domain_available
        function filterKeywordsByAIO(filterType, subFilter = 'all-aio') {
            currentAIOFilter = filterType;
            const aioSubTabsContainer = $('.aio-sub-tabs-container');
            if (filterType === 'with-aio') {
                aioSubTabsContainer.slideDown();
            } else {
                aioSubTabsContainer.slideUp();
            }

            $('.parent-keyword-card').each(function() {
                const parentId = $(this).data('parent-id');
                const table = $(`#child-table-${parentId}`);

                // If table is not initialized as DataTable yet, skip
                if (!$.fn.DataTable.isDataTable(table)) return;

                const dataTable = table.DataTable();

                // Clear previous search/filter
                dataTable.search('').draw();

                // Filter rows based on AIO status and domain_available
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    const row = dataTable.row(dataIndex).node();
                    const hasAIO = $(row).data('has-aio') === true || $(row).data('has-aio') === 'true';
                    const domainAvailable = $(row).data('domain_available') === true || $(row).data('domain_available') === 'true';

                    // Main filter
                    if (filterType === 'all') return true;
                    
                    if (filterType === 'with-aio') {
                        if (!hasAIO) return false;
                        
                        // Apply sub-filter for "With AIO" tab
                        if (subFilter === 'all-aio') return true;
                        if (subFilter === 'mentioned-client') return domainAvailable;
                        if (subFilter === 'not-mentioned') return !domainAvailable;
                        
                        return true;
                    }
                    
                    if (filterType === 'without-aio') return !hasAIO;

                    return true;
                });

                dataTable.draw();

                // Remove the custom filter function
                $.fn.dataTable.ext.search.pop();

                // Show/hide parent card based on whether any rows are visible
                const visibleRows = dataTable.rows({
                    search: 'applied'
                }).nodes().length;
                
                if (visibleRows === 0 && filterType !== 'all') {
                    $(this).hide();
                } else {
                    $(this).show();
                }

                // Update child count badge
                const childCountBadge = $(this).find('#child-count-' + parentId);
                if (childCountBadge.length) {
                    childCountBadge.text(visibleRows + ' children');
                }
            });
        }


        // Initialize event handlers for the generated content
function initializeEventHandlers() {
    // Toggle children visibility
    $('.toggle-children').on('click', function() {
        const parentId = $(this).data('parent-id');
        const button = $(this);
        const icon = button.find('i');
        const childrenContainer = $(`#children-${parentId}`);

        if (childrenContainer.is(':hidden')) {
            button.html('<i class="fas fa-chevron-up"></i> Hide Children');
            childrenContainer.slideDown();

            // Apply current filter to newly shown children
            if (currentAIOFilter !== 'all') {
                setTimeout(() => {
                    filterKeywordsByAIO(currentAIOFilter);
                }, 300);
            }
        } else {
            button.html('<i class="fas fa-chevron-down"></i> Show Children');
            childrenContainer.slideUp();
        }
    });

    // Global AIO Tab switching
    $('.global-aio-tab-btn').on('click', function() {
        const filterType = $(this).data('filter');

        // Update active tab button
        $('.global-aio-tab-btn').removeClass('active');
        $(this).addClass('active');
        currentAIOFilter = filterType;

        // Filter keywords with default sub-filter
        filterKeywordsByAIO(filterType, 'all-aio');
        
        // Reset sub-tabs to default
        $('.aio-sub-tab-btn').removeClass('active');
        $('.aio-sub-tab-btn[data-sub-filter="all-aio"]').addClass('active');
    });

    // AIO Sub-tab switching
    $(document).on('click', '.aio-sub-tab-btn', function() {
        const subFilter = $(this).data('sub-filter');
        
        // Update active sub-tab button
        $('.aio-sub-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Apply filter with sub-filter
        filterKeywordsByAIO('with-aio', subFilter);
    });
}

        function regenerateClusterResultsWithFilter() {
            // Get the original response data (you might need to store it globally)
            // For now, let's assume you have it in a variable

            // Regenerate HTML for each parent based on current filter
            Object.keys(allChildKeywords).forEach(parentId => {
                const children = allChildKeywords[parentId];
                let filteredChildren = children;

                // Apply current filter
                if (currentAIOFilter === 'with-aio') {
                    filteredChildren = children.filter(child => child.has_ai_overview === true);
                } else if (currentAIOFilter === 'without-aio') {
                    filteredChildren = children.filter(child => child.has_ai_overview === false);
                }

                // Regenerate the table for this parent
                const tableContainer = $(`#children-${parentId}`);
                if (tableContainer.length) {
                    tableContainer.html(generateChildKeywordsHTML(filteredChildren, parentId));

                    // Reinitialize DataTable
                    setTimeout(() => {
                        initializeChildDataTables();
                    }, 100);

                    // Show/hide parent based on filtered children count
                    const parentCard = $(`#parent-${parentId}`);
                    if (filteredChildren.length === 0 && currentAIOFilter !== 'all') {
                        parentCard.hide();
                    } else {
                        parentCard.show();
                    }
                }
            });
        }


        // Toast notification function
        function showToast(type, message) {
            const toastId = 'toast-' + Date.now();
            const icon = {
                'success': 'check-circle',
                'error': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            } [type] || 'info-circle';

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

        function initializeChildDataTables() {
            $('.child-datatable').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        pageLength: 10,
                        lengthMenu: [10, 25, 50, 100],
                        order: [
                            [5, 'desc']
                        ], // Sort by CTR descending
                        autoWidth: false,
                        responsive: false,
                        scrollX: true, // Enable horizontal scrolling
                        scrollCollapse: true,
                        fixedColumns: false,
                        lengthChange: false,
                        ordering: true,
                        searching: true,
                        info: false
                    });
                }
            });
        }

    });
</script>
@endsection