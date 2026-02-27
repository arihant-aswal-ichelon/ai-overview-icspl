<?php

namespace App\Services;

use Google\Client;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Exception;
use Google\Ads\GoogleAds\Util\V21\ResourceNames;
use Google\Ads\GoogleAds\V21\Services\GenerateKeywordIdeasRequest;
use Google\Ads\GoogleAds\V21\Services\KeywordSeed;
use Google\Ads\GoogleAds\V21\Services\UrlSeed;
use Google\Service\Storage;
use GPBMetadata\Google\Ads\GoogleAds\V21\Enums\KeywordPlanNetwork;
use Illuminate\Support\Facades\Log;

class GoogleSearchConsoleService
{
    protected $client;
    protected $service;
    protected $property;
    protected $customerId;
    protected $managerId;

    public function __construct($customerId, $managerId)
    {
        $this->client = new Client();
        $this->customerId = $customerId;
        $this->managerId = $managerId;
        // $this->client->setAuthConfig('C:\xampp\htdocs\cbo\storage\app\google-analytics.json');
        $this->client->setAuthConfig(storage_path('app/google-analytics.json'));
        $this->client->addScope('https://www.googleapis.com/auth/webmasters');

        $this->service = new SearchConsole($this->client);
        // $this->property = $propertyUri;
    }

