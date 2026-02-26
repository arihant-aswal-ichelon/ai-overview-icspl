/**
 * AUTO-SAVE MEDIAN DATA FIX
 * 
 * This script monitors the processing status of keywords in #keywordsTable
 * and automatically saves median data when all processing is complete.
 */

(function() {
    'use strict';
    
    // Global state tracking
    window.autoSaveMedianState = {
        isProcessing: false,
        lastCheckTime: null,
        autoSaveTriggered: false,
        processingCheckInterval: null
    };
    
    /**
     * Check if all rows in #keywordsTable have completed processing
     * @returns {Object} { allDone: boolean, totalRows: number, completedRows: number, aioYesRows: number }
     */
    function checkProcessingStatus() {
        const $table = $('#keywordsTable');
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) {
            return { allDone: false, totalRows: 0, completedRows: 0, aioYesRows: 0 };
        }
        
        const table = $table.DataTable();
        let totalRows = 0;
        let completedRows = 0;
        let aioYesRows = 0;
        
        // Check each row across all pages
        table.rows().every(function() {
            const $row = $(this.node());
            totalRows++;
            
            // Check if all three status columns are done
            const searchApiStatus = $row.find('.search-api-status .badge').text().trim();
            const aioStatus = $row.find('.aio-status .badge').text().trim();
            const clientMentionedStatus = $row.find('.client-mentioned-status .badge').text().trim();
            
            // Count AIO Yes results
            if (aioStatus === 'Yes' || aioStatus === 'Done') {
                aioYesRows++;
            }
            
            // Check if this row is fully processed
            const isDone = (
                (searchApiStatus === 'Done' || searchApiStatus === 'Yes' || searchApiStatus === 'No') &&
                (aioStatus === 'Yes' || aioStatus === 'No' || aioStatus === 'Done') &&
                (clientMentionedStatus === 'Yes' || clientMentionedStatus === 'No' || clientMentionedStatus === 'Done')
            );
            
            if (isDone) {
                completedRows++;
            }
        });
        
        const allDone = totalRows > 0 && completedRows === totalRows;
        
        console.log(`Processing Status: ${completedRows}/${totalRows} rows completed, ${aioYesRows} with AIO Yes`);
        
        return { 
            allDone, 
            totalRows, 
            completedRows, 
            aioYesRows 
        };
    }
    
    /**
     * Collect median data from rows with AIO status "Yes"
     * @returns {Array} Array of row data objects
     */
    function collectMedianDataFromAioYes() {
        const medianData = [];
        const $table = $('#keywordsTable');
        
        if (!$table.length || !$.fn.DataTable.isDataTable($table)) {
            console.error('Keywords table not found or not initialized');
            return medianData;
        }
        
        const table = $table.DataTable();
        
        table.rows().every(function() {
            const $row = $(this.node());
            const aioStatus = $row.find('.aio-status .badge').text().trim();
            
            // Only collect rows with AIO status "Yes" or "Done"
            if (aioStatus === 'Yes' || aioStatus === 'Done') {
                const rowData = {
                    keyword: $row.find('th[data-keyword]').data('keyword'),
                    monthly_search: parseInt($row.find('td[data-avg_monthly_searches]').data('avg_monthly_searches')) || 0,
                    competition: $row.find('td[data-competition]').data('competition') || 'UNDEFINED',
                    low_bid: parseFloat($row.find('td[data-low_top_of_page_bid]').data('low_top_of_page_bid')) || 0,
                    high_bid: parseFloat($row.find('td[data-high_top_of_page_bid]').data('high_top_of_page_bid')) || 0,
                    clicks: parseInt($row.find('td[data-clicks]').data('clicks')) || 0,
                    ctr: parseFloat($row.find('td[data-ctr]').data('ctr')) || 0,
                    impressions: parseInt($row.find('td[data-impressions]').data('impressions')) || 0,
                    position: parseFloat($row.find('td[data-position]').data('position')) || 0,
                    keyword_planner_id: $row.data('keyword-id') || null
                };
                
                medianData.push(rowData);
            }
        });
        
        console.log(`Collected ${medianData.length} rows with AIO Yes status`);
        return medianData;
    }
    
    /**
     * Get date range from filter inputs
     * @returns {Object} { dateFrom: string, dateTo: string }
     */
    function getDateRange() {
        const dateFrom = $('#filter_date_from').val() || '';
        const dateTo = $('#filter_date_to').val() || '';
        
        return { dateFrom, dateTo };
    }
    
    /**
     * Trigger automatic save of median data
     */
    function triggerAutoSaveMedianData() {
        // Prevent multiple triggers
        if (window.autoSaveMedianState.autoSaveTriggered) {
            console.log('Auto-save already triggered, skipping...');
            return;
        }
        
        window.autoSaveMedianState.autoSaveTriggered = true;
        console.log('🚀 Triggering auto-save for median data...');
        
        // Collect data
        const medianData = collectMedianDataFromAioYes();
        
        if (medianData.length === 0) {
            console.warn('No AIO Yes keywords found to save');
            showToast('No keywords with AIO Insights to save', 'warning');
            return;
        }
        
        // Get date range
        const { dateFrom, dateTo } = getDateRange();
        
        // Show notification
        showToast(`Auto-saving ${medianData.length} keywords with AIO Insights...`, 'info');
        
        // Call the saveMedianData function
        if (typeof saveMedianData === 'function') {
            saveMedianData(medianData, dateFrom, dateTo);
        } else {
            console.error('saveMedianData function not found!');
            showToast('Error: Save function not available', 'error');
        }
    }
    
    /**
     * Start monitoring processing status
     */
    function startProcessingMonitor() {
        console.log('Starting processing monitor...');
        
        // Clear any existing interval
        if (window.autoSaveMedianState.processingCheckInterval) {
            clearInterval(window.autoSaveMedianState.processingCheckInterval);
        }
        
        // Check every 3 seconds
        window.autoSaveMedianState.processingCheckInterval = setInterval(function() {
            const status = checkProcessingStatus();
            
            // Update last check time
            window.autoSaveMedianState.lastCheckTime = new Date();
            
            // If all processing is done and we have AIO Yes results
            if (status.allDone && status.aioYesRows > 0) {
                console.log('✅ All processing complete! Triggering auto-save...');
                
                // Stop monitoring
                clearInterval(window.autoSaveMedianState.processingCheckInterval);
                window.autoSaveMedianState.processingCheckInterval = null;
                
                // Wait a bit to ensure all UI updates are complete
                setTimeout(function() {
                    triggerAutoSaveMedianData();
                }, 2000);
            }
        }, 3000); // Check every 3 seconds
    }
    
    /**
     * Toast notification function (if not already defined)
     */
    function showToast(message, type = 'success') {
        // Check if showToast is already globally defined
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }
        
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
        
        // Add toast container if it doesn't exist
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060"></div>');
        }
        
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
    
    /**
     * Initialize the auto-save monitoring system
     */
    function init() {
        console.log('Initializing auto-save median data system...');
        
        // Wait for keywords table to be ready
        const checkTable = setInterval(function() {
            const $table = $('#keywordsTable');
            
            if ($table.length && $.fn.DataTable.isDataTable($table)) {
                console.log('Keywords table found, starting monitor...');
                clearInterval(checkTable);
                
                // Start monitoring after a short delay
                setTimeout(function() {
                    startProcessingMonitor();
                }, 2000);
            }
        }, 500);
        
        // Safety timeout - stop checking after 30 seconds
        setTimeout(function() {
            clearInterval(checkTable);
        }, 30000);
    }
    
    // Expose functions globally
    window.autoSaveMedianFix = {
        init: init,
        startMonitor: startProcessingMonitor,
        checkStatus: checkProcessingStatus,
        collectData: collectMedianDataFromAioYes,
        triggerSave: triggerAutoSaveMedianData
    };
    
    // Auto-initialize when document is ready
    $(document).ready(function() {
        // Wait for the results to load
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.id === 'keywordsTableSection' || 
                            (node.querySelector && node.querySelector('#keywordsTableSection'))) {
                            console.log('Keywords table section detected, initializing auto-save...');
                            observer.disconnect();
                            setTimeout(init, 1000);
                        }
                    });
                }
            });
        });
        
        // Start observing the result_box
        const resultBox = document.getElementById('result_box');
        if (resultBox) {
            observer.observe(resultBox, {
                childList: true,
                subtree: true
            });
        }
        
        // Also check if table already exists
        if ($('#keywordsTableSection').length) {
            observer.disconnect();
            setTimeout(init, 1000);
        }
    });
    
})();