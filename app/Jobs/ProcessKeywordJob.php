<?php

namespace App\Jobs;

use App\Models\KeywordPlanner;
use App\Models\AiOverview;
use App\Models\OrganicResult;
use App\Models\RelatedQuestions;
use App\Models\RelatedSearches;
use App\Helpers\GeneralHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessKeywordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $keyword;
    protected $keywordPlannerId;
    protected $keywordRequestId;
    protected $clientPropertyId;
    protected $domainManagementId;
    protected $clusterRequestId;

    public function __construct($keyword, $keywordPlannerId, $keywordRequestId, $clientPropertyId, $domainManagementId, $clusterRequestId)
    {
        $this->keyword = $keyword;
        $this->keywordPlannerId = $keywordPlannerId;
        $this->keywordRequestId = $keywordRequestId;
        $this->clientPropertyId = $clientPropertyId;
        $this->domainManagementId = $domainManagementId;
        $this->clusterRequestId = $clusterRequestId;
    }

    public function handle()
    {
        try {
            // Fetch search results
            $organicResultsData = [];
            $relatedQuestionsData = [];
            $relatedSearchesData = [];

            $searchJson = GeneralHelper::getSearchResult($this->keyword);
            $searchData = json_decode($searchJson, true);

            if (!$searchData) {
                Log::error("Failed to decode JSON or empty response for keyword: {$this->keyword}");
                throw new \Exception("Failed to fetch or decode search results for keyword: {$this->keyword}");
            }
            // Process AI Overview
            $aiOverview = null;
            $hasAio = false;

            if (isset($searchData['ai_overview'])) {
                
                if (!isset($searchData['ai_overview']['page_token'])) {
                    $aiOverview = $searchData['ai_overview'];
                    $hasAio = true;
                } else {
                    $aioJson = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                    $aiOverview = json_decode($aioJson, true);
                    $hasAio = true;
                }
            }

            // Store AI Overview
            if ($aiOverview) {
                
                AiOverview::create([
                    'domainmanagement_id' => $this->domainManagementId,
                    'client_property_id' => $this->clientPropertyId,
                    'keyword_request_id' => $this->keywordRequestId,
                    'keyword_planner_id' => $this->keywordPlannerId,
                    'cluster_request_id' => $this->clusterRequestId,
                    'text_blocks' => isset($aiOverview['text_blocks']) ? 
                        json_encode($aiOverview['text_blocks'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'json' => $aiOverview ? 
                        json_encode($aiOverview, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : null,
                    'markdown' => $aiOverview['markdown'] ?? null,
                ]);
                
                // Update keyword planner AI status
                KeywordPlanner::where('id', $this->keywordPlannerId)->update(['ai_status' => '1']);    
            } else {
                KeywordPlanner::where('id', $this->keywordPlannerId)->update(['ai_status' => '0']);
            }

            // Store Organic Results
            if (isset($searchData['organic_results'])) {
                foreach ($searchData['organic_results'] as $result) {

                    $organicResultsData[] = [
                        'domainmanagement_id' => $this->domainManagementId,
                        'client_property_id' => $this->clientPropertyId,
                        'keyword_request_id' => $this->keywordRequestId,
                        'cluster_request_id' => $this->clusterRequestId,
                        'keyword_planner_id' => $this->keywordPlannerId,
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
                        'keyword_planner_id' => $this->keywordPlannerId,
                        'keyword_request_id' => $this->keywordRequestId,
                        'cluster_request_id' => $this->clusterRequestId,
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
                        'keyword_planner_id' => $this->keywordPlannerId,
                        'keyword_request_id' => $this->keywordRequestId,
                        'cluster_request_id' => $this->clusterRequestId,
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
            $this->updateProcessingStatus();
            Log::info("Keyword: {$this->keyword}, {$this->keywordPlannerId}, {$this->keywordRequestId}, {$this->clientPropertyId}, {$this->domainManagementId}, {$this->clusterRequestId}");

        } catch (\Exception $e) {
            Log::error("Error processing keyword {$this->keyword}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'keyword_planner_id' => $this->keywordPlannerId,
                'keyword_request_id' => $this->keywordRequestId
            ]);
            $this->updateProcessingStatus(false);
        }
    }
    private function updateProcessingStatus($success = true)
    {
        // You might want to store status per session or globally
        $cacheKey = "keyword_processing_{$this->keywordRequestId}";
        $status = cache()->get($cacheKey, [
            'total_keywords' => 0,
            'processed_count' => 0,
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'processing'
        ]);
        
        $status['processed_count']++;
        if ($success) {
            $status['success_count']++;
        } else {
            $status['failed_count']++;
        }
        
        // Check if all are processed
        if ($status['processed_count'] >= $status['total_keywords']) {
            $status['status'] = 'complete';
        }
        
        cache()->put($cacheKey, $status, now()->addHours(2));
    }

}