    public function generateKeywordIdeas($keyword, $locationIds = ['2840'], $languageId = '1000')
    {
        try {
            $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
            
            // Prepare keywords array (single keyword)
            $keywords = [$keyword];
            
            // Create geo target constants
            $geoTargetConstants = array_map(function ($locationId) {
                return ResourceNames::forGeoTargetConstant($locationId);
            }, $locationIds);
            
            // Prepare the request
            $request = new GenerateKeywordIdeasRequest([
                'customer_id' => $this->customerId,
                'language' => ResourceNames::forLanguageConstant($languageId),
                'geo_target_constants' => $geoTargetConstants,
                'keyword_plan_network' => KeywordPlanNetwork::GOOGLE_SEARCH,
                'keyword_seed' => new KeywordSeed(['keywords' => $keywords]),
                // Optional: Include historical metrics
                'include_adult_keywords' => false,
                // Optional: Add keyword annotations if needed
                'keyword_annotation' => [],
                // Optional: Set historical metrics options
                'historical_metrics_options' => [
                    'year_month_range' => [
                        'start' => [
                            'year' => date('Y') - 1, // Last year
                            'month' => date('n')
                        ],
                        'end' => [
                            'year' => date('Y'),
                            'month' => date('n')
                        ]
                    ]
                ]
            ]);
            
            // Generate keyword ideas
            $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas($request);
            
            $results = [];
            foreach ($response->iterateAllElements() as $result) {
                $metrics = $result->getKeywordIdeaMetrics();
                
                $results[] = [
                    'keyword' => $result->getText(),
                    'avg_monthly_searches' => $metrics ? $metrics->getAvgMonthlySearches() : 0,
                    'competition' => $metrics ? $this->getCompetitionLevel($metrics->getCompetition()) : 'UNKNOWN',
                    'competition_value' => $metrics ? $metrics->getCompetition() : 0,
                    'low_top_of_page_bid_micros' => $metrics ? $this->microsToCurrency($metrics->getLowTopOfPageBidMicros()) : 0,
                    'high_top_of_page_bid_micros' => $metrics ? $this->microsToCurrency($metrics->getHighTopOfPageBidMicros()) : 0,
                ];
            }
            
            return $results;
            
        } catch (GoogleAdsException $googleAdsException) {
            echo "Request failed. Google Ads errors:\n";
            foreach ($googleAdsException->getGoogleAdsFailure()->getErrors() as $error) {
                printf(
                    "Error: %s: %s\n",
                    $error->getErrorCode()->getErrorCode(),
                    $error->getMessage()
                );
            }
            return [];
        } catch (ApiException $apiException) {
            echo "API Exception: " . $apiException->getMessage() . "\n";
            return [];
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function searchKeywordsByUrl(string $url)
    {
        // Use UrlSeed for domain-based keyword ideas[citation:2]
        $request = new GenerateKeywordIdeasRequest([
            'customer_id' => $this->customerId,
            'url_seed' => new UrlSeed(['url' => $url]),
            'language' => ResourceNames::forLanguageConstant(1000), // English
            'keyword_plan_network' => KeywordPlanNetwork::GOOGLE_SEARCH,
            // Add geo_target_constants for location targeting if needed
        ]);
        dd($url);
        
        return $this->executeRequest($request);
    }

    public function searchKeywords(string $keyword)
    {
        // Use KeywordSeed for keyword-based ideas[citation:2]
        dd("keywod: $keyword");
        $request = new GenerateKeywordIdeasRequest([
            'customer_id' => $this->customerId,
            'keyword_seed' => new KeywordSeed(['keywords' => [$keyword]]),
            'language' => ResourceNames::forLanguageConstant(1000),
            'keyword_plan_network' => KeywordPlanNetwork::GOOGLE_SEARCH,
        ]);
        dd($request, $keyword);
        
        return $this->executeRequest($request);
    }
    private function executeRequest(GenerateKeywordIdeasRequest $request)
    {
        try {
            $keywordPlanIdeaServiceClient = $this->googleAdsClient->getKeywordPlanIdeaServiceClient();
            $response = $keywordPlanIdeaServiceClient->generateKeywordIdeas($request);
            
            $results = [];
            foreach ($response->iterateAllElements() as $result) {
                $metrics = $result->getKeywordIdeaMetrics();
                $results[] = [
                    'keyword' => $result->getText(),
                    'avg_monthly_searches' => $metrics ? $metrics->getAvgMonthlySearches() : 0,
                    'competition' => $metrics ? $this->mapCompetition($metrics->getCompetition()) : 'UNKNOWN',
                    'low_top_of_page_bid_micros' => $metrics ? $this->microsToCurrency($metrics->getLowTopOfPageBidMicros()) : 0,
                    'high_top_of_page_bid_micros' => $metrics ? $this->microsToCurrency($metrics->getHighTopOfPageBidMicros()) : 0,
                ];
            }
            return $results;
            
        } catch (\Google\Ads\GoogleAds\V16\Errors\GoogleAdsException $e) {
            // Log specific API errors
            foreach ($e->getGoogleAdsFailure()->getErrors() as $error) {
                \Log::error('Google Ads API Error: ' . $error->getMessage());
            }
            return [];
        } catch (\Exception $e) {
            \Log::error('General Error in KeywordPlannerService: ' . $e->getMessage());
            return [];
        }
    }


    public function getSearchAnalytics($propertyUrl, $limit, $startDate = '90daysAgo', $endDate = 'today')
    {
        try {
            // \Log::info("GSC: Getting analytics for " . $propertyUrl);
            // dd($startDate, $endDate);
            // Convert relative dates to actual dates
            if ($startDate === '90daysAgo') {
                $startDate = date('Y-m-d', strtotime('-90 days'));
            }
            if ($endDate === 'today') {
                $endDate = date('Y-m-d');
            }
            
            $request = new SearchAnalyticsQueryRequest([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['query'],
                'rowLimit' => $limit,
                'dataState' => 'all',
            ]);

            
            $response = $this->service->searchanalytics->query(
                $propertyUrl,
                $request
            );
            // dd($this->processQueryResponse($response));

            return $this->processQueryResponse($response);
        } catch (Exception $e) {
            throw new Exception('Error fetching GSC data: ' . $e->getMessage());
        }
    }

    private function processQueryResponse($response)
    {
        $keywords = [];
        
        if (!empty($response->rows)) {
            foreach ($response->rows as $row) {
                $clicks = $row->getClicks() ?? 0;
                
                if ($clicks <= 0) {
                    continue;
                }
                $keywords[] = [
                    'query' => $row->keys[0] ?? '', // The keyword
                    'clicks' => $row->clicks ?? 0,
                    'impressions' => $row->impressions ?? 0,
                    'ctr' => $row->ctr ? round($row->ctr * 100, 2) : 0, // Convert to percentage
                    'position' => $row->position ? round($row->position, 1) : 0,
                    'date' => date('Y-m-d') // Add current date for filtering
                ];
            }
        }
        // dd($response->rows, $keywords);


        return $keywords;
    }


    /**
     * Get indexed pages with basic information
     */
    public function getIndexedPages($propertyurl, $startDate, $endDate)
    {
        try {
            $request = new SearchAnalyticsQueryRequest([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dimensions' => ['page'],
                'rowLimit' => 1000,
            ]);

            $response = $this->service->searchanalytics->query(
                $propertyurl,
                $request
            );

            return $this->processResponse($response);
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }

    private function processResponse($response)
    {
        $pages = [];

        if (!empty($response->rows)) {
            foreach ($response->rows as $row) {
                $clicks = $row->getClicks() ?? 0;
                
                // Skip keywords with 0 clicks
                if ($clicks <= 0) {
                    continue;
                }
                $pages[] = [
                    'url' => $row->keys[0],
                    'clicks' => $row->clicks,
                    'impressions' => $row->impressions,
                    'ctr' => $row->ctr,
                    'position' => $row->position,
                ];
            }
        }

        return $pages;
    }
    public function getKeywordAnalytics(
        $propertyUrl,
        $keyword,
        $limit = 1,
        $startDate = '90daysAgo',
        $endDate = 'today'
    ) {
        try {
            // Convert relative dates
            if ($startDate === '90daysAgo') {
                $startDate = date('Y-m-d', strtotime('-365 days'));
            }
            if ($endDate === 'today') {
                $endDate = date('Y-m-d');
            }

            $request = new SearchAnalyticsQueryRequest([
                'startDate' => $startDate,
                'endDate'   => $endDate,
                'dimensions'=> ['query'],
                'rowLimit'  => $limit,
                'dimensionFilterGroups' => [
                    [
                        'filters' => [
                            [
                                'dimension' => 'query',
                                'operator'  => 'equals',
                                'expression'=> $keyword,
                            ]
                        ]
                    ]
                ],
                'dataState' => 'all',
            ]);
            dd($request);


            $response = $this->service->searchanalytics->query(
                $propertyUrl,
                $request
            );
            if (empty($response->rows)) {
                return [
                    'keyword'      => $keyword,
                    'clicks'       => 0,
                    'impressions'  => 0,
                    'ctr'          => 0,
                    'position'     => 0,
                ];
            }

            $row = $response->rows[0];

            return [
                'keyword'     => $row->keys[0] ?? $keyword,
                'clicks'      => $row->clicks ?? 0,
                'impressions' => $row->impressions ?? 0,
                'ctr'         => isset($row->ctr) ? round($row->ctr * 100, 2) : 0,
                'position'    => isset($row->position) ? round($row->position, 1) : 0,
            ];
            // return $this->processKeywordResponse($response, $keyword);

        } catch (Exception $e) {
            throw new Exception('Error fetching keyword analytics: ' . $e->getMessage());
        }
    }
    public function getKeywordsByClicks($propertyUrl, $limit = 100, $startDate = null, $endDate = null)
    {
        try {
            // Set default dates if not provided (last 30 days)
            if (!$startDate) {
                $startDate = date('Y-m-d', strtotime('-90 days'));
            }
            if (!$endDate) {
                $endDate = date('Y-m-d');
            }

            // Validate dates
            if (strtotime($startDate) > strtotime($endDate)) {
                throw new Exception("Start date cannot be after end date");
            }

            // Create the query request
            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            
            // Set dimensions to get query (keyword) data
            $request->setDimensions(['query']);
            
            // Set row limit
            $request->setRowLimit($limit);
            
            // Add metrics we want to retrieve
            $request->setDimensions(['query']);
            
            // Optional: Filter by page if you want specific page keywords
            // $request->setDimensionFilterGroups([
            //     'filters' => [
            //         [
            //             'dimension' => 'page',
            //             'operator' => 'equals',
            //             'expression' => $propertyUrl
            //         ]
            //     ]
            // ]);

            // Execute the query
            $response = $this->service->searchanalytics->query($propertyUrl, $request);
            
            // Process the response
            $keywords = [];
            
            if ($response->getRows()) {
                foreach ($response->getRows() as $row) {
                    $clicks = $row->getClicks() ?? 0;
                
                    if ($clicks <= 0) {
                        continue;
                    }
                    $keywords[] = [
                        'keyword' => $row->getKeys()[0] ?? '',
                        'clicks' => $row->getClicks() ?? 0,
                        'impressions' => $row->getImpressions() ?? 0,
                        'ctr' => $row->getCtr() ?? 0,
                        'position' => $row->getPosition() ?? 0
                    ];
                }
                
                // Sort by clicks in descending order
                usort($keywords, function($a, $b) {
                    return $b['clicks'] <=> $a['clicks'];
                });
                
                // Apply limit after sorting
                $keywords = array_slice($keywords, 0, $limit);
            }
            
            return $keywords;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
}