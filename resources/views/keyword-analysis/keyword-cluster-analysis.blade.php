@extends('layouts.page-app')

@section("content")
<style>
    /* Add to your existing CSS */

    .children-container {
        /* max-height: 0; */
        overflow: hidden;
        transition: max-height 0.5s ease;
    }

    .children-container.loaded {
        max-height: 1000px;
        /* Adjust based on content */
    }

    .loading-container {
        min-height: 100px;
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

    /* Custom styles for auto keyword fetch */
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

    /* Add to your existing CSS */
    .loading-status {
        display: none;
    }

    .parent-keyword-card {
        position: relative;
        overflow: hidden;
    }

    .parent-keyword-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #007bff, transparent);
        animation: loading-shimmer 2s infinite;
    }

    @keyframes loading-shimmer {
        0% {
            left: -100%;
        }

        100% {
            left: 100%;
        }
    }

    .children-container {
        position: relative;
        min-height: 100px;
    }

    .children-container .loaded {
        animation: fadeIn 0.5s ease;
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

    /* Success animation for loaded state */
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

    /* New styles for radio buttons */
    .form-check-input:checked {
        background-color: #1a73e8;
        border-color: #1a73e8;
    }

    .form-check-label {
        font-weight: 500;
        color: #333;
    }

    #domainClusterGroup, #keywordClusterGroup {
        margin-top: 15px;
    }

    /* Style for the cluster request dropdowns */
    select.form-control:disabled {
        background-color: #f8f9fa;
        cursor: not-allowed;
    }

    /* Make the cluster request option labels clearer */
    select.form-control option {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    select.form-control option:last-child {
        border-bottom: none;
    }

    /* Filter type selection styling */
    

    

    .form-check-inline {
        margin-right: 20px;
    }

    /* Input group styling */
    .input-group-custom {
        margin-top: 10px;
    }
</style>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Add Keyword Cluster Request</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('view-client/' . $id)}}">Keyword List</a></li>
                            <li class="breadcrumb-item active">Add Keyword Cluster Request</li>
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
                                <input type="hidden" name="client_property_id" value="{{ $id }}">
                                <input type="hidden" name="domainmanagement_id" value="{{ $domainmanagement_id }}">

                                <!-- Radio buttons for filter type -->
                                <div class="col-md-12 filter-type-group">
                                    <h6>Filter Type</h6>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filter_type" id="filterDomain" value="domain" checked>
                                        <label class="form-check-label" for="filterDomain">Domain</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="filter_type" id="filterKeyword" value="keyword">
                                        <label class="form-check-label" for="filterKeyword">Keyword</label>
                                    </div>
                                </div>

                                <!-- Domain Input -->
                                <div class="col-md-12 input-group-custom" id="domainInputGroup">
                                    <label class="form-label">Domain Name</label>
                                    <input type="text" class="form-control" name="domain_name" value="{{ $domain_name }}" readonly>
                                </div>

                                <!-- Keyword Input -->
                                <div class="col-md-12 input-group-custom" id="keywordInputGroup" style="display: none;">
                                    <label class="form-label">Keyword</label>
                                    <input type="text" class="form-control" name="master_keyword" value="" placeholder="Enter keyword to filter">
                                </div>

                                <div class="col-md-2 filter-item">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo (new DateTime('3 months ago'))->format('Y-m-d'); ?>">
                                </div>

                                <div class="col-md-2 filter-item">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo (new DateTime('1 day ago'))->format('Y-m-d'); ?>">
                                </div>
                                
                                <div class="col-md-2 filter-item">
                                    <label class="form-label">Median Limits</label>
                                    <input type="number" name="median_limit" id="median_limit" class="form-control" value="20">
                                </div>
                                
                                <div class="col-12">
                                    <button class="btn btn-primary" id="show_result">
                                        <i class="fas fa-search me-2"></i>Show the Result
                                    </button>
                                    <div class="form-text mt-2">
                                        <i class="fas fa-info-circle text-primary me-1"></i>
                                        This will automatically fetch keywords from Google Search Console and generate related keywords from Keyword Planner.
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
            
            @if($all_cluster_requests)
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
            @endif
            
            <!-- Add this in the appropriate section of your HTML -->
            <div class="col-md-12">
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" id="showAllTab">All Keywords</button>
                        <button type="button" class="btn btn-outline-primary" id="medianTab">Median</button>
                    </div>
                </div>
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
    document.addEventListener('livewire:init', () => {
        console.log('Livewire initialized');
    });
    
    $(document).ready(function() {
        // Toggle between domain and keyword input fields
        function toggleInputFields() {
            const selectedValue = $('input[name="filter_type"]:checked').val();
            
            if (selectedValue === 'domain') {
                // Show domain input, hide keyword input
                $('#domainInputGroup').show();
                $('#keywordInputGroup').hide();
                
                // Make domain input readonly as per original
                $('input[name="domain_name"]').prop('readonly', true);
                
                // Clear keyword input value if needed
                $('input[name="master_keyword"]').val('');
            } else if (selectedValue === 'keyword') {
                // Show keyword input, hide domain input
                $('#keywordInputGroup').show();
                $('#domainInputGroup').hide();
                
                // Make keyword input editable
                $('input[name="master_keyword"]').prop('readonly', false);
                
                // Clear domain input value
                $('input[name="domain_name"]').val('');
            }
        }
        
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
        toggleInputFields();
        toggleClusterRequestFields();
        
        // Add change event listeners to radio buttons
        $('input[name="filter_type"]').change(function() {
            toggleInputFields();
        });
        
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

            const isCluster = $(this).attr('id') === 'show_cluster_result';
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
            button.prop('disabled', true);
            button.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

            let formData = $('#clusterRequestForm').serialize();
            
            // Add the selected cluster request type and ID to form data
            formData += '&cluster_type=' + selectedType + '&cluster_request_id=' + selectedId;
            
            $("#result_box").html('');

            $.ajax({
                url: "{{ route('auto-keyword-fetch') }}",
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
                        $("#result_box").html(response.html);

                        // Store important IDs for lazy loading
                        if (response.data) {
                            sessionStorage.setItem('keyword_request_id', response.data.keyword_request_id);
                            sessionStorage.setItem('cluster_request_id', response.data.cluster_request_id);
                            sessionStorage.setItem('is_previous_request', response.data.is_previous_request);
                            sessionStorage.setItem('cluster_type', selectedType);
                        }

                        // If auto_load is enabled, show progress
                        if (response.data && response.data.auto_load) {
                            showToast('info', 'Auto-loading child keywords...');
                        }

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

        // Show result button click handler
        $("#show_result").on("click", function(e) {
            e.preventDefault();

            const button = $(this);
            const isCluster = false;

            // Show loading state
            button.prop('disabled', true);
            button.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

            let formData = $('#keywordFilterForm').serialize();
            $("#result_box").html('');
            console.log('formData'+formData);

            $.ajax({
                url: "{{ route('auto-keyword-fetch') }}",
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
                    if (response.success) {
                        $("#result_box").html(response.html);

                        // Store important IDs for lazy loading
                        if (response.data) {
                            sessionStorage.setItem('keyword_request_id', response.data.keyword_request_id);
                            sessionStorage.setItem('cluster_request_id', response.data.cluster_request_id);
                            sessionStorage.setItem('is_previous_request', response.data.is_previous_request);
                            sessionStorage.setItem('filter_type', $('input[name="filter_type"]:checked').val());
                        }

                        // If auto_load is enabled, show progress
                        if (response.data && response.data.auto_load) {
                            showToast('info', 'Auto-loading child keywords...');
                        }

                        showToast('success', response.message);
                        if (response.data.keywords_count && response.data.keywords_count < 1000 && response.is_full_results === false) {
                            setTimeout(() => {
                                showFetchMoreModal(response.keywords_count, response.remaining_keywords);
                            }, 1000);
                        }
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
                    button.html('<i class="fas fa-search me-2"></i>Show the Result');
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
                }
            });
        });
        function showFetchMoreModal(currentCount, remainingCount) {
            $('#currentKeywordCount').text(currentCount);
            $('#remainingKeywordCount').text(remainingCount);
            
            const modal = new bootstrap.Modal(document.getElementById('fetchMoreModal'));
            modal.show();
        }
        

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
            const keyword_request_id = sessionStorage.getItem('keyword_request_id');

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
                beforeSend: function() {
                    // Show loading message
                    showToast('info', `Fetching AI Overview for: ${keyword}`);
                },
                success: function(response) {
                    if (response.success) {
                        keyword_planner_id = response.keyword_planner_id;
                        console.log("output: ", response.keyword_planner_id, keyword_planner_id);

                        if (response.ai_status) {
                            showToast('success', `AI Overview found for "${keyword}"`);
                        } else {
                            showToast('warning', `No AI Overview found for "${keyword}"`);
                        }

                        showAioModalDirect(response.data);

                    } else {
                        showToast('error', 'Error: ' + response.message);
                        resetButton(clickedButton);
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    showToast('error', 'Failed to fetch AI Overview');
                    resetButton(clickedButton);
                }
            });
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
            keyword_planner_id = data.keyword_planner_id;

            $('#aioDirectModal').on('hidden.bs.modal', function() {

                // console.log("inside keyword_planner_id: ", keyword_planner_id);
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

        function resetButton(button) {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-robot me-2"></i>Get AIO Result';
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
                button.innerHTML = '<i class="fas fa-robot me-2"></i>Get AIO Result';
                button.classList.remove('disabled');
            }
        }

        document.addEventListener('hidden.bs.modal', function(event) {
            if (event.target.id === 'aioModal') {
                // Find all loading buttons and reset them
                document.querySelectorAll('.get-aio-btn[disabled]').forEach(button => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-robot me-2"></i>Get AIO Result';
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
        }

        // Initialize date inputs with default values (last 30 days)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(today.getDate() - 30);

            // Format dates as YYYY-MM-DD
            const formatDate = (date) => {
                return date.toISOString().split('T')[0];
            };

            // Add tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Add keyboard shortcut for Show Result (Ctrl+Enter)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                const showResultBtn = document.getElementById('show_result');
                if (showResultBtn && !showResultBtn.disabled) {
                    e.preventDefault();
                    showResultBtn.click();
                }
            }
        });
    });
$(document).ready(function() {
    // Variables
    let originalContent = '';
    let isMedianView = false;
    let currentLimit = 20;
    let currentClusterId = null;
    let currentClusterType = null;
    
    // Get limit from hidden input
    function updateLimit() {
        const limitInput = $('input[name="limit"]');
        if (limitInput.length) {
            currentLimit = parseInt(limitInput.val()) || 20;
        }
    }
    
    // Show median view - Make AJAX call to get median data
    function showMedianView() {
        updateLimit();
        
        // Get current cluster request ID and type
        const selectedType = $('input[name="cluster_filter_type"]:checked').val();
        let selectedId = '';
        
        if (selectedType === 'domain') {
            selectedId = $('#domain_cluster_request').val();
        } else if (selectedType === 'keyword') {
            selectedId = $('#keyword_cluster_request').val();
        }
        
        if (!selectedId) {
            showToast('warning', 'Please select a cluster request first');
            return;
        }
        
        // Store current state
        currentClusterId = selectedId;
        currentClusterType = selectedType;
        
        // Store original content if not already stored
        if (!originalContent) {
            originalContent = $('#result_box').html();
        }
        
        // Show loading
        $('#result_box').html(`
            <div class="text-center p-5">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-3">
                    <h5>Calculating Median Analysis...</h5>
                    <p class="text-muted mt-2">Fetching ${selectedType} cluster data for median analysis...</p>
                </div>
            </div>
        `);
        
        // Make AJAX call to get median data
        const formData = $('#clusterRequestForm').serialize();
        
        $.ajax({
            url: "{{ route('auto-keyword-fetch') }}",
            type: "POST",
            data: formData + '&median=true&median_limit=' + currentLimit + '&cluster_request_id=' + selectedId,
            success: function(response) {
                if (response.success) {
                    $('#result_box').html(response.html);
                    isMedianView = true;
                    updateTabStates();
                    
                    // Remove any DataTable initialization for median view
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#keywordsTable')) {
                        $('#keywordsTable').DataTable().destroy();
                    }
                    
                    // Remove CSS classes for highest/lowest
                    $('#keywordsTable tbody tr').each(function() {
                        $(this).removeClass('table-success table-danger');
                    });
                    
                    showToast('success', response.message || 'Median analysis loaded successfully');
                } else {
                    $('#result_box').html(originalContent);
                    showToast('error', response.message || 'Failed to load median analysis');
                }
            },
            error: function(xhr) {
                $('#result_box').html(originalContent);
                showToast('error', 'Failed to load median analysis');
                console.error('Error:', xhr.responseText);
            }
        });
    }
    
    // Show all keywords view
    function showAllKeywordsView() {
        if (!originalContent) {
            showToast('warning', 'No original content available');
            return;
        }
        
        $('#result_box').html(originalContent);
        isMedianView = false;
        updateTabStates();
        
        // Reinitialize DataTables if they exist
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#keywordsTable')) {
            $('#keywordsTable').DataTable().destroy();
            $('#keywordsTable').DataTable({
                pageLength: 10,
                order: [[5, 'desc']]
            });
        }
        
        // Reinitialize processing checks
        setTimeout(initializeProcessingChecks, 500);
        
        showToast('info', 'Showing all keywords');
    }
    
    // Update tab states
    function updateTabStates() {
        if (isMedianView) {
            $('#medianTab').removeClass('btn-outline-primary').addClass('btn-primary');
            $('#showAllTab').removeClass('btn-primary').addClass('btn-outline-primary');
        } else {
            $('#medianTab').removeClass('btn-primary').addClass('btn-outline-primary');
            $('#showAllTab').removeClass('btn-outline-primary').addClass('btn-primary');
        }
    }
    
    // Reset on new data
    function resetOnNewData() {
        originalContent = '';
        isMedianView = false;
        currentClusterId = null;
        currentClusterType = null;
        updateTabStates();
    }
    
    // Tab click handlers
    $('#medianTab').on('click', showMedianView);
    $('#showAllTab').on('click', showAllKeywordsView);
    
    // Listen for new data loaded via AJAX
    $(document).on('ajaxSuccess', function(event, xhr, settings) {
        if (settings.url.includes('auto-keyword-fetch')) {
            // Check if this is a median response
            const response = JSON.parse(xhr.responseText);
            if (response.data && response.data.is_median_view) {
                isMedianView = true;
            } else {
                resetOnNewData();
            }
        }
    });
    
    // Initialize
    updateLimit();
    updateTabStates();
    
    // Toast notification function
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
    
    // Global function declaration for showToast
    window.showToast = showToast;
});
</script>
@endsection