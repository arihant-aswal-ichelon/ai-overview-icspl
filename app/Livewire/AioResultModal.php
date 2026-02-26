<?php

namespace App\Livewire;

use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Str;

class AioResultModal extends Component
{
    public $keyword = '';
    public $isOpen = false;
    public $loading = false;
    public $status = '';
    public $aioData = null;
    
    // Add properties for the data
    public $keywordData = null;
    public $keywordRequestId = null;
    public $clientPropertyId = null;
    public $domainManagementId = null;
    
    protected $listeners = ['openAioModal' => 'openModal'];

    // In AioResultModal.php
// public function mount(
//         $keyword,
//         $keyword_data,
//         $keyword_request_id,
//         $client_property_id,
//         $domainmanagement_id)
// {
//     $params=[
//         'keyword' => $keyword,
//         'keyword_data' => $keyword_data,
//         'keyword_request_id' => $keyword_request_id,
//         'client_property_id' => $client_property_id,
//         'domainmanagement_id' => $domainmanagement_id
//     ];
//     Log::debug('AioResultModal component mounted', [$keyword]);
    
//     // If params are provided on mount, handle them
    
//     $this->openModal($keyword,
//         $keyword_data,
//         $keyword_request_id,
//         $client_property_id,
//         $domainmanagement_id);
// }

    public function openModal($keyword,
        $keyword_data,
        $keyword_request_id,
        $client_property_id,
        $domainmanagement_id)
    {
        if (empty($params) || !is_array($params)) {
            Log::error('openModal called without params', [
                $keyword,
                $keyword_data,
                $keyword_request_id,
                $client_property_id,
                $domainmanagement_id
            ]);
            return;
        }

        Log::debug('=== AIO MODAL OPEN START ===');
        Log::debug('Raw params received:', ['params' => $params]);
        Log::debug('Raw params received:', ['params' => $params]);
        
        // Extract all parameters
        $this->keyword = $params['keyword'] ?? '';
        $this->keywordData = $params['keyword_data'] ?? null;
        $this->keywordRequestId = $params['keyword_request_id'] ?? null;
        $this->clientPropertyId = $params['client_property_id'] ?? null;
        $this->domainManagementId = $params['domainmanagement_id'] ?? null;
        
        // Log extracted data with more details
        Log::info('AioResultModal - Data extracted', [
            'keyword' => $this->keyword,
            'keywordData_exists' => !empty($this->keywordData),
            'keywordRequestId' => $this->keywordRequestId,
            'clientPropertyId' => $this->clientPropertyId,
            'domainManagementId' => $this->domainManagementId,
            'full_keywordData_type' => gettype($this->keywordData),
            'keywordData_sample' => is_array($this->keywordData) ? array_slice($this->keywordData, 0, 2) : $this->keywordData,
        ]);
        
        $this->isOpen = true;
        $this->loading = true;
        $this->aioData = null;
        
        // Validate required parameters
        if (!$this->keywordData || !$this->keywordRequestId) {
            $this->status = 'Error: Missing required parameters.';
            Log::warning('AioResultModal - Missing required parameters', [
                'keywordData' => $this->keywordData,
                'keywordRequestId' => $this->keywordRequestId,
                'clientPropertyId' => $this->clientPropertyId,
                'domainManagementId' => $this->domainManagementId,
            ]);
            $this->loading = false;
            Log::debug('=== AIO MODAL OPEN END (Missing params) ===');
            return;
        }
        
        $this->status = 'Extracting AIO Result...';
        Log::debug('Modal state set to open, loading started');
        
        // Dispatch event to show Bootstrap modal immediately
        $this->dispatch('showAioModal');
        Log::debug('Dispatched showAioModal event');
        
        // Start fetching data in background
        Log::debug('Starting getAioResult() method');
        $this->getAioResult();
        
        Log::debug('=== AIO MODAL OPEN END ===');
    }

