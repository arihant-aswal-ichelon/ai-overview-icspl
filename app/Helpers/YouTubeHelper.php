<?php
namespace App\Helpers;
use Auth;

class YouTubeHelper{

    public static function get_yt_data_overall($request, $client_id, $start_date, $end_date, $yt_channel_id, $access_token){

        $youtube_api_key = env('YOUTUBE_API_KEY');
        $youtube_metrics = array( 'views', 'estimatedMinutesWatched',  'averageViewDuration', 'comments', 'likes', 'dislikes', 'shares','subscribersGained', 'subscribersLost' );

        $curl = curl_init();  

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://youtubeanalytics.googleapis.com/v2/reports?endDate='.$end_date.'&ids=channel=='.$yt_channel_id.'&metrics='.implode(',', $youtube_metrics).'&sort=-estimatedMinutesWatched&startDate='.$start_date.'&key='.$youtube_api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            $request->session()->flash("message", $error_msg);
            return redirect('/view-client/'.$client_id);
        }
        $response = json_decode($response);
        
        return $response;
    }

    public static function get_yt_top_watched($request, $client_id, $start_date, $end_date, $yt_channel_id, $access_token, $maxresults=10){

        $youtube_api_key = "AIzaSyAGMY5jnk_riNAmXRmCn65lweEs9v1vtMI";
        $youtube_metrics = array( 'views', 'averageViewDuration');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://youtubeanalytics.googleapis.com/v2/reports?dimensions=video&endDate='.$end_date.'&ids=channel=='.$yt_channel_id.'&maxResults='.$maxresults.'&metrics='.implode(',', $youtube_metrics).'&sort=-views&startDate='.$start_date.'&key='.$youtube_api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            $request->session()->flash("message", $error_msg);
            return redirect('/view-client/'.$client_id);
        }
        $response = json_decode($response);

        $response_arr = $yt_videos = array(); $yt_videos_str = "";
        if(isset($response->rows) && !empty($response->rows)){
            foreach ($response->rows as $key => $value) {
                $yt_video_id= $value[0];

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails%2Cstatistics&startDate='.$start_date.'&endDate='.$end_date.'&id='.$yt_video_id.'&key='.$youtube_api_key,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$access_token
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response);
                
                $yt_videos[] = array('video_statistics' => $response, 'video_data' => $value);
            }
            return $yt_videos;
        }

        return $response;
    }

    public static function get_yt_content($request, $client_id, $start_date, $end_date, $yt_channel_id, $access_token, $maxresults=10){
        
        $youtube_api_key = "AIzaSyAGMY5jnk_riNAmXRmCn65lweEs9v1vtMI";
        $youtube_metrics = array( 'views', 'estimatedMinutesWatched',  'averageViewDuration', 'comments', 'likes', 'dislikes', 'shares','subscribersGained', 'subscribersLost' );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://youtubeanalytics.googleapis.com/v2/reports?dimensions=video&endDate='.$end_date.'&ids=channel=='.$yt_channel_id.'&maxResults='.$maxresults.'&metrics='.implode(',', $youtube_metrics).'&sort=-views&startDate='.$start_date.'&key='.$youtube_api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            $request->session()->flash("message", $error_msg);
            return redirect('/view-client/'.$client_id);
        }
        $response = json_decode($response);

        $response_arr = $yt_videos = array(); $yt_videos_str = "";

        if(isset($response->rows) && !empty($response->rows)){
            foreach ($response->rows as $key => $value) {
                $yt_video_id= $value[0]; unset($value[0]);

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails%2Cstatistics&startDate='.$start_date.'&endDate='.$end_date.'&id='.$yt_video_id.'&key='.$youtube_api_key,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer '.$access_token
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $response = json_decode($response);
                
                $yt_videos[] = array('video_statistics' => $response, 'video_metrics' => $youtube_metrics, 'video_data' => $value, 'video_total' => self::get_yt_data_overall($request, $client_id, $start_date, $end_date, $yt_channel_id, $access_token));
            }
            return $yt_videos;
        }

        return $response;
    }

    public static function get_yt_video_by_id($request, $client_id, $start_date, $end_date, $yt_channel_id, $video_id, $access_token, $maxresults=10){

        $youtube_api_key = "AIzaSyAGMY5jnk_riNAmXRmCn65lweEs9v1vtMI";
        $youtube_metrics = array( 'views', 'estimatedMinutesWatched',  'averageViewDuration', 'comments', 'likes', 'dislikes', 'shares','subscribersGained', 'subscribersLost' );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://youtubeanalytics.googleapis.com/v2/reports?dimensions=video&endDate='.$end_date.'&filters=video=='.$video_id.'&ids=channel=='.$yt_channel_id.'&maxResults='.$maxresults.'&metrics='.implode(',', $youtube_metrics).'&sort=-views&startDate='.$start_date.'&key='.$youtube_api_key,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$access_token
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        if (curl_errno($curl)) {
            $error_msg = curl_error($curl);
        }
        curl_close($curl);
        if (isset($error_msg)) {
            $request->session()->flash("message", $error_msg);
            return redirect('/view-client/'.$client_id);
        }
        $video_statistics = json_decode($response);
        
        $response_arr = $yt_videos = array(); $yt_videos_str = "";
        if(isset($video_statistics->rows) && !empty($video_statistics->rows)){

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://youtube.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails%2Cstatistics&startDate='.$start_date.'&endDate='.$end_date.'&id='.$video_id.'&key='.$youtube_api_key,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer '.$access_token
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response);
            
            $yt_videos[] = array('video_data' => $response, 'video_statistics' => $video_statistics);
            
            return $yt_videos;
        }

        return $response;
    }

    public static function get_yt_traffic($request, $client_id, $start_date, $end_date, $yt_channel_id, $access_token){
        
    }
}