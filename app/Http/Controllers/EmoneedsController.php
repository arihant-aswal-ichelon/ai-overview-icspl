<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DomainManagementModel;
use PDO;
use Carbon\Carbon;

class EmoneedsController extends Controller
{

    protected $client_id;
    protected $client_url;


    public function __construct(Request $request)
    {
        $this->client_id = $request->id;
        $this->client_url = $request->url;
        $this->client = DomainManagementModel::where('id', $this->client_id)->first();
    }


    // public function opdReport(Request $request)
    // {
    //     dd($request);
    // }

    public function opdReport(Request $request)
    {
        $lms_data = $filter_arr = [];
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        // dd($this->client->scheduled_slug);

        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            $db_encoded = file_get_contents($lms_url.'statuslog.txt');
            if(isset($db_encoded) && !empty($db_encoded)){
                $db_decode = json_decode($db_encoded);
                if(isset($db_decode) && !empty($db_decode)){
                    if($lms_url == $db_decode->site_url){
                        
                        /**LMS Server*/
                        $servername = $db_decode->access->ip;
                        $username = $db_decode->access->user;
                        $password = $db_decode->access->pass;
                        $dbName = $db_decode->access->name;

                        $error = true;
                    }
                }
            }
            if(!$error){
                $request->session()->flash("message", "Invalid Request!");
                return redirect('/view-client/'.$lms_client_id);
            }

             /** Local Server */
            //  $servername = "127.0.0.1";
            //  $username = "root";
            //  $password = "";
            //  $dbName = "emodeeds";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $condition = "";
            $condition1 = "";
            $condition2 = "";
            $condition3 = "";

            $filter_telecaller_arr = [];

            //Get Lead telecaller
            $lms_source_stmt = $lms_pdo->prepare("SELECT name as lead_telecaller_name, id as lead_telecaller_id FROM `users` WHERE `department_id` = '1';");
            $lms_source_stmt->execute();
            $lms_telecaller = $lms_source_stmt->fetchAll();

            $lms_data['lead_telecallers'] = $lms_telecaller;


            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){

                if(strpos("to", $_POST['lms_daterange']) == true){

                    $date = explode(' to ', $_POST['lms_daterange']);
                    
                    $date1 = Carbon::createFromFormat('d M, Y', $date[0]);
                    $date2 = Carbon::createFromFormat('d M, Y', $date[1]);
                    
                    $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                    $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                }else{
                    $date =  $_POST['lms_daterange'];
                    
                    $date1 = Carbon::createFromFormat('d M, Y', $date);
                    $date2 = Carbon::createFromFormat('d M, Y', $date);
                    
                    $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                    $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                }
                
                // dd($start_date);

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

                $condition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";
                $condition1 .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
                $condition2 .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition3 .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'and leads.created_at <= '".$start_date."'";

                if(isset($_POST['lms_telecallers']) && !empty($_POST['lms_telecallers'])){

                    $selected_telecallers = $_POST['lms_telecallers'];
                    $filter_telecaller_arr['filter_telecaller'] = $selected_telecallers;

                    $condition .= " and `telecallerid` = (".$selected_telecallers.")";
                    $condition2 .= " and `telecallerid` = (".$selected_telecallers.")";
                    $condition3 .= " and `telecallerid` = (".$selected_telecallers.")";


                    $lms_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition." Group By calls.lead_id) AS subquery;");
                    // dd($lms_opd_followup_stmt);
                    $lms_opd_followup_stmt->execute();
                    $lms_opd_followup = $lms_opd_followup_stmt->fetchAll();
                    $lms_data['lms_opd_followup'] = $lms_opd_followup[0]["count"];
                    
                    
                    $lms_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition." Group By calls.lead_id;");
                    $lms_opd_followup_ids_stmt->execute();
                    $lms_opd_followup_ids = $lms_opd_followup_ids_stmt->fetchAll();
                    
