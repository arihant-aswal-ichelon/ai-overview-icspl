<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\HistoryLog;
use App\Models\KeywordPlanner;
use App\Models\KeywordRequest;
use App\Models\OrganicResult;
use App\Models\RelatedQuestions;
use App\Models\AiOverview;
use App\Models\RelatedSearches;

class HistoryLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($dmid, $cpid)
    {
        $keywordPlanners = DB::table('keyword_planner as kp')
            ->join('history_log as hl', 'kp.id', '=', 'hl.keyword_planner_id')
            ->where('kp.domainmanagement_id', $dmid)
            ->where('kp.client_property_id', $cpid)
            ->select(
                'kp.id as planner_id',
                'kp.keyword_p',
                DB::raw('COUNT(hl.id) as total_logs'),
                DB::raw('MAX(hl.updated_at) as last_updated'),
                DB::raw('(SELECT aio_status FROM history_log WHERE keyword_planner_id = kp.id ORDER BY created_at DESC LIMIT 1) as aio_status'),
                DB::raw('(SELECT search_status FROM history_log WHERE keyword_planner_id = kp.id ORDER BY created_at DESC LIMIT 1) as search_status')
            )
            ->groupBy('kp.id', 'kp.keyword_p')
            ->orderBy('kp.id')
            ->get();

        $allLogs = HistoryLog::where('history_log.domainmanagement_id', $dmid)
            ->join('keyword_request', 'history_log.keyword_request_id', 'keyword_request.id')
            ->where('history_log.client_property_id', $cpid)
            ->select('history_log.*', 'keyword_request.keyword as keyword_r')
            ->orderBy('history_log.created_at', 'desc')
            ->get()
            ->groupBy('keyword_planner_id');
        // dd($allLogs->toArray());
        return view('history-log.index', compact('keywordPlanners', 'allLogs', 'dmid', 'cpid'));
    }
    /**
     * Show the detail page for a specific history log entry.
     * priority_sync = 1 (default): use keyword_planner_id only
     * priority_sync = 0: filter AiOverview also by history_log_id
     */
    public function showLogs($dmid, $cpid, $keywordPlannerId)
    {
        // Fetch all logs for this planner
        $allLogs = DB::table('history_log as hl')
            ->join('keyword_request as kr', 'hl.keyword_request_id', '=', 'kr.id')
            ->join('keyword_planner as kp', 'hl.keyword_planner_id', '=', 'kp.id')
            ->where('hl.domainmanagement_id', $dmid)
            ->where('hl.client_property_id', $cpid)
            ->where('hl.keyword_planner_id', $keywordPlannerId)
            ->select(
                'hl.id',
                'hl.keyword_planner_id',
                'hl.aio_status',
                'hl.search_status',
                'hl.created_at',
                'kr.keyword as keyword_r',
                'kp.keyword_p as keyword_p'
            )
            ->orderBy('hl.created_at', 'desc')
            ->get();

        // Keyword planner & request for breadcrumb / page header
        $keywordPlanner = KeywordPlanner::where('id', $keywordPlannerId)->first();
        $keywordRequest = KeywordRequest::where('id', $keywordPlanner->keyword_request_id)->first();

        return view('history-log.show', compact(
            'dmid', 'cpid', 'keywordPlannerId',
            'allLogs', 'keywordPlanner', 'keywordRequest'
        ));
    }

    /**
     * AJAX: Return the keyword-extracted partial HTML for a given history log.
     */
    public function getExtractedResult(Request $request, $dmid, $cpid, $keywordPlannerId, $historyLogId)
    {
        // 1. Validate keyword planner exists for this dmid + cpid
        $keywordplanner = KeywordPlanner::where('id', $keywordPlannerId)
            ->where('domainmanagement_id', $dmid)
            ->where('client_property_id', $cpid)
            ->first();

        if (!$keywordplanner) {
            return response()->json([
                'error' => "Keyword Planner #$keywordPlannerId not found for the given domain or client property."
            ], 404);
        }

        // 2. Validate history log exists and belongs to this planner/dmid/cpid
        $selectedLog = DB::table('history_log as hl')
            ->join('keyword_request as kr', 'hl.keyword_request_id', '=', 'kr.id')
            ->where('hl.id', $historyLogId)
            ->where('hl.domainmanagement_id', $dmid)
            ->where('hl.client_property_id', $cpid)
            ->where('hl.keyword_planner_id', $keywordPlannerId)
            ->select('hl.*', 'kr.keyword as keyword_r')
            ->first();

        if (!$selectedLog) {
            return response()->json([
                'error' => "History Log #$historyLogId not found for Keyword Planner #$keywordPlannerId."
            ], 404);
        }

        // 3. Determine priority_sync
        $prioritySync = (int) $selectedLog->aio_status;

        // 4. Keyword request
        $keywordRequest = KeywordRequest::where('id', $keywordplanner->keyword_request_id)->first();

        if (!$keywordRequest) {
            return response()->json([
                'error' => "Keyword Request not found for Keyword Planner #$keywordPlannerId."
            ], 404);
        }

        // 5. Remaining data
        $organicResults   = OrganicResult::where('keyword_planner_id', $keywordPlannerId)->where('history_log_id',$historyLogId)->get();
        $relatedQuestions = RelatedQuestions::where('keyword_planner_id', $keywordPlannerId)->where('history_log_id',$historyLogId)->get();
        $relatedSearches  = RelatedSearches::where('keyword_planner_id', $keywordPlannerId)->where('history_log_id',$historyLogId)->get();

        $aiOverviewQuery = AiOverview::where('keyword_planner_id', $keywordPlannerId);
        if ($prioritySync === 0) {
            $aiOverviewQuery->where('history_log_id', $historyLogId);
        }
        $aiOverview = $aiOverviewQuery->orderBy('priority_sync', 'asc')->get();

        $html = view('history-log.keyword-extracted', compact(
            'keywordplanner', 'keywordRequest',
            'organicResults', 'relatedQuestions', 'aiOverview', 'relatedSearches'
        ))->render();

        return response()->json(['html' => $html]);
    }

    public function getLogsforPlanner($dmid, $cpid, $keywordPlannerId)
    {
        $logs = HistoryLog::where('domainmanagement_id', $dmid)
            ->where('client_property_id', $cpid)
            ->where('keyword_planner_id', $keywordPlannerId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}