    public function getAioResult()
    {
        try {
            Log::debug('getAioResult() called');
            $this->status = 'Processing keyword data...';
            
            // Validate required parameters
            if (!$this->keywordData || !$this->keywordRequestId) {
                throw new \Exception('Required parameters missing.');
            }

            Log::info('Calling store.keyword.planner endpoint', [
                'keyword' => $this->keyword,
                'keywordRequestId' => $this->keywordRequestId,
                'clientPropertyId' => $this->clientPropertyId,
                'domainManagementId' => $this->domainManagementId,
                'keywordData_size' => is_array($this->keywordData) ? count($this->keywordData) : 'N/A',
            ]);

            // Call the endpoint that stores data with all parameters
            $response = Http::post(route('store.keyword.planner'), [
                'keyword' => $this->keyword,
                'keyword_request_id' => $this->keywordRequestId,
                'client_property_id' => $this->clientPropertyId,
                'domainmanagement_id' => $this->domainManagementId,
                'keyword_data' => $this->keywordData,
                '_token' => csrf_token()
            ]);
            
            Log::debug('API Response received', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);
            
            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('API request failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new \Exception('Failed to store keyword data. Status: ' . $response->status());
            }
            
            $data = $response->json();
            Log::debug('API Response parsed', [
                'success' => $data['success'] ?? false,
                'has_search_data' => isset($data['data']['search_data']),
                'has_ai_overview' => isset($data['data']['ai_overview']),
            ]);
            
            if (!$data['success']) {
                throw new \Exception($data['message'] ?? 'Unknown error from API');
            }
            
            // Check if AI Overview data needs to be fetched
            if (isset($data['data']['search_data'])) {
                $this->aioData = $data['data']['ai_overview'] ?? null;
                $this->status = 'AIO Result extracted and stored successfully!';
                Log::info('AIO data retrieved from API', [
                    'aioData_exists' => !empty($this->aioData),
                ]);
            } else {
                // If no AI data in response, try to fetch it directly
                Log::debug('No AI data in initial response, calling fetchDirectAioResult');
                $this->fetchDirectAioResult();
            }
            
        } catch (\Exception $e) {
            Log::error('AIO Result Error in getAioResult()', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->status = 'Error: ' . $e->getMessage();
            $this->dispatch('aioModalError', keyword: $this->keyword);
            
            // Log the error for debugging
            Log::error('AIO Result Error: ' . $e->getMessage());
            Log::error('Parameters received:', [
                'keyword' => $this->keyword,
                'keywordRequestId' => $this->keywordRequestId,
                'clientPropertyId' => $this->clientPropertyId,
                'domainManagementId' => $this->domainManagementId,
            ]);
        }
        
        $this->loading = false;
        Log::debug('getAioResult() completed, loading set to false');
    }
    
    private function fetchDirectAioResult()
    {
        try {
            $this->status = 'Fetching AI Overview directly...';
            Log::debug('fetchDirectAioResult() called');
            
            // Call the getAioResult endpoint directly
            $response = Http::post(route('keyword-analysis.get-aio-result'), [
                'keyword' => $this->keyword,
                '_token' => csrf_token()
            ]);
            
            Log::debug('Direct AIO API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                Log::debug('Direct AIO Response parsed', [
                    'success' => $data['success'] ?? false,
                    'has_ai_overview' => isset($data['data']['ai_overview']),
                ]);
                
                if ($data['success'] && isset($data['data']['ai_overview'])) {
                    $this->aioData = $data['data']['ai_overview'];
                    $this->status = 'AIO Result fetched successfully!';
                    Log::info('Direct AIO fetch successful');
                } else {
                    $this->status = 'No AI Overview available for this keyword.';
                    Log::warning('Direct AIO fetch returned no data');
                }
            } else {
                $errorBody = $response->body();
                Log::error('Direct AIO API request failed', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                ]);
                throw new \Exception('Failed to fetch AI Overview data.');
            }
        } catch (\Exception $e) {
            Log::error('Error in fetchDirectAioResult()', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $this->status = 'Could not fetch AI Overview: ' . $e->getMessage();
        }
    }


    public function closeModal()
    {
        $this->isOpen = false;
        $this->loading = false;
        $this->status = '';
        $this->aioData = null;
        
        // Reset all properties
        $this->keyword = '';
        $this->keywordData = null;
        $this->keywordRequestId = null;
        $this->clientPropertyId = null;
        $this->domainManagementId = null;
        
        // Dispatch event to hide Bootstrap modal
        $this->dispatch('hideAioModal');
        
        // Notify that modal is closed so buttons can be reset
        $this->dispatch('aioModalClosed', keyword: $this->keyword);
    }

    public function render()
    {
        return view('livewire.aio-result-modal');
    }
}