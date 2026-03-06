<?php

namespace App\Console\Commands;

use App\Jobs\ProcessKeywordStatusJob;
use App\Models\AiOverview;
use App\Models\HistoryLog;
use App\Models\KeywordPlanner;
use App\Models\KeywordRequest;
use App\Models\MedianFetch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoSyncAIOforclient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AutoSyncAIOforclient:send';
    protected $description = 'Send scheduled emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bucket = MedianFetch::where('median-fetch.bucket', 1)->where('median-fetch.keyword_request_id', 1)
            ->join('client_properties', 'median-fetch.client_property_id', 'client_properties.id')
            ->select('median-fetch.*', 'client_properties.frequency')
            ->get();
        
        // dd($bucket->toArray());
        $frequencyago= [];
        foreach ($bucket as $item) {
            // Parse frequency format DD:HH:MM → convert to total minutes
            $aiOverview = AiOverview::where('keyword_request_id', $item->keyword_request_id)
                ->where('client_property_id', $item->client_property_id)
                ->where('keyword_planner_id', $item->keyword_p)
                ->where('priority_sync', 1)
                ->orderBy('id','desc')
                ->first();
            // dd($aiOverview->toArray());

            [$days, $hours, $minutes] = explode(':', $item->frequency);
            $frequencyInMinutes = ((int)$days * 1440) + ((int)$hours * 60) + (int)$minutes;

            $updatedAt = Carbon::parse($aiOverview->updated_at);
            // dd($updatedAt);
            $nextRunDue = $updatedAt->copy()->addMinutes($frequencyInMinutes);
            // Check if current time has passed the next scheduled run
            if (Carbon::now()->greaterThanOrEqualTo($nextRunDue)) {
                $keywordPlanner = KeywordPlanner::where('id', $item->keyword_p)->first();
                $keywordRequest = KeywordRequest::find($item->keyword_request_id);

                if (!$keywordRequest) {
                    Log::warning("No KeywordRequest found for id: {$item->keyword_request_id}");
                    continue;
                }

                // Generate a unique session ID (same pattern as keywordStore)
                $sessionId = 'auto_sync_' . $item->keyword_request_id . '_' . time();

                // dd($keywordPlanner->toArray());
                $keyword = $keywordPlanner->keyword_p;

                // Initialize cache for this keyword (mirrors keywordStore logic)
                $cacheKey = "keyword_status_{$sessionId}_{$keywordPlanner->id}";
                cache()->put($cacheKey, [
                    'keyword'                => $keyword,
                    'search_api_status'      => 'Processing',
                    'aio_status'             => 'Processing',
                    'client_mentioned_status'=> 'Processing',
                    'processed'              => false,
                    'index'                  => $keywordPlanner->id,
                    'keyword_planner_id'     => null,
                ], now()->addHours(24));

                // // Create a HistoryLog record for this auto-sync run
                $historyLog = HistoryLog::create([
                    'domainmanagement_id'  => $item->domainmanagement_id,
                    'client_property_id'   => $item->client_property_id,
                    'keyword_request_id'   => $item->keyword_request_id,
                    'keyword_planner_id'   => $keywordPlanner->id,
                ]);

                // // Dispatch the job — same signature as keywordStore + history_log_id
                // $frequencyago[]= [$keyword,
                //     $keywordPlanner->id,
                // ];
                ProcessKeywordStatusJob::dispatch(
                    $keyword,
                    $item->keyword_request_id,
                    $item->client_property_id,
                    $item->domainmanagement_id,
                    $sessionId,
                    $keywordPlanner->id,
                    null,
                    $historyLog->id,
                );
                Log::info("Dispatched job for keyword: {$keyword} | keyword_request_id: {$item->keyword_request_id} | index: {$keywordPlanner->id} | history_log_id: {$historyLog->id}");
            }
        }
        return Command::SUCCESS;
    }

}