                    $opd_followup_ids_array = [];
                    foreach($lms_opd_followup_ids as $key => $lead){
                        $opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_opd_followup_ids = implode(',', $opd_followup_ids_array);
                    
                    if($implode_opd_followup_ids != ""){
                        $lms_next_opd_converted_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_next_opd_converted_stmt->execute();
                        $lms_next_opd_converted = $lms_next_opd_converted_stmt->fetchAll();
                        $lms_data['lms_next_opd_converted'] = $lms_next_opd_converted['0']['count'];
                        
                        $lms_next_opd_denial_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-denial') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_next_opd_denial_stmt->execute();
                        $lms_next_opd_denial = $lms_next_opd_denial_stmt->fetchAll();
                        $lms_data['lms_next_opd_denial'] = $lms_next_opd_denial['0']['count'];
                        
                        
                        $lms_converted_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('opd-converted-to-plan') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_converted_stmt->execute();
                        $lms_converted = $lms_converted_stmt->fetchAll();
                        $lms_data['lms_converted'] = $lms_converted['0']['count'];

                        $lms_effort_done_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_effort_done_stmt->execute();
                        $lms_effort_done = $lms_effort_done_stmt->fetchAll();
                        $lms_data['lms_effort_done'] = $lms_effort_done['0']['count'];
                    }else{
                        $lms_data['lms_next_opd_converted'] = 0;
                        $lms_data['lms_next_opd_denial'] = 0;
                        $lms_data['lms_converted'] = 0;
                        $lms_data['lms_effort_done'] = 0;
                    }

                    $lms_new_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition2." Group By calls.lead_id) AS subquery;");
                    // dd($lms_opd_followup_stmt);
                    $lms_new_opd_followup_stmt->execute();
                    $lms_new_opd_followup = $lms_new_opd_followup_stmt->fetchAll();
                    $lms_data['lms_new_opd_followup'] = $lms_new_opd_followup[0]["count"];

