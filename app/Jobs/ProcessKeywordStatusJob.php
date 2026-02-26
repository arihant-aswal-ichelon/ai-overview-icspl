<?php

namespace App\Jobs;

use App\Models\KeywordPlanner;
use App\Models\AiOverview;
use App\Helpers\GeneralHelper;
use App\Models\Client_propertiesModel;
use App\Models\OrganicResult;
use App\Models\RelatedQuestions;
use App\Models\RelatedSearches;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessKeywordStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $keyword;
    protected $keywordRequestId;
    protected $clientPropertyId;
    protected $domainManagementId;
    protected $sessionId;
    protected $index;
    protected $keyword_planner_id;


    public function __construct($keyword, $keywordRequestId, $clientPropertyId, $domainManagementId, $sessionId, $index, $keyword_planner_id)
    {
        $this->keyword = $keyword;
        $this->keywordRequestId = $keywordRequestId;
        $this->clientPropertyId = $clientPropertyId;
        $this->domainManagementId = $domainManagementId;
        $this->sessionId = $sessionId;
        $this->index = $index;
        $this->keyword_planner_id = $keyword_planner_id;
    }

    public function handle()
    {
        $organicResultsData = [];
        $relatedQuestionsData = [];
        $relatedSearchesData = [];
    
        $cacheKey = "keyword_status_{$this->sessionId}_{$this->index}";
        
        try {
            // Step 1: Check if keyword exists in KeywordPlanner

            $keywordPlanner = KeywordPlanner::where([
                ['keyword_p', $this->keyword],
                ['client_property_id', $this->clientPropertyId],
                ['domainmanagement_id', $this->domainManagementId],
                ['keyword_request_id', $this->keywordRequestId]
            ])->first();

            // If not exists, create it
            if (!$keywordPlanner) {
                $keywordPlanner = KeywordPlanner::create([
                    'keyword_p' => $this->keyword,
                    'client_property_id' => $this->clientPropertyId,
                    'domainmanagement_id' => $this->domainManagementId,
                    'keyword_request_id' => $this->keywordRequestId,
                    'ai_status' => '0',
                ]);
            }
            $this->keyword_planner_id = $keywordPlanner->id;

            // Step 2: Update Search API Status to "Done"
            Cache::put($cacheKey, [
                'keyword' => $this->keyword,
                'search_api_status' => 'Done',
                'aio_status' => 'Processing',
                'client_mentioned_status' => 'Processing',
                'processed' => false,
                'keyword_planner_id' => $this->keyword_planner_id,
                'index' => $this->index,
            ], now()->addHours(24));

            // Step 3: Fetch search results and check for AIO
            $searchJson = GeneralHelper::getSearchResult($this->keyword);
            $searchData = json_decode($searchJson, true);

            if (!$searchData) {
                throw new \Exception("Failed to fetch search results");
            }

            // Step 4: Process AI Overview
            $hasAio = false;
            $aiOverviewData = null;
            
            if (isset($searchData['ai_overview'])) {
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $aiOverviewData = $searchData['ai_overview'];
                    $hasAio = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                } else {
                    $aioJson = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $aiOverviewData = json_decode($aioJson, true);
                    $hasAio = !empty($aiOverviewData['markdown']) || !empty($aiOverviewData['text_blocks']);
                }
            }

            // Step 5: Save AIO if exists
            if ($hasAio && $aiOverviewData) {
                AiOverview::where('keyword_planner_id', $this->keyword_planner_id)->whereNull('cluster_request_id')
                    ->update(['priority_sync' => '0']);
                AiOverview::create([
                    'domainmanagement_id' => $this->domainManagementId,
                    'client_property_id' => $this->clientPropertyId,
                    'keyword_request_id' => $this->keywordRequestId,
                    'keyword_planner_id' => $this->keyword_planner_id,
                    'text_blocks' => isset($aiOverviewData['text_blocks']) ? 
                        json_encode($aiOverviewData['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'json' => json_encode($aiOverviewData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                    'markdown' => $aiOverviewData['markdown'] ?? null,
                ]);
                
                $keywordPlanner->update(['ai_status' => '1']);
            } else {
                $keywordPlanner->update(['ai_status' => '0']);
            }

            // Store Organic Results
            if (isset($searchData['organic_results'])) {
                foreach ($searchData['organic_results'] as $result) {

                    $organicResultsData[] = [
                        'domainmanagement_id' => $this->domainManagementId,
                        'client_property_id' => $this->clientPropertyId,
                        'keyword_request_id' => $this->keywordRequestId,
                        'keyword_planner_id' => $this->keyword_planner_id,
                        'position' => $result['position'] ?? null,
                        'title' => $result['title'] ?? null,
                        'link' => $result['link'] ?? null,
                        'source' => $result['source'] ?? null,
                        'domain' => $result['domain'] ?? null,
                        'displayed_link' => $result['displayed_link'] ?? null,
                        'snippet' => $result['snippet'] ?? null,
                        'snippet_highlighted_word' => isset($result['snippet_highlighted_words']) ? 
                            implode(", ", $result['snippet_highlighted_words']) : null,
                        'sitelinks' => (isset($result['sitelinks'])) ? 
                            json_encode($result['sitelinks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'favicon' => null,
                        'date' => $result['date'] ?? null,
                        'json' => $result ? json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($organicResultsData)) {
                    foreach (array_chunk($organicResultsData, 300) as $chunk) {
                        OrganicResult::insert($chunk);
                    }
                }
            }

            // Store Related Questions
            if (isset($searchData['related_questions'])) {
                foreach ($searchData['related_questions'] as $question) {
                    if ($question['is_ai_overview'] ?? false) {
                        continue;
                    }
                    
                    $relatedQuestionsData[] = [
                        'domainmanagement_id' => $this->domainManagementId,
                        'client_property_id' => $this->clientPropertyId,
                        'keyword_planner_id' => $this->keyword_planner_id,
                        'keyword_request_id' => $this->keywordRequestId,
                        'question' => $question['question'] ?? null,
                        'answer' => isset($question['answer']) ? $question['answer'] : ($question['markdown'] ?? null),
                        'source_title' => $question['source']['title'] ?? null,
                        'source_link' => $question['source']['link'] ?? null,
                        'source_source' => $question['source']['source'] ?? null,
                        'source_domain' => $question['source']['domain'] ?? null,
                        'source_displayed_link' => $question['source']['displayed_link'] ?? null,
                        'source_favicon' => null,
                        'json' => $question ? json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'date' => $question['date'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($relatedQuestionsData)) {
                    foreach (array_chunk($relatedQuestionsData, 300) as $chunk) {
                        RelatedQuestions::insert($chunk);
                    }
                }
            }
            // Store Related Searches
            if (isset($searchData['related_searches'])) {   
                foreach ($searchData['related_searches'] as $index => $relatedSearch) {
                    
                    $relatedSearchesData[] = [
                        'domainmanagement_id' => $this->domainManagementId,
                        'client_property_id' => $this->clientPropertyId,
                        'keyword_planner_id' => $this->keyword_planner_id,
                        'keyword_request_id' => $this->keywordRequestId,
                        'query' => $relatedSearch['query'] ?? null,
                        'link' => $relatedSearch['link'] ?? null,
                        'json' => $relatedSearch ? json_encode($relatedSearch, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($relatedSearchesData)) {
                    foreach (array_chunk($relatedSearchesData, 300) as $chunk) {
                        RelatedSearches::insert($chunk);
                    }
                }

            }

            // Step 6: Check if client is mentioned (you need to implement this logic)
            $clientMentioned = false;
            if ($hasAio && $aiOverviewData) {
                // Get client property to check domain
                $clientProperty = Client_propertiesModel::find($this->clientPropertyId);
                if ($clientProperty) {
                    $aiOverviewObj = new \stdClass();
                    $aiOverviewObj->markdown = $aiOverviewData['markdown'] ?? null;
                    $aiOverviewObj->text_blocks = isset($aiOverviewData['text_blocks']) 
                        ? json_encode($aiOverviewData['text_blocks']) 
                        : null;
                    $aiOverviewObj->json = json_encode($aiOverviewData);
                    
                    $clientMentioned = GeneralHelper::check_keyword_mentioned(
                        $keywordPlanner->id, 
                        $this->clientPropertyId
                    );
                }
            }

            // Step 7: Final cache update with all statuses
            Cache::put($cacheKey, [
                'keyword' => $this->keyword,
                'search_api_status' => 'Done',
                'aio_status' => $hasAio ? 'Yes' : 'No',
                'client_mentioned_status' => $clientMentioned ? 'Yes' : 'No',
                'processed' => true,
                'index' => $this->index,
                'keyword_planner_id' => $this->keyword_planner_id,
                'processed_at' => now()->toDateTimeString()
            ], now()->addHours(24));

            Log::info("Processed keyword: {$this->keyword} | keyword_planner_id: {$this->keyword_planner_id} | AIO: " . ($hasAio ? 'Yes' : 'No') . " | Client Mentioned: " . ($clientMentioned ? 'Yes' : 'No'));

        } catch (\Exception $e) {
            Log::error("Job failed for keyword {$this->keyword}: " . $e->getMessage());
            
            // Store error in cache
            Cache::put($cacheKey, [
                'keyword' => $this->keyword,
                'search_api_status' => 'Error',
                'aio_status' => 'Error',
                'client_mentioned_status' => 'Error',
                'processed' => true,
                'error' => $e->getMessage(),
                'index' => $this->index,
                'keyword_planner_id' => $this->keyword_planner_id,
                'processed_at' => now()->toDateTimeString()
            ], now()->addHours(24));
        }
    }
}