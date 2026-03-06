<?php

use App\Http\Controllers\AiSimilarityAnalysisController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DomainManagementController;
use App\Http\Controllers\GenerateAIOPromptAnalysisController;
use App\Http\Controllers\KeywordAnalysisController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Auth::routes();

Route::get('/',  [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home',  [HomeController::class, 'index'])->name('home');

    //Google Auth
    Route::get('/gauth',  [HomeController::class, 'gauth'])->name('gauth');
    Route::get('/gauth/{id}',  [HomeController::class, 'gauth'])->name('gauth-id');
    Route::get('/profile',  [HomeController::class, 'profile'])->name('profile');
    Route::get('/edit',  [HomeController::class, 'profileEdit'])->name('profileEdit');
    Route::post('/edit',  [HomeController::class, 'profileUpdate'])->name('profileUpdate');

    //Clients
    Route::get('/clients',  [DomainManagementController::class, 'index'])->name('clients');
    Route::get('/add-client',  [DomainManagementController::class, 'create'])->name('add-client');
    Route::post('/add-client',  [DomainManagementController::class, 'store'])->name('store-client');
    Route::get('/view-client/{id}',  [DomainManagementController::class, 'show'])->name('view-client');
    Route::get('/edit-client/{id}',  [DomainManagementController::class, 'edit'])->name('edit-client');
    Route::post('/edit-client',  [DomainManagementController::class, 'update'])->name('update-client');

    //Clients Properties
    Route::get('/clients-properties/{id}',  [DomainManagementController::class, 'showproperties'])->name('clients-properties');
    Route::get('/add-client-properties/{id}',  [DomainManagementController::class, 'createproperties'])->name('add-client-properties');
    Route::post('/add-client-properties',  [DomainManagementController::class, 'storeproperties'])->name('store-client-properties');
    Route::get('/edit-client-properties/{id}',  [DomainManagementController::class, 'editproperties'])->name('edit-client-properties');
    Route::post('/edit-client-properties',  [DomainManagementController::class, 'updateproperties'])->name('update-client-properties');

    Route::get('/keyword-request/{id}',  [KeywordAnalysisController::class, 'index'])->name('keyword-request');
    Route::get('/add-keyword-request/{id}',  [KeywordAnalysisController::class, 'create'])->name('add-keyword-request');
    Route::post('/add-keyword-request',  [KeywordAnalysisController::class, 'store'])->name('store-keyword-request');
    Route::get('/edit-keyword-request/{id}',  [KeywordAnalysisController::class, 'edit'])->name('edit-keyword-request');
    Route::post('/edit-keyword-request',  [KeywordAnalysisController::class, 'update'])->name('update-keyword-request');

    Route::get('/keyword-cluster-analysis/{id}',  [KeywordAnalysisController::class, 'keywordClusterAnalysis'])->name('keyword-cluster-analysis');
    // Route::get('/check-keyword-status', [KeywordAnalysisController::class, 'checkKeywordStatus'])->name('check.keyword.status');

    Route::post('/keyword-store',  [KeywordAnalysisController::class, 'keywordStore'])->name('keyword-store');
    Route::get('/keyword-analysis/{dmid}/{cpid}/{id}',  [KeywordAnalysisController::class, 'keywordAnalysis'])->name('keyword-analysis');
    // Add these routes inside the auth middleware group
    Route::post('/auto-keyword-fetch', [KeywordAnalysisController::class, 'autoKeywordFetch'])->name('auto-keyword-fetch');
    // Route::post('/process-all-keywords', [KeywordAnalysisController::class, 'processAllKeywordsAutomatically'])->name('process.all.keywords');
    // Route::get('/check-processing-status', [KeywordAnalysisController::class, 'checkProcessingStatus'])->name('check.processing.status');

    Route::get('/fetch',  [KeywordAnalysisController::class, 'fetchGscUrls'])->name('fetchGscUrls');
    // Route::post('/get-aio-result', [KeywordAnalysisController::class, 'getAioResult'])->name('get-aio-result');
    Route::post('/get-aio-result', [KeywordAnalysisController::class, 'getAioResult'])->name('keyword-analysis.get-aio-result');
    Route::get('/extracted-aio-result/{id}', [KeywordAnalysisController::class, 'extractedAioResult'])->name('extracted-aio-result');

    // Route::post('/store-keyword-data', [KeywordAnalysisController::class, 'storeKeywordData'])->name('store.keyword.data');
    Route::post('/store-keyword-planner', [KeywordAnalysisController::class, 'storeKeywordPlanner'])->name('store.keyword.planner');
    Route::get('/domain', [KeywordAnalysisController::class, 'enrichWithGscData'])->name('enrichWithGscData');
    Route::get('/search',  [KeywordAnalysisController::class, 'searchKeyword'])->name('searchKeyword');
    Route::post('/get-aio-result', [KeywordAnalysisController::class, 'getAioResult'])->name('get.aio.result');

    Route::post('/check-child-keywords/{parentKeywordId}', [KeywordAnalysisController::class, 'checkChildKeywords'])->name('check-child-keywords');
    Route::post('/sync-aio-data', [KeywordAnalysisController::class, 'sync_now'])->name('sync-aio-data');
    Route::post('/sync-ai-overview', [KeywordAnalysisController::class, 'syncAiOverview'])->name('sync-ai-overview');
    Route::post('/check-aistatus', [KeywordAnalysisController::class, 'checkAIStatus'])->name('check-aistatus');

    Route::get('/aio-cluster-analysis/{id}',  [KeywordAnalysisController::class, 'aioClusterAnalysis'])->name('aio-cluster-analysis');
    Route::post('/aio-keyword-fetch', [KeywordAnalysisController::class, 'aioKeywordFetch'])->name('aio-keyword-fetch');
    Route::post('/keywordmediansave', [KeywordAnalysisController::class, 'keywordmediansave'])->name('keywordmediansave');
    Route::post('/autokeywordmediansave', [KeywordAnalysisController::class, 'autokeywordmediansave'])->name('autokeywordmediansave');
    Route::post('/keyword-store-more', [KeywordAnalysisController::class, 'keywordStoreMore'])->name('keyword-store-more');
    Route::get('/hello',  [KeywordAnalysisController::class, 'hello'])->name('hello');
    Route::get('/median-results/{id}',  [KeywordAnalysisController::class, 'medianResults'])->name('median-results');
    Route::post('/median-display',  [KeywordAnalysisController::class, 'medianDisplay'])->name('median-display');
    // Add these routes

    Route::post('/process-all-keywords', [KeywordAnalysisController::class, 'processAllKeywordsAutomatically'])->name('process.all.keywords');
    Route::get('/check-keyword-status', [KeywordAnalysisController::class, 'checkKeywordStatus'])->name('check.keyword.status');

    Route::post('/fetch-keyword-planner-keywords', [KeywordAnalysisController::class, 'fetchKeywordPlannerKeywordsdata'])
        ->name('fetch.keyword.planner.keywords');

    Route::post('/process-keyword-planner-keywords', [KeywordAnalysisController::class, 'processKeywordPlannerKeywords'])
        ->name('process.keyword.planner.keywords');

    Route::get('/check-keyword-planner-status', [KeywordAnalysisController::class, 'checkKeywordPlannerStatus'])
        ->name('check.keyword.planner.status');

    // Add these routes
    Route::post('/process-extracted-keywords', [KeywordAnalysisController::class, 'processExtractedKeywords'])->name('process.extracted.keywords');
    Route::get('/check-extracted-keywords-status', [KeywordAnalysisController::class, 'checkExtractedKeywordsStatus'])->name('check.extracted.keywords.status');



    // AI Similarity Analysis
    Route::get('/ai-similarity-analysis/{id}', [AiSimilarityAnalysisController::class, 'index'])->name('ai-similarity-analysis-index');
    Route::get('/comparison-analysis/{client_id}/{keyword_id}', [AiSimilarityAnalysisController::class, 'comparsionAnalysis'])->name('comparsion-analysis');

    // Generate AIO prompt analysis
    Route::get('/generate-aio-prompt/{dmid}/{cpid}/{id}',  [GenerateAIOPromptAnalysisController::class, 'index'])->name('generate-aio-prompt');
    Route::post('/generate-prompt', [GenerateAIOPromptAnalysisController::class, 'generate_prompt'])->name('generate.prompt');
    Route::post('/update-prompt', [GenerateAIOPromptAnalysisController::class, 'update_prompt'])->name('update.prompt');

    // Display AIO Prompt from Database
    Route::get('/openairesponse',  [GenerateAIOPromptAnalysisController::class, 'openairesponse'])->name('openairesponse');
    Route::get('/display-aio-prompt/{dmid}/{cpid}',  [GenerateAIOPromptAnalysisController::class, 'display_aio_prompt'])->name('display.aio.prompt');
    Route::get('/display-specific-aio-prompt/{id}',  [GenerateAIOPromptAnalysisController::class, 'display_specific_aio_prompt'])->name('display.specific.aio.prompt');
    Route::post('/brand-neutral-prompt',  [GenerateAIOPromptAnalysisController::class, 'brand_neutral_prompt'])->name('brand.neutral.prompt');
    Route::post('/visibility-tracking-prompt',  [GenerateAIOPromptAnalysisController::class, 'visibility_tracking_prompt'])->name('visibility.tracking.prompt');
    Route::post('/competitor-trigger-prompt',  [GenerateAIOPromptAnalysisController::class, 'competitor_trigger_prompt'])->name('competitor.trigger.prompt');
    Route::post('/analyze-single-prompt-gemini', [GenerateAIOPromptAnalysisController::class, 'analyze_single_prompt_gemini'])->name('analyze.single.prompt.gemini');
    Route::post('/analyze-single-prompt-search-api', [GenerateAIOPromptAnalysisController::class, 'analyze_single_prompt_search_api'])->name('analyze.single.prompt.search.api');
    Route::post('/analyze-single-prompt-chatgpt', [GenerateAIOPromptAnalysisController::class, 'analyze_single_prompt_chatgpt'])->name('analyze.single.prompt.chatgpt');

});
Route::get('/AutoSyncAIOforclient', function () {Artisan::call('AutoSyncAIOforclient:send');return "Cron executed successfully";});
Route::get('/queue',  [KeywordAnalysisController::class, 'queue'])->name('queue');
Route::get('/check',  [KeywordAnalysisController::class, 'checkFunctions'])->name('check');
Route::get('/printall',  [KeywordAnalysisController::class, 'printAllTables'])->name('printall');

Route::get('/fetchGscData',  [KeywordAnalysisController::class, 'fetchGscData'])->name('fetchGscData');
Route::post('/sync-gsc-aio', [KeywordAnalysisController::class, 'syncGscAio'])->name('sync.gsc.aio');
Route::post('/check-processing-status', [KeywordAnalysisController::class, 'checkProcessingStatus'])->name('gsc.aio.check-status');
Route::post('/get-cached-results', [KeywordAnalysisController::class, 'getCachedResults'])->name('gsc.aio.get-cached');