                    $lms_old_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition3." Group By calls.lead_id) AS subquery;");
                    $lms_old_opd_followup_stmt->execute();
                    $lms_old_opd_followup = $lms_old_opd_followup_stmt->fetchAll();
                    // dd($lms_old_opd_followup);
                    $lms_data['lms_old_opd_followup'] = $lms_old_opd_followup[0]["count"];

                    $lms_new_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition2." Group By calls.lead_id;");
                    $lms_new_opd_followup_ids_stmt->execute();
                    $lms_new_opd_followup_ids = $lms_new_opd_followup_ids_stmt->fetchAll();
                    
                    $new_opd_followup_ids_array = [];
                    foreach($lms_new_opd_followup_ids as $key => $lead){
                        $new_opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_new_opd_followup_ids = implode(',', $new_opd_followup_ids_array);

                    if($implode_new_opd_followup_ids != ""){

                        $lms_effort_done_new_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_new_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_effort_done_new_stmt->execute();
                        $lms_effort_done_new = $lms_effort_done_new_stmt->fetchAll();
                        $lms_data['lms_effort_done_new'] = $lms_effort_done_new['0']['count'];
                    }else{
                        $lms_data['lms_effort_done_new'] = 0;
                    }

                    $lms_old_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition3." Group By calls.lead_id;");
                    $lms_old_opd_followup_ids_stmt->execute();
                    $lms_old_opd_followup_ids = $lms_old_opd_followup_ids_stmt->fetchAll();
                    
                    $old_opd_followup_ids_array = [];
                    foreach($lms_old_opd_followup_ids as $key => $lead){
                        $old_opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_old_opd_followup_ids = implode(',', $old_opd_followup_ids_array);

                    if($implode_old_opd_followup_ids != ""){

                        $lms_effort_done_old_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_old_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_effort_done_old_stmt->execute();
                        $lms_effort_done_old = $lms_effort_done_old_stmt->fetchAll();
                        $lms_data['lms_effort_done_old'] = $lms_effort_done_old['0']['count'];
                    }else{
                        $lms_data['lms_effort_done_old'] = 0;
                    }

                }else{

                    $lms_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition." Group By calls.lead_id) AS subquery;");
                    
                    $lms_opd_followup_stmt->execute();
                    $lms_opd_followup = $lms_opd_followup_stmt->fetchAll();
                    $lms_data['lms_opd_followup'] = $lms_opd_followup[0]["count"];
                    
                    
                    $lms_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition." Group By calls.lead_id;");
                    $lms_opd_followup_ids_stmt->execute();
                    $lms_opd_followup_ids = $lms_opd_followup_ids_stmt->fetchAll();
                    
                    $opd_followup_ids_array = [];
                    foreach($lms_opd_followup_ids as $key => $lead){
                        $opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_opd_followup_ids = implode(',', $opd_followup_ids_array);
                    
                    if($implode_opd_followup_ids != ""){
                        $lms_next_opd_converted_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_next_opd_converted_stmt->execute();
                        $lms_next_opd_converted = $lms_next_opd_converted_stmt->fetchAll();
                        $lms_data['lms_next_opd_converted'] = $lms_next_opd_converted['0']['count'];
                        
                        $lms_next_opd_denial_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_next_opd_denial_stmt->execute();
                        $lms_next_opd_denial = $lms_next_opd_denial_stmt->fetchAll();
                        $lms_data['lms_next_opd_denial'] = $lms_next_opd_denial['0']['count'];
                        
                        
                        $lms_converted_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('opd-converted-to-plan') and status_logs.field_change = '0' and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_converted_stmt->execute();
                        $lms_converted = $lms_converted_stmt->fetchAll();
                        $lms_data['lms_converted'] = $lms_converted['0']['count'];

                        $lms_effort_done_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");

                        $lms_effort_done_stmt->execute();
                        $lms_effort_done = $lms_effort_done_stmt->fetchAll();
                        $lms_data['lms_effort_done'] = $lms_effort_done['0']['count'];

                    }else{
                        $lms_data['lms_next_opd_converted'] = 0;
                        $lms_data['lms_next_opd_denial'] = 0;
                        $lms_data['lms_converted'] = 0;
                        $lms_data['lms_effort_done'] = 0;
                    }

                    $lms_new_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition2." Group By calls.lead_id) AS subquery;");
                    $lms_new_opd_followup_stmt->execute();
                    $lms_new_opd_followup = $lms_new_opd_followup_stmt->fetchAll();
                    // dd($lms_new_opd_followup);
                    $lms_data['lms_new_opd_followup'] = $lms_new_opd_followup[0]["count"];

                    $lms_old_opd_followup_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition3." Group By calls.lead_id) AS subquery;");
                    $lms_old_opd_followup_stmt->execute();
                    $lms_old_opd_followup = $lms_old_opd_followup_stmt->fetchAll();
                    $lms_data['lms_old_opd_followup'] = $lms_old_opd_followup[0]["count"];

                    $lms_new_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition2." Group By calls.lead_id;");
                    $lms_new_opd_followup_ids_stmt->execute();
                    $lms_new_opd_followup_ids = $lms_new_opd_followup_ids_stmt->fetchAll();
                    // dd($lms_new_opd_followup_ids);
                    
                    $new_opd_followup_ids_array = [];
                    foreach($lms_new_opd_followup_ids as $key => $lead){
                        $new_opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_new_opd_followup_ids = implode(',', $new_opd_followup_ids_array);

                    if($implode_new_opd_followup_ids != ""){

                        $lms_effort_done_new_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_new_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        $lms_effort_done_new_stmt->execute();
                        $lms_effort_done_new = $lms_effort_done_new_stmt->fetchAll();
                        $lms_data['lms_effort_done_new'] = $lms_effort_done_new['0']['count'];
                    }else{
                        $lms_data['lms_effort_done_new'] = 0;
                    }

                    $lms_old_opd_followup_ids_stmt = $lms_pdo->prepare("SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('next-opd-follow-up') ) ".$condition3." Group By calls.lead_id;");
                    $lms_old_opd_followup_ids_stmt->execute();
                    $lms_old_opd_followup_ids = $lms_old_opd_followup_ids_stmt->fetchAll();
                    
                    $old_opd_followup_ids_array = [];
                    foreach($lms_old_opd_followup_ids as $key => $lead){
                        $old_opd_followup_ids_array[] = $lead['lead_id'];
                    }
                    $implode_old_opd_followup_ids = implode(',', $old_opd_followup_ids_array);

                    if($implode_old_opd_followup_ids != ""){

                        $lms_effort_done_old_stmt = $lms_pdo->prepare("SELECT COUNT(status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN ('next-opd-converted','next-opd-follow-up','opd-converted-to-plan','next-opd-denial') and status_logs.lead_id IN (".$implode_old_opd_followup_ids.")".$condition1." ORDER BY status_logs.created_at DESC;");
                        
                        $lms_effort_done_old_stmt->execute();
                        $lms_effort_done_old = $lms_effort_done_old_stmt->fetchAll();
                        $lms_data['lms_effort_done_old'] = $lms_effort_done_old['0']['count'];
                        // dd( $lms_effort_done_old);
                    }else{
                        $lms_data['lms_effort_done_old'] = 0;
                    }
                }

                if($lms_data['lms_opd_followup'] > 0){

                    $lms_data['lms_next_opd_converted_P'] = round(($lms_data['lms_next_opd_converted']/$lms_data['lms_opd_followup'] )* 100,2);
                    $lms_data['lms_next_opd_denial_P'] =  round(($lms_data['lms_next_opd_denial']/$lms_data['lms_opd_followup'] )* 100,2);
                    $lms_data['lms_converted_P'] =  round(($lms_data['lms_converted']/$lms_data['lms_opd_followup'] )* 100,2);
                }else{
                    $lms_data['lms_next_opd_converted_P'] = 0;
                    $lms_data['lms_next_opd_denial_P'] =  0;
                    $lms_data['lms_converted_P'] =  0;
                }

            }else {
                $lms_data['lms_daterange'] = '';
            }



                // dd($lms_data);


            $lms_data['filter_telecaller_arr'] = $filter_telecaller_arr;

            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.opd.lms-opd", compact("lms_data"));
    }

}
