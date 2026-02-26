<?php

namespace App\Http\Controllers;

use App\Helpers\GeneralHelper;
use App\Models\Client_propertiesModel;
use App\Models\GeneratedPrompts;
use App\Models\GeneratedPromptsResponse;
use App\Models\MedianFetch;
use App\Models\RelatedQuestions;
use App\Models\RelatedSearches;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI;

class GenerateAIOPromptAnalysisController extends Controller
{
    // protected $audioAnalyzer;
    private $openaiApiKey;
    private $openai;
    public $endpoints = [];

    private $geminiApiKey;
    private $projectId;
    private $location;
    private $modelId;
    private $keyFilePath;
    
    private $whisperEndpoint = 'https://api.openai.com/v1/audio/transcriptions';
    private $chatEndpoint = 'https://api.openai.com/v1/chat/completions';
    private $geminiEndpoint;

    public function __construct(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');
        $this->openaiApiKey = env('OPENAI_API_KEY');
        $this->geminiApiKey = env('GEMINI_API_KEY');
        
        $this->projectId = env('GCP_PROJECT_ID', 'composed-arch-472508-u2');
        $this->location = env('GCP_LOCATION', 'us-central1');
        $this->modelId = env('GEMINI_MODEL_ID', 'gemini-2.5-flash');
        $this->keyFilePath = storage_path('app/service-account-key.json');
        
        $this->geminiEndpoint = "https://{$this->location}-aiplatform.googleapis.com/v1/projects/{$this->projectId}/locations/{$this->location}/publishers/google/models/{$this->modelId}:generateContent";
        $this->openai = OpenAI::client(env('GEMINI_KEY'));
        $this->endpoints=[];

        $this->client_id = $request->id;
        $this->slug_id = $request->slug;
    }
    /**
     * Display a listing of the resource.
     */
    public function index($domainmanagement_id, $client_property_id, $keyword_request_id)
    {
        $medianResults = MedianFetch::where('median-fetch.client_property_id', $client_property_id)
            ->where('median-fetch.domainmanagement_id', $domainmanagement_id)
            ->where('median-fetch.keyword_request_id', $keyword_request_id)
            ->where('median-fetch.bucket', 1)
            ->join('keyword_planner', 'median-fetch.keyword_p', 'keyword_planner.id')
            ->select([
                'median-fetch.keyword_p as mf_keyword_p',
                'median-fetch.monthlysearch_p as mf_monthlysearch_p',
                'median-fetch.competition_p as mf_competition_p',
                'median-fetch.low_bid_p as mf_low_bid_p',
                'median-fetch.high_bid_p as mf_high_bid_p',
                'median-fetch.clicks_p as mf_clicks_p',
                'median-fetch.ctr_p as mf_ctr_p',
                'median-fetch.impressions_p as mf_impressions_p',
                'median-fetch.position_p as mf_position_p',
                'median-fetch.updated_at as mf_updated_at',
                'keyword_planner.keyword_p as kp_keyword',
            ])
            ->get()
            ->map(function ($medianFetch) {
                // Extract keyword_p from each medianFetch record
                $keywordId = $medianFetch->mf_keyword_p;
                
                // Get related searches with the same keyword_planner_id (multiple records)
                $relatedSearches = RelatedSearches::where('keyword_planner_id', $keywordId)
                    ->select([
                        'query as rs_query',
                    ])->get();
                
                // Get related questions with the same keyword_planner_id (multiple records)
                $relatedQuestions = RelatedQuestions::where('keyword_planner_id', $keywordId)
                    ->select([
                        'question as rq_question',
                        'answer as rq_answer',
                        // 'source_title as rq_source_title',
                        // 'source_link as rq_source_link',
                        // 'source_source as rq_source_source',
                        // 'source_domain as rq_source_domain',
                        // 'source_displayed_link as rq_source_displayed_link',
                        // 'source_favicon as rq_source_favicon',
                        // 'json as rq_json',
                    ])->get();
                
                // Add these collections to the medianFetch object
                $medianFetch->related_searches = $relatedSearches;
                $medianFetch->related_questions = $relatedQuestions;
                
                return $medianFetch;
            });
        // dd($medianResults->toArray());
        return view('generate-aio-prompt-analysis.index', compact('medianResults', 'client_property_id', 'domainmanagement_id', 'keyword_request_id'));
    }
    public function display_aio_prompt($domainmanagement_id, $client_property_id)
    {
        $generate_prompt_results = GeneratedPrompts::where([
            'generated_prompts.domainmanagement_id' => $domainmanagement_id,
            'generated_prompts.client_property_id'  => $client_property_id,
        ])
        ->join('median-fetch', 'median-fetch.keyword_request_id', '=', 'generated_prompts.keyword_request_id')
        ->where('median-fetch.bucket',1)
        ->select('generated_prompts.*', 'median-fetch.median_name')
        ->distinct('generated_prompts.id')
        ->get();
        // dd($generate_prompt_results);

        return view('generate-aio-prompt-analysis.display-aio-prompt',
            compact('generate_prompt_results', 'client_property_id', 'domainmanagement_id')
        );
    }
    
    public function display_specific_aio_prompt($id)
    {
        $record = GeneratedPrompts::find($id);

        if (!$record) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $keyword_mentioned = Client_propertiesModel::where(
            'domainmanagement_id',
            $record->domainmanagement_id
        )->value('keyword_mentioned_array');

        $prompt = json_decode($record->prompt, true);
        $results = [
            [
                'keyword_id' => $record->keyword_ids,
                'keyword'    => $record->keyword_ids,
                'prompt'     => $prompt,
            ]
        ];

        // ── Load saved AI responses from DB, grouped by source ────────────
        // Each row's `prompt_json` column holds a JSON blob with per-prompt data.
        $savedRows = GeneratedPromptsResponse::where('generated_prompt_id', $id)
            ->get(['source', 'prompt_json']);

        // Group into: { 'chatgpt' => [...], 'gemini' => [...], 'searchapi' => [...] }
        $savedResponses = [];
        foreach ($savedRows as $row) {
            $decoded = json_decode($row->prompt_json, true);
            if (!$decoded) continue;
            $savedResponses[$row->source][] = $decoded;
        }

        $generated_prompt_id = $id;

        return view(
            'generate-aio-prompt-analysis.prompt-table',
            compact('results', 'keyword_mentioned', 'savedResponses', 'generated_prompt_id')
        );
    }

    public function send_prompt(Request $request){

        
    }

    public function generate_prompt(Request $request)
    {
        $selectedKeywords = $request->input('selected_keywords', []);

        if (empty($selectedKeywords)) {
            return response()->json(['error' => 'No keywords provided.'], 422);
        }
        $keywords = json_encode($selectedKeywords['keyword']);
        $people_also_asked = json_encode($selectedKeywords['questions']);
        $also_asked_questions = json_encode($selectedKeywords['searches']);
        $results = []; 

        $rawprompt = $this->getAIsearchtext($keywords, $people_also_asked, $also_asked_questions);
        $cleanJson = trim(
            str_replace(['```json', '```'], '', $rawprompt)
        );
        
        $keyword_mentioned = Client_propertiesModel::where(
            'domainmanagement_id',
            $request->domainmanagement_id
        )->value('keyword_mentioned_array');

        GeneratedPrompts::create([
            'domainmanagement_id'=>$request->domainmanagement_id,
            'client_property_id'=>$request->client_property_id,
            'keyword_request_id'=>$request->keyword_request_id,
            'keyword_ids'=>implode(", ", $selectedKeywords['keyword_id']),
            'prompt'=>$cleanJson,
        ]);
        $prompt = json_decode($cleanJson, true);

        // dd($prompt);
        $results[] = [
            'keyword_id'      => $selectedKeywords['keyword_id'],
            'keyword'         => $keywords,
            'prompt'          => $prompt,
            'questions_count' => 0,
            'searches_count'  => 0,
        ];

        return view('generate-aio-prompt-analysis.prompt-table', compact('keyword_mentioned','results'));
    }
    
    public function update_prompt(Request $request)
    {
        dd($request->all());
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

    /**
     * Get Google access token using service account
     */
    private function getGoogleAccessToken()
    {
        if (!file_exists($this->keyFilePath)) {
            throw new Exception("Service account key file not found: " . $this->keyFilePath);
        }

        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        $creds = new ServiceAccountCredentials($scopes, $this->keyFilePath);
        $token = $creds->fetchAuthToken();

        if (!isset($token['access_token'])) {
            throw new Exception("Could not obtain access token");
        }

        return $token['access_token'];
    }

    private function getAIsearchtext($root_keyword, $people_also_asked, $also_asked_questions){
        $prompt = <<<PROMPT
        You are an AI Search Visibility Strategist and Competitive Intelligence Analyst.

        INPUT DATA:

        Root Keyword:
        $root_keyword

        People Also Asked (PAA):
        $people_also_asked

        Also Asked Questions:
        $also_asked_questions

        OBJECTIVES:

        ---------------------------------------------------
        STEP 1 — Competitor Detection & Mapping
        ---------------------------------------------------

        1. Identify brand names mentioned inside:
        - People Also Asked
        - Also Asked Questions

        2. Extract:
        - Unique competitor brand names
        - Frequency of occurrence
        - Dataset source (PAA / Also Asked / Both)

        3. Classify competitor presence level:
        - High (3+ mentions)
        - Medium (2 mentions)
        - Low (1 mention)

        ---------------------------------------------------
        STEP 2 — Intent Clustering
        ---------------------------------------------------

        Cluster all queries into:
        - Informational
        - Commercial Investigation
        - Comparison
        - Transactional
        - Local Intent
        - Authority / Trust Validation
        - Problem-Solution

        ---------------------------------------------------
        STEP 3 — Generate 20 AI Search Prompts (STRICTLY NON-COMPETITOR)
        ---------------------------------------------------

        Generate 20 realistic AI-style prompts users would ask AI platforms.

        Rules:
        - DO NOT include any competitor brand names
        - Prompts must be brand-neutral
        - Conversational and natural
        - Reflect real buyer behavior
        - Include:
        • Recommendation queries
        • Comparison-type queries (generic, not brand vs brand)
        • Cost/pricing
        • Best-in-category
        • Expert validation
        • Location-based (if relevant)
        • Problem-solution prompts
        - Avoid repetition
        - Ground prompts in provided data

        ---------------------------------------------------
        STEP 4 — Select Top 10 Strategic Prompts
        ---------------------------------------------------

        From the 20 prompts, select Top 10 for AI Visibility Tracking.

        Rules:
        - Must remain brand-neutral
        - Must not contain competitor names
        - Prioritize high commercial intent
        - Prioritize prompts that generate ranked outputs
        - Prioritize authority validation behavior

        ---------------------------------------------------
        STEP 5 — Competitor-Trigger Prompts (Separate Section)
        ---------------------------------------------------

        If competitor brands were detected:

        Generate 5 competitor-focused prompts designed to:
        - Trigger comparison with competitors
        - Force ranking outputs
        - Test brand differentiation
        - Surface authority positioning

        These competitor prompts must be placed ONLY in this section.

        If no competitors found, write:
        “No competitor brand detected in input data.”

        ---------------------------------------------------
        OUTPUT FORMAT
        ---------------------------------------------------

        SECTION A: Competitor Presence Analysis

        Table:
        1. Competitor Name
        2. Dataset (PAA / Also Asked / Both)
        3. Frequency
        4. Presence Level

        ---------------------------------------------------

        SECTION B: 20 AI Search Prompts (Brand-Neutral Only)

        Numbered list.

        ---------------------------------------------------

        SECTION C: Top 10 Recommended Prompts for AI Visibility Tracking (Brand-Neutral Only)

        Table:
        1. Prompt
        2. Intent Category
        3. Strategic Value (1–2 lines)
        4. Expected AI Output Type

        ---------------------------------------------------

        SECTION D: Competitor-Trigger Prompts
        (5 prompts OR state no competitor detected)

        Do not restate input.
        Do not explain methodology.
        Be structured and analytical.

        Provide the output in the provided JSON format:
        {
        "section_a_competitor_presence_analysis": {
            "summary": "",
            "table": [
            {
                "competitor_name": "",
                "dataset": "",
                "frequency": 0,
                "presence_level": ""
            }
            ]
        },
        "section_b_20_ai_search_prompts_brand_neutral": [
            "",
            "",
            ...
        ],
        "section_c_top_10_recommended_prompts_for_ai_visibility_tracking": [
            {
            "prompt": "",
            "intent_category": "",
            "strategic_value": "",
            "expected_ai_output_type": ""
            },
            {
            "prompt": "",
            "intent_category": "",
            "strategic_value": "",
            "expected_ai_output_type": ""
            },
            ...
        ],
        "section_d_competitor_trigger_prompts": [
            "",
            "",
            ...
        ]
        }
        PROMPT;
        return $this->analyzeWithGeminiText($prompt);
    }

    private function analyzeWithGeminiText($prompt)
    {
        $accessToken = $this->getGoogleAccessToken();

        // dd($prompt);
        $requestPayload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken
        ])->timeout(180)->retry(4, 1000)->post($this->geminiEndpoint, $requestPayload);
        
        if (!$response->successful()) {
            throw new Exception("Gemini API error: " . $response->body());
        }

        // $response = $response->json();

        if (isset($response['error'])) {
            throw new Exception("Gemini API error: " . $response['error']['message']);
        }

        // dd($response);
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return $response['candidates'][0]['content']['parts'][0]['text'];
        }

        return null;
    }


    private function analyzeWithSearchAPIText($prompt)
    {
        $searchJson = GeneralHelper::getSearchResult($prompt);
        $searchData = json_decode($searchJson, true);

        $aiOverview = null;
        $hasAio = false;

        if (isset($searchData['ai_overview'])) {
            if (!isset($searchData['ai_overview']['page_token'])) {
                return $searchData['ai_overview'];
            } else {
                $aioJson = GeneralHelper::getaioResult($searchData['ai_overview']['page_token']);
                return json_decode($aioJson, true);
            }
        }
        return null;
    }

    private function analyzeClientMentions(array $prompts, string $clientRaw): array
    {
        // Split on comma, trim whitespace, filter empty strings
        $terms = array_values(
            array_filter(
                array_map('trim', explode(',', $clientRaw)),
                fn($t) => $t !== ''
            )
        );

        $perPrompt    = [];
        $termTotals   = array_fill_keys($terms, 0);
        $mentionedCnt = 0;

        foreach ($prompts as $promptText) {
            $text = (string) $promptText;

            // ── Step 1: Send the prompt to Gemini and get AI response ──────
            // We analyse what the AI says in response to the prompt,
            // NOT the prompt text itself — this tells us whether the AI
            // mentions our client brand in its answer.
            $aiResponse = '';
            $raw = $this->analyzeWithGeminiText($text);
            $gemini_response = str_replace('\n', "\n", $raw);
            $gemini_response = $this->markdownToHtml($gemini_response);

            $aiResponse = (string) ($raw ?? '');

            // ── Step 2: Search the AI response for each client term ─────────
            $breakdown = [];
            $total     = 0;

            foreach ($terms as $term) {
                // Case-insensitive substring count across the Gemini response
                $count = substr_count(mb_strtolower($aiResponse), mb_strtolower($term));
                $breakdown[$term]    = $count;
                $termTotals[$term]  += $count;
                $total              += $count;
            }

            $isMentioned = $total > 0;
            if ($isMentioned) {
                $mentionedCnt++;
            }

            $perPrompt[] = [
                'prompt'       => $text,        // original prompt shown in modal
                'ai_response'  => $aiResponse,  // Gemini response we actually searched
                'gemini_response' => $gemini_response,
                'is_mentioned' => $isMentioned,
                'total_count'  => $total,
                'breakdown'    => $breakdown,
            ];
        }

        $totalPrompts = count($prompts);
        $percentage   = $totalPrompts > 0
            ? round(($mentionedCnt / $totalPrompts) * 100, 1)
            : 0.0;

        return [
            'per_prompt' => $perPrompt,
            'summary'    => [
                'total_prompts'   => $totalPrompts,
                'mentioned_count' => $mentionedCnt,
                'percentage'      => $percentage,
                'client_terms'    => $terms,
                'term_totals'     => $termTotals,
            ],
        ];
    }

    public function brand_neutral_prompt(Request $request)
    {

        try {
            $prompts = $request->input('prompts', []);
            $client  = $request->input('client', '');

            if (empty($prompts)) {
                return response()->json(['error' => 'No prompts provided.'], 422);
            }

            $analysis = $this->analyzeClientMentions($prompts, $client);
            return response()->json(['success' => true, 'data' => $analysis]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /visibility-tracking-prompt
     * Expects: { prompts: [...], client: "abc,def,ghi" }
     */
    public function visibility_tracking_prompt(Request $request)
    {
        try {
            $prompts = $request->input('prompts', []);
            $client  = $request->input('client', '');

            if (empty($prompts)) {
                return response()->json(['error' => 'No prompts provided.'], 422);
            }

            $analysis = $this->analyzeClientMentions($prompts, $client);
            return response()->json(['success' => true, 'data' => $analysis]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /competitor-trigger-prompt
     * Expects: { prompts: [...], client: "abc,def,ghi" }
     */
    public function competitor_trigger_prompt(Request $request)
    {
        try {
            $prompts = $request->input('prompts', []);
            $client  = $request->input('client', '');

            if (empty($prompts)) {
                return response()->json(['error' => 'No prompts provided.'], 422);
            }

            $analysis = $this->analyzeClientMentions($prompts, $client);
            return response()->json(['success' => true, 'data' => $analysis]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function analyze_single_prompt_gemini(Request $request)
    {
        try {
            $promptText        = trim($request->input('prompt', ''));
            $clientRaw         = trim($request->input('client', ''));
            $index             = (int) $request->input('index', 0);
            $section           = $request->input('section', '');
            $generatedPromptId = $request->input('generated_prompt_id');

            if ($promptText === '') {
                return response()->json(['success' => false, 'error' => 'No prompt text provided.'], 422);
            }

            // ── Parse client terms ─────────────────────────────────────────────
            $terms = array_values(
                array_filter(
                    array_map('trim', explode(',', $clientRaw)),
                    fn ($t) => $t !== ''
                )
            );

            $raw             = $this->analyzeWithGeminiText($promptText);
            $geminiResponse  = $this->markdownToHtml($raw);
            $aiResponse      = (string) ($raw ?? '');
            Log::info($aiResponse);

            $breakdown = [];
            $total     = 0;

            foreach ($terms as $term) {
                $count            = substr_count(mb_strtolower($aiResponse), mb_strtolower($term));
                $breakdown[$term] = $count;
                $total           += $count;
            }

            $isMentioned = $total > 0;

            // ── Save response to DB ────────────────────────────────────────────
            if ($generatedPromptId) {
                $record = GeneratedPrompts::find($generatedPromptId);
                // Use firstOrNew + save so we don't duplicate on re-run
                $responseRow = GeneratedPromptsResponse::where([
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'gemini',
                ])->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(prompt_json, '$.prompt_index')) = ?", [$index])->first()
                    ?? new GeneratedPromptsResponse();

                $responseRow->fill([
                    'domainmanagement_id' => $record->domainmanagement_id ?? null,
                    'client_property_id'  => $record->client_property_id  ?? null,
                    'keyword_request_id'  => $record->keyword_request_id  ?? null,
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'gemini',
                    'prompt_json'         => json_encode([
                        'prompt_index'       => $index,
                        'section'            => $section,
                        'prompt'             => $promptText,
                        'ai_response'        => $aiResponse,
                        'formatted_response' => $geminiResponse,
                        'is_mentioned'       => $isMentioned,
                        'total_count'        => $total,
                        'breakdown'          => $breakdown,
                        'client_terms'       => $terms,
                    ]),
                ])->save();
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'index'        => $index,
                    'section'      => $section,
                    'is_mentioned' => $isMentioned,
                    'client_terms' => $terms,
                    'per_prompt'   => [
                        'prompt'           => $promptText,
                        'ai_response'      => $aiResponse,
                        'gemini_response'  => $geminiResponse,
                        'is_mentioned'     => $isMentioned,
                        'total_count'      => $total,
                        'breakdown'        => $breakdown,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function analyze_single_prompt_chatgpt(Request $request)
    {
        try {
            $promptText        = trim($request->input('prompt', ''));
            $clientRaw         = trim($request->input('client', ''));
            $index             = (int) $request->input('index', 0);
            $section           = $request->input('section', '');
            $generatedPromptId = $request->input('generated_prompt_id');

            if ($promptText === '') {
                return response()->json(['success' => false, 'error' => 'No prompt text provided.'], 422);
            }

            // ── Parse client terms ─────────────────────────────────────────────
            $terms = array_values(
                array_filter(
                    array_map('trim', explode(',', $clientRaw)),
                    fn ($t) => $t !== ''
                )
            );

            // ── Call OpenAI (ChatGPT) ──────────────────────────────────────────
            $raw             = $this->analyzeWithOpenAIText($promptText);
            $chatgptResponse = $this->markdownToHtml((string) ($raw ?? ''));
            $aiResponse      = (string) ($raw ?? '');

            Log::info('[ChatGPT] prompt=' . $promptText);

            // ── Count client mentions ──────────────────────────────────────────
            $breakdown = [];
            $total     = 0;

            foreach ($terms as $term) {
                $count            = substr_count(mb_strtolower($aiResponse), mb_strtolower($term));
                $breakdown[$term] = $count;
                $total           += $count;
            }

            $isMentioned = $total > 0;

            // ── Save response to DB ────────────────────────────────────────────
            if ($generatedPromptId) {
                $record = GeneratedPrompts::find($generatedPromptId);
                $responseRow = GeneratedPromptsResponse::where([
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'chatgpt',
                ])->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(prompt_json, '$.prompt_index')) = ?", [$index])->first()
                    ?? new GeneratedPromptsResponse();

                $responseRow->fill([
                    'domainmanagement_id' => $record->domainmanagement_id ?? null,
                    'client_property_id'  => $record->client_property_id  ?? null,
                    'keyword_request_id'  => $record->keyword_request_id  ?? null,
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'chatgpt',
                    'prompt_json'         => json_encode([
                        'prompt_index'       => $index,
                        'section'            => $section,
                        'prompt'             => $promptText,
                        'ai_response'        => $aiResponse,
                        'formatted_response' => $chatgptResponse,
                        'is_mentioned'       => $isMentioned,
                        'total_count'        => $total,
                        'breakdown'          => $breakdown,
                        'client_terms'       => $terms,
                    ]),
                ])->save();
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'index'        => $index,
                    'section'      => $section,
                    'is_mentioned' => $isMentioned,
                    'client_terms' => $terms,
                    'per_prompt'   => [
                        'prompt'           => $promptText,
                        'ai_response'      => $aiResponse,
                        'chatgpt_response' => $chatgptResponse,
                        'is_mentioned'     => $isMentioned,
                        'total_count'      => $total,
                        'breakdown'        => $breakdown,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function analyze_single_prompt_search_api(Request $request)
    {
        try {
            $promptText        = trim($request->input('prompt', ''));
            $clientRaw         = trim($request->input('client', ''));
            $index             = (int) $request->input('index', 0);
            $section           = $request->input('section', '');
            $generatedPromptId = $request->input('generated_prompt_id');

            if ($promptText === '') {
                return response()->json(['success' => false, 'error' => 'No prompt text provided.'], 422);
            }

            // ── Parse client terms ─────────────────────────────────────────
            $terms = array_values(
                array_filter(
                    array_map('trim', explode(',', $clientRaw)),
                    fn ($t) => $t !== ''
                )
            );

            // ── Call Search API ────────────────────────────────────────────
            $rawResult     = $this->analyzeWithSearchAPIText($promptText);
            $aiResponse    = '';
            $formattedHtml = '<em class="text-muted">No AI overview returned by Search API.</em>';

            if (!empty($rawResult)) {
                $aiResponse    = $this->extractTextFromArray($rawResult);
                $formattedHtml = $this->searchApiResultToHtml($rawResult);
            }

            // ── Count client mentions ──────────────────────────────────────
            $breakdown = [];
            $total     = 0;

            foreach ($terms as $term) {
                $count            = substr_count(mb_strtolower($aiResponse), mb_strtolower($term));
                $breakdown[$term] = $count;
                $total           += $count;
            }

            $isMentioned = $total > 0;

            Log::info('[SearchAPI] prompt=' . $promptText . ' | mentioned=' . ($isMentioned ? 'yes' : 'no'));

            // ── Save response to DB ────────────────────────────────────────
            if ($generatedPromptId) {
                $record = GeneratedPrompts::find($generatedPromptId);
                $responseRow = GeneratedPromptsResponse::where([
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'searchapi',
                ])->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(prompt_json, '$.prompt_index')) = ?", [$index])->first()
                    ?? new GeneratedPromptsResponse();

                $responseRow->fill([
                    'domainmanagement_id' => $record->domainmanagement_id ?? null,
                    'client_property_id'  => $record->client_property_id  ?? null,
                    'keyword_request_id'  => $record->keyword_request_id  ?? null,
                    'generated_prompt_id' => $generatedPromptId,
                    'source'              => 'searchapi',
                    'prompt_json'         => json_encode([
                        'prompt_index'       => $index,
                        'section'            => $section,
                        'prompt'             => $promptText,
                        'ai_response'        => $aiResponse,
                        'formatted_response' => $formattedHtml,
                        'is_mentioned'       => $isMentioned,
                        'total_count'        => $total,
                        'breakdown'          => $breakdown,
                        'client_terms'       => $terms,
                    ]),
                ])->save();
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'index'        => $index,
                    'section'      => $section,
                    'is_mentioned' => $isMentioned,
                    'client_terms' => $terms,
                    'per_prompt'   => [
                        'prompt'          => $promptText,
                        'ai_response'     => $aiResponse,
                        'gemini_response' => $formattedHtml,
                        'is_mentioned'    => $isMentioned,
                        'total_count'     => $total,
                        'breakdown'       => $breakdown,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recursively extract all string leaf values from a nested array.
     * Used to turn the Search API AI overview structure into flat text
     * for client-mention checking.
     */
    private function extractTextFromArray($data): string
    {
        if (is_string($data)) {
            return $data;
        }
        if (is_array($data)) {
            $parts = [];
            foreach ($data as $value) {
                $extracted = $this->extractTextFromArray($value);
                if ($extracted !== '') {
                    $parts[] = $extracted;
                }
            }
            return implode(' ', $parts);
        }
        return '';
    }

    /**
     * Convert the Search API AI overview array into readable HTML.
     * Handles common structures: text_blocks, references, bullets.
     */
    private function searchApiResultToHtml($data): string
    {
        if (is_string($data)) {
            return '<p>' . nl2br(htmlspecialchars($data)) . '</p>';
        }

        if (!is_array($data)) {
            return '';
        }

        $html = '';

        // text_blocks array (common in Google AI overview responses)
        if (isset($data['text_blocks']) && is_array($data['text_blocks'])) {
            foreach ($data['text_blocks'] as $block) {
                $text = $block['snippet'] ?? $block['text'] ?? '';
                if ($text !== '') {
                    $html .= '<p>' . nl2br(htmlspecialchars($text)) . '</p>';
                }
            }
        }

        // bullets
        if (isset($data['bullets']) && is_array($data['bullets'])) {
            $html .= '<ul>';
            foreach ($data['bullets'] as $bullet) {
                $text = is_string($bullet) ? $bullet : ($bullet['text'] ?? $bullet['snippet'] ?? '');
                if ($text !== '') {
                    $html .= '<li>' . htmlspecialchars($text) . '</li>';
                }
            }
            $html .= '</ul>';
        }

        // references / sources list
        if (isset($data['references']) && is_array($data['references'])) {
            $html .= '<hr><p class="fw-bold mb-1" style="font-size:.8rem;color:#64748b;">SOURCES</p><ul class="list-unstyled mb-0">';
            foreach ($data['references'] as $ref) {
                $title = htmlspecialchars($ref['title'] ?? $ref['source'] ?? 'Source');
                $url   = $ref['url'] ?? $ref['link'] ?? '#';
                $html .= '<li style="font-size:.8rem;margin-bottom:4px;">'
                       . '<a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener noreferrer">'
                       . $title . '</a></li>';
            }
            $html .= '</ul>';
        }

        // Fallback: no known structure — pretty-print everything
        if ($html === '') {
            $html = '<pre style="font-size:.78rem;white-space:pre-wrap;">'
                  . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                  . '</pre>';
        }

        return $html;
    }

    private function markdownToHtml(string $markdown): string
    {
        // ── Inline links: [text](url) → <a> ───────────────────────────────
        // Must run BEFORE bold/italic so URLs with * are not mangled
        $html = preg_replace(
            '/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/',
            '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
            $markdown
        );

        // ── Grounding / citation references: [[N]](url) or [N] bare ─────
        // Gemini sometimes emits [[1]](url) style references — turn them
        // into superscript links. Must come AFTER the inline-link pass.
        $html = preg_replace(
            '/\[\[(\d+)\]\]\((https?:\/\/[^\)]+)\)/',
            '<sup><a href="$2" target="_blank" rel="noopener noreferrer" title="Reference $1">[$1]</a></sup>',
            $html
        );

        // Headers
        $html = preg_replace('/^### (.+)$/m', '<h5 class="mt-3 mb-1">$1</h5>', $html);
        $html = preg_replace('/^## (.+)$/m',  '<h4 class="mt-3 mb-1">$1</h4>', $html);
        $html = preg_replace('/^# (.+)$/m',   '<h3 class="mt-3 mb-1">$1</h3>', $html);

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Horizontal rule
        $html = preg_replace('/^---$/m', '<hr>', $html);

        // Numbered lists — wrap consecutive items in <ol>
        $html = preg_replace_callback(
            '/((?:^\d+\.\s+.+\n?)+)/m',
            function ($matches) {
                $items = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', trim($matches[0]));
                return '<ol>' . $items . '</ol>';
            },
            $html
        );

        // Unordered lists
        $html = preg_replace_callback(
            '/((?:^[-*]\s+.+\n?)+)/m',
            function ($matches) {
                $items = preg_replace('/^[-*]\s+(.+)$/m', '<li>$1</li>', trim($matches[0]));
                return '<ul>' . $items . '</ul>';
            },
            $html
        );

        // Paragraphs — wrap non-tag lines in <p>
        $lines = explode("\n", $html);
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!preg_match('/^<(h[1-6]|ul|ol|li|hr|p|strong|em|sup|a)/', $line)) {
                $line = '<p>' . $line . '</p>';
            }
            $result[] = $line;
        }

        return implode("\n", $result);
    }
    public function analyzeWithOpenAIText($prompt)
    {
        $requestPayload = [
            'model' => 'gpt-4o-search-preview',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->openaiApiKey
        ])->timeout(180)->retry(4, 1000)->post($this->chatEndpoint, $requestPayload);

        if (!$response->successful()) {
            throw new Exception("OpenAI API error: " . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new Exception("OpenAI API error: " . $responseData['error']['message']);
        }

        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        return null;
    }
    public function openairesponse()
    {
        return $this->analyzeWithOpenAIText("Who is the president of USA");
    }
}