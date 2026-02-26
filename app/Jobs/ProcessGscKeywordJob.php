<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\GeneralHelper;
use Illuminate\Support\Facades\Log;

class ProcessGscKeywordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyword;
    public $index;
    public $searchTerms;
    public $sessionId;

    public function __construct($keyword, $index, $searchTerms, $sessionId)
    {
        $this->keyword = $keyword;
        $this->index = $index;
        $this->searchTerms = $searchTerms;
        $this->sessionId = $sessionId;
    }

    public function handle()
    {
        
        $cacheKey = "gsc_aio_result_{$this->sessionId}_{$this->index}";
        try {
            $hasAioOverview = false;
            $hasSearchOverview = false;
            $clientMentioned = false;
            $aiOverviewData = null;
            // $hasAio = $this->checkInAio($this->keyword);
            // Log::info("Starting job for keyword: {$this->keyword}, Index: {$this->index}");
            
            // Search for the keyword
            $searchJson = GeneralHelper::getSearchResult($this->keyword);
            $searchData = json_decode($searchJson, true);
                
            if (isset($searchData['organic_results'])) {
                $hasSearchOverview =true;
            }
            if (isset($searchData['ai_overview'])) {
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $aiOverviewData = $searchData['ai_overview'];
                    
                    // Check if it's already an array
                    if (is_array($aiOverviewData)) {
                        $hasAioOverview = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                    } elseif (is_string($aiOverviewData)) {
                        // If it's a string, try to decode it
                        $decoded = json_decode($aiOverviewData, true);
                        if ($decoded) {
                            $aiOverviewData = $decoded;
                            $hasAioOverview = !empty($decoded['markdown']) || !empty($decoded['text_blocks']);
                        }
                    }
                } else {
                    // Need to fetch AIO using page_token
                    $aio_json = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $aiOverviewData = json_decode($aio_json, true);
                    if (is_array($aiOverviewData)) {
                        $hasAioOverview = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                    }
                }

                if ($hasAioOverview && is_array($aiOverviewData)) {
                    
                    $aiOverviewObj = new \stdClass();
                    $aiOverviewObj->markdown = $aiOverviewData['markdown'] ?? null;
                    $aiOverviewObj->text_blocks = isset($aiOverviewData['text_blocks']) 
                        ? json_encode($aiOverviewData['text_blocks']) 
                        : null;
                    $aiOverviewObj->json = json_encode($aiOverviewData);
                    
                    // Check each search term
                    foreach ($this->searchTerms as $term) {
                        if (!empty($term)) {
                            $isMentioned = GeneralHelper::domainExistsInAIOverview($aiOverviewObj, $term);
                            if ($isMentioned) {
                                $clientMentioned = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            // Store in cache for real-time access
            // $clientMentioned = $hasAioOverview ? $this->checkClientMentioned() : false;
            // $cacheKey = "gsc_aio_result_{$this->sessionId}_{$this->index}";
            // $existing = cache()->get($cacheKey);
            
            // if ($existing && $existing['processed'] === true) {
            //     Log::info("Keyword {$this->keyword} already processed, skipping.");
            //     return;
            // }
            cache()->put($cacheKey, [
                'index' => $this->index,
                'keyword' => $this->keyword,
                'aio_status' => $hasAioOverview ? 'Yes' : 'No',
                'has_aio' => $hasAioOverview,
                'client_mentioned' => $clientMentioned ? 'Yes' : 'No',
                'processed' => true,
                'processed_at' => now()->toDateTimeString(),
            ], now()->addHours(24));
            
            Log::info("Processed AIO for keyword: {$this->keyword}, Has AIO: " . ($hasAioOverview ? 'Yes' : 'No'). ", Client Mentioned: " . ($clientMentioned ? 'Yes' : 'No'). ", Search Overview: " . ($hasSearchOverview ? 'Yes' : 'No'));
            
        } catch (\Exception $e) {
            Log::error("Job failed for keyword {$this->keyword}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        
            // Store error in cache
            cache()->put($cacheKey, [
                'index' => $this->index,
                'keyword' => $this->keyword, 
                'aio_status' => 'Error',
                'has_aio' => false,
                'client_mentioned' => 'Error',
                'processed' => true,
                'error' => $e->getMessage(),
                'processed_at' => now()->toDateTimeString(),
            ], now()->addHours(24));
            
            // throw $e;
            
            // $key = "gsc_aio_result_{$this->sessionId}_{$this->index}";
            // cache()->put($key, $result, now()->addHours(24));
        }
    }
}