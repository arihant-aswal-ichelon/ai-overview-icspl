<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\KeywordPlanner;
use Illuminate\Support\Facades\Log;

class CleanupStuckKeywordJobs extends Command
{
    protected $signature = 'cleanup:stuck-keyword-jobs';
    protected $description = 'Clean up stuck keyword processing jobs';

    public function handle()
    {
        $this->info('Cleaning up stuck keyword jobs...');
        
        // Find batches older than 2 hours
        $keys = Cache::get('keyword_batch_*');
        $cleaned = 0;
        
        // This is a simplified version - in production you'd need proper Redis scanning
        $batches = [
            // You'd get these from Redis or your cache driver
        ];
        
        foreach ($batches as $batchId => $batchData) {
            if (isset($batchData['started_at'])) {
                $started = \Carbon\Carbon::parse($batchData['started_at']);
                if ($started->diffInHours(now()) > 2) {
                    Cache::forget($batchId);
                    Cache::forget("keyword_batch_running_{$batchId}");
                    $cleaned++;
                    Log::info("Cleaned up stuck batch: {$batchId}");
                }
            }
        }
        
        // Also clean up jobs that were dispatched but never completed
        $stuckJobs = KeywordPlanner::whereNull('ai_status')
            ->where('created_at', '<', now()->subHours(2))
            ->get();
            
        foreach ($stuckJobs as $job) {
            // Mark as failed
            $job->update([
                'ai_status' => '0',
                'processed_at' => now(),
                'error_message' => 'Job timed out after 2 hours'
            ]);
            $cleaned++;
        }
        
        $this->info("Cleaned up {$cleaned} stuck jobs/batches");
        return 0;
    }
}