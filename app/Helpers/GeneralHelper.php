<?php
namespace App\Helpers;

use App\Models\AiOverview;
use App\Models\Client_propertiesModel;
use Auth;

class GeneralHelper{

    public static function check_gtoken_status($request, $access_token, $client_id) {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token='.$access_token,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            $error = isset($response->error)?$response->error:"";

            if(!empty($error) && $error == "invalid_token"){
                return true;
            }else{
                return false;
            }
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$client_id);
        }
    }

    public static function generate_gtoken($request, $refresh_token, $lms_client_id) {
        try {
            $client_secret_json = @file_get_contents(base_path().'/client_secrets.json');
            $client_secret = json_decode($client_secret_json);
            $client_secret = $client_secret->web;
            $client_id = $client_secret->client_id;
            $client_secret_token = $client_secret->client_secret;

            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret_token,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token'),
            ));

            $response = curl_exec($curl);
            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);
            }
            curl_close($curl);
            if (isset($error_msg)) {
                $request->session()->flash("message", $error_msg);
                return redirect('/view-client/'.$lms_client_id);
            }

            $response = json_decode($response);
            $access_token = $response->access_token;
            
            return $access_token;
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
    }

    public static function getSearchResult($keyword, $engine = 'google'){
        try{ 
            $url = "https://www.searchapi.io/api/v1/search";
            $params = array(
                "engine" => $engine,
                "q" => $keyword,
                "api_key" => env('AIO_TOKEN'),
                "location" => 'India',
                "gl" => 'in',
            );
            $queryString = http_build_query($params);

            $curl = curl_init();
                curl_setopt_array($curl, [
                CURLOPT_URL => $url . '?' . $queryString,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "accept: application/json"
                ]
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);
            return $response;

        } catch (\Throwable $ex) {
            return $ex;
        }
    }
    

    public static function getaioResult($page_token, $engine = 'google_ai_overview'){
        try{

            $url = "https://www.searchapi.io/api/v1/search";
            $params = array(
                "engine" => $engine,
                "page_token" => $page_token,
                "api_key" => env('AIO_TOKEN'),
                "location" => 'India',
                "gl" => 'in',
            );
            $queryString = http_build_query($params);

            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL => $url . '?' . $queryString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "accept: application/json"
            ]
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);
            return $response;

        } catch (\Throwable $ex) {
            return $ex;
        }
    }
    public static function generate_keyword_ideas($keyword_seeds, $customer_id){
        try{
            $url = "https://googleads.googleapis.com/v13/customers/".$customer_id.":generateKeywordIdeas";

            $postData = [
                "keywordSeed" => [
                    "keywords" => $keyword_seeds
                ],
                "pageSize" => 50
            ];

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($postData),
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "Authorization: Bearer " . env('ACCESS_TOKEN'),
                    "developer-token: " . env('DEVELOPER_TOKEN')
                ]
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);

            curl_close($curl);
            return $response;

        } catch (\Throwable $ex) {
            return $ex;
        }
    }

    public static function domainExistsInAIOverview($aiOverview, $searchterm)
    {
        if (!$aiOverview) {
            return false;
        }
        
        $searchterm = strtolower($searchterm);

        if (!empty($aiOverview->markdown) &&
            str_contains(strtolower($aiOverview->markdown), $searchterm)) {
            return true;
        }

        if (!empty($aiOverview->text_blocks) &&
            str_contains(strtolower($aiOverview->text_blocks), $searchterm)) {
            return true;
        }

        if (!empty($aiOverview->json)) {
            $jsonString = is_array($aiOverview->json)
                ? json_encode($aiOverview->json)
                : $aiOverview->json;

            if (str_contains(strtolower($jsonString), $searchterm)) {
                return true;
            }
        }

        return false;
    }
    public static function check_keyword_mentioned($keyword_planner_id, $client_property_id){
        $client_data = Client_propertiesModel::where('id',$client_property_id)->first();
        if (empty($client_data->keyword_mentioned_array)) {
            return false;
        }
        $searchTerms = array_map('trim', explode(',', $client_data->keyword_mentioned_array));
        $aioData=AiOverview::where([
            ['keyword_planner_id', $keyword_planner_id],
            ['client_property_id', $client_property_id ?? session('client_property_id')]
        ])->first();
        // dd($searchTerms);
        $aiOverviewObj = new \stdClass();
        $aiOverviewObj->markdown = $aioData['markdown'] ?? null;
        $aiOverviewObj->text_blocks = isset($aioData['text_blocks']) 
            ? json_encode($aioData['text_blocks']) 
            : null;
        $aiOverviewObj->json = json_encode($aioData);


        foreach ($searchTerms as $term) {
            if (!empty($term)) {
                $isMentioned = GeneralHelper::domainExistsInAIOverview($aiOverviewObj, $term);
                // dd($isMentioned);
                if ($isMentioned) {
                    return true;
                }
            }
        }
        // dd($keyword_planner_id, $client_property_id);
    }
}