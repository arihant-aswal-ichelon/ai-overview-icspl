<?php

namespace App\Http\Controllers;

use App\Models\AiOverview;
use App\Models\KeywordPlanner;
use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use OpenAI;

class AiSimilarityAnalysisController extends Controller
{
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
    public function index($id)
    {
        $ai_data = KeywordPlanner::where('client_property_id', $id)
            ->withCount('aiOverview')
            ->whereHas('aiOverview')
            ->get();
        // dd($ai_data->toArray());
        return view('ai-similarity-analysis.index', compact('ai_data'));
    }

    public function comparsionAnalysis($client_id, $keyword_id){
        $keyword = KeywordPlanner::where('id', $keyword_id)->where('client_property_id', $client_id)->first();
        $ai_overview = AiOverview::where('keyword_planner_id', $keyword_id)->where('client_property_id', $client_id)->get();
        dd($keyword->toArray(), $ai_overview->toArray());
        return view('ai-similarity-analysis.comparsion-analysis', compact('keyword', 'ai_overview'));
        
    }

    private function transcribeWithGemini($audioFilePath)
    {
        if (!file_exists($audioFilePath)) {
            throw new Exception("Audio file not found: " . $audioFilePath);
        }

        // Get access token using service account
        $accessToken = $this->getGoogleAccessToken();

        // Prepare audio data
        $audioData = file_get_contents($audioFilePath);
        $base64Audio = base64_encode($audioData);
        $mimeType = mime_content_type($audioFilePath);

        // Construct request payload
        $requestPayload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => 'Please transcribe this audio file exactly as it is spoken. Also, translate it to English if it is in another language. and return the english transcription only. Do not include any other text.'],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Audio
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Make API call
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken
        ])->post($this->geminiEndpoint, $requestPayload);

        if (!$response->successful()) {
            throw new Exception("Gemini API error: " . $response->body());
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new Exception("Gemini API error: " . $responseData['error']['message']);
        }

        if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            return $responseData['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new Exception("Could not extract transcription from Gemini response");
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
