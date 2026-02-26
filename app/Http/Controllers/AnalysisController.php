<?php

namespace App\Http\Controllers;
use PDO;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Models\DomainManagementModel;
use App\Helpers\GeneralHelper;
use App\Helpers\YouTubeHelper;

class AnalysisController extends Controller
{
    protected $client_id;
    protected $client_url;

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        //
    }

    public function __construct(Request $request)
    {
        $this->client_id = $request->id;
        $this->client_url = $request->url;
        $this->client = DomainManagementModel::where('id', $this->client_id)->first();
    }

    //LMS Source
    public function get_lms_data_step_1(Request $request)
    {
        $lms_data = array();
        $error = false;
        $lms_client_id = $this->client_id;
        $lms_url = $this->client_url;
        
        try {
            $_SESSION['lms_client_check'] = $lms_client_id;
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
                //  $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();

            $lms_data['lms_stages'] = $lms_stages;
            $lms_data['lead_sources'] = $lms_sources;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['lms_client_name'] = $this->client->name;


        } catch(\PDOException $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.lms.lms-step-1", compact("lms_data"));
    }

    public function get_lms_data_step_2(Request $request)
    {
        $lms_data = $filter_arr = [];
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        $filters = ['filter_source' => []]; // Example filter source for demonstration

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check if 'lms_sources' is set in the POST array and filter out null values
            $lms_sourcesF = isset($_POST['lms_sources']) ? array_filter($_POST['lms_sources']) : [];
        }

        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            
            $db_encoded = file_get_contents($lms_url.'statuslog.txt');
            if(isset($db_encoded) && !empty($db_encoded)){
                $db_decode = json_decode($db_encoded);
                // dd($db_encoded);
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
            //  $dbName = "primeivfcrm_new";

            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;
            // dd($lms_sources);

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;

            $implode_selected_sources = '';
            $implode_slug = '';
            $condition = "";
            $conditionIncoming = "";
            $confirmationcondition = "";
            $condition1 = "";
            $condition2 = "";
            $condition3 = "";
            $condition4 = "";
            $condition5 = "";
            $conversions = [];
            $qualified = [];
            $disQualified = [];
            $notKnown = [];
            $scheduled = [];
            $visited = [];
            $missed = [];

            $Callback_condition = "";

            if(isset($lms_sourcesF) && !empty($lms_sourcesF)){
                
                $selected_sources = $lms_sourcesF;
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
                $condition = " where leads.lead_source_id IN (".$implode_selected_sources.") ";
                $Callback_condition .= " and leads.lead_source_id IN (".$implode_selected_sources.") ";
                $condition4 .= " and leads.lead_source_id IN (".$implode_selected_sources.") ";
                $condition5 .= " and status_logs.lead_source_id IN (".$implode_selected_sources.") ";
                $confirmationcondition .= " and leads.lead_source_id IN (".$implode_selected_sources.")";
            }

            // filter condition for leads log
            $duplicatelogcondition = "";
            $duplicatelogcondition1 = "";
            $statecondition = "";

            $created_date_filter = date('Y-m-d');

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){
                
                if(strpos($_POST['lms_daterange'],"to") != false){

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
                
                $conditionIncoming = " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition1 .= "WHERE leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition2 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition4 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition5 .= "and status_logs.created_at >= '".$start_date."' and status_logs.created_at < '".$end_date."'";
                $condition3 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

                $duplicatelogcondition .= " and duplicateleads.created_at >= '".$start_date."' and duplicateleads.created_at <= '".$end_date."'";
                $duplicatelogcondition1 .= "Where duplicateleads.created_at >= '".$start_date."' and duplicateleads.created_at <= '".$end_date."'";
                $confirmationcondition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";
                $statecondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";

                $Callback_condition .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."'";

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

            }else {

                $today = Carbon::yesterday()->startOfDay();

                $todayFormatted = $today->format('d M, Y');

                $startOfMonth = Carbon::now()->startOfMonth();

                $startOfMonthFormatted = $startOfMonth->format('d M, Y');

                $start_date = date('Y-m-d 00:00:00', strtotime($startOfMonthFormatted));
                $end_date = date('Y-m-d 00:00:00', strtotime($todayFormatted.' +1 day'));


                $conditionIncoming = " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition1 .= "WHERE leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition2 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition4 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $condition5 .= "and status_logs.created_at >= '".$start_date."' and status_logs.created_at < '".$end_date."'";
                $condition3 .= "and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

                $duplicatelogcondition .= " and duplicateleads.created_at >= '".$start_date."' and duplicateleads.created_at <= '".$end_date."'";
                $duplicatelogcondition1 .= "Where duplicateleads.created_at >= '".$start_date."' and duplicateleads.created_at <= '".$end_date."'";
                $confirmationcondition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";
                $statecondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";

                $Callback_condition .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."'";

                $lms_data['lms_daterange'] = $startOfMonthFormatted . " to " . $todayFormatted;
            }


            $today_lead_arr = array();
            $duplicate_lead_arr = array();

        // Creating sql condition for filters according to dates and stages
        if(isset($_POST['lms_stages']) && !empty($_POST['lms_stages'])){
            $selected_stages = $_POST['lms_stages'];
            $implode_selected_stages = implode(',', $_POST['lms_stages']);
            $filter_arr['filter_stage'] = $_POST['lms_stages'];
            foreach($selected_stages as $key => $stages){

                // For stages name
                $stages_stmt = $lms_pdo->prepare("SELECT name FROM lead_statuses where id='".$stages."';");

                $stages_stmt->execute();
                $stages_name = $stages_stmt->fetch();
                $lms_leads_status_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where status_logs.field_change = '0' and lead_statuses.id ='".$stages."' ".$condition5." ;");
                // dd( $lms_leads_status_stmt);
                $lms_leads_status_stmt->execute();
                $lms_leads_status = $lms_leads_status_stmt->fetchAll();
                $lms_leads_status_counts[] = array('stages' => $stages_name['name'], 'lead_count' => $lms_leads_status['0']['count']);

            }
            $lms_data['lms_leads_status'] = $lms_leads_status_counts;

        }
        else{  
            if(isset($lms_sourcesF) && !empty($lms_sourcesF)){
                //Get Leads
                $lms_leads_stmt = $lms_pdo->prepare("SELECT lead_sources.name as source, COALESCE(COUNT(leads.id), 0) AS lead_count FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id inner join lead_statuses on leads.lead_status_id=lead_statuses.id ".$condition." GROUP BY source order by lead_count desc;");
                $lms_leads_stmt->execute();
                $lms_leads = $lms_leads_stmt->fetchAll();
                $lms_data['lms_leads'] = $lms_leads;

                //Get Today Lead Count
                foreach($selected_sources as $key => $source){

                    $scheduled1 = null;
                    $visited1 = null;

                    // For source name
                    $source_stmt = $lms_pdo->prepare("SELECT name FROM lead_sources where id='".$source."';");
                    $source_stmt->execute();
                    $source_name = $source_stmt->fetch();
                    // For Today Lead Count
                    $lms_count_stmt = $lms_pdo->prepare("SELECT lead_sources.name as source, COALESCE(COUNT(leads.id), 0) AS lead_count FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id where leads.lead_source_id='".$source."' and date(leads.created_at)='".$created_date_filter."' ORDER BY lead_count DESC;");
                    $lms_count_stmt->execute();
                    $lms_count = $lms_count_stmt->fetch();
                    $today_lead_arr[] = array('source' => $source_name['name'], 'lead_count' => $lms_count['lead_count']);

                    // For Duplicate Lead Count
                    $lms_duplicate_count_stmt = $lms_pdo->prepare("SELECT lead_sources.name as source, COALESCE(COUNT(duplicateleads.id), 0) AS lead_count FROM lead_sources  LEFT JOIN duplicateleads ON lead_sources.id = duplicateleads.lead_source_id where duplicateleads.lead_source_id='".$source."'". $duplicatelogcondition." ORDER BY lead_count DESC;");
                    $lms_duplicate_count_stmt->execute();
                    $lms_duplicate_count = $lms_duplicate_count_stmt->fetch();
                    $duplicate_lead_arr[] = array('source' => $source_name['name'], 'lead_count' => $lms_duplicate_count['lead_count']);

                    //Get Leads where status is Conversions
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and leads.lead_source_id='".$source."' ".$condition2." ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions[] = array('source' => $source_name['name'], 'lead_count' => $lms_leads_status_conversions['0']['count']);

                    //Get Leads where status is Qualified
                    $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id=lead_statuses.id where lead_statuses. is_stage = 'qualified' and leads.lead_source_id='".$source."' ".$condition3." ;");
                    $lms_leads_status_qualified_stmt->execute();
                    $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
                    $qualified[] = array('source' => $source_name['name'], 'lead_count' => $lms_leads_status_qualified['0']['count']);

                    //Get Leads where status is DisQualified
                    $lms_leads_status_disQualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'disqualified' and leads.lead_source_id='".$source."' ".$condition3." ORDER BY leads.created_at DESC;");
                    $lms_leads_status_disQualified_stmt->execute();
                    $lms_leads_status_disQualified = $lms_leads_status_disQualified_stmt->fetchAll();
                    $disQualified[] = array('source' => $source_name['name'], 'lead_count' => $lms_leads_status_disQualified['0']['count']);

                    //Get Leads where status is Not Known
                    $lms_leads_status_notKnown_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'notknows' and leads.lead_source_id='".$source."' ".$condition3." ;");
                    $lms_leads_status_notKnown_stmt->execute();
                    $lms_leads_status_notKnown = $lms_leads_status_notKnown_stmt->fetchAll();
                    $notKnown[] = array('source' => $source_name['name'], 'lead_count' => $lms_leads_status_notKnown['0']['count']);

                    //Get Leads where status is Appointment Scheduled
                    $lms_leads_status_scheduled_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $lms_leads_status_scheduled_stmt->execute();
                    $lms_leads_status_scheduled = $lms_leads_status_scheduled_stmt->fetchAll();
                    $scheduled1 = $lms_leads_status_scheduled['0']['count'];

                    //Get Leads where status is Appointment Visited
                    $lms_leads_status_visited_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $lms_leads_status_visited_stmt->execute();
                    $lms_leads_status_visited = $lms_leads_status_visited_stmt->fetchAll();
                    $visited1 = $lms_leads_status_visited['0']['count'];

                    //Get Leads where status is Appointment Missed
                    $lms_leads_status_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs.lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $lms_leads_status_missed_stmt->execute();
                    $lms_leads_status_missed = $lms_leads_status_missed_stmt->fetchAll();
                    $missed[] = array('source' => $source_name['name'], 'lead_count' => $lms_leads_status_missed['0']['count']);


                    //Get Leads where status is Appointment Visit not in Schedule

                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);

                // not in Schedule

                if($implode_appointment_schedule != ''){
                    $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_stmt->execute();
                    $appointment_visit = $appointment_visit_stmt->fetchAll();
                    $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_Ucount = 0;
                }

                //Get Leads where status Conversions not in Appointment Visit 

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_array = [];
                foreach($appointment_visit as $key => $lead){
                    $appointment_visit_array[] = $lead['id'];
                }
                $implode_appointment_visit = implode(',', $appointment_visit_array);

                if($implode_appointment_schedule != ''){
                    //AV IDS Not in AS
                    $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_IDSNINAS_stmt->execute();
                    $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();
                    
                    $appointment_visit_IDSNINAS_array = [];
                    foreach($appointment_visit_IDSNINAS as $key => $lead){
                        $appointment_visit_IDSNINAS_array[] = $lead['id'];
                    }
                    $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);
                }else{
                    $implode_appointment_visit_IDSNINAS = "";
                }
                // not in visit
                if($implode_appointment_visit != ''){
                    $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition."  and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                    $conversions_stmt->execute();
                    $conversions1 = $conversions_stmt->fetchAll();
                    $conversions_Ucount =  $conversions1['0']['count'] ;

                    // conversions IDS Not in AV
                    $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                    $conversions_IDSNINAV_stmt->execute();
                    $conversions_IDSNINAV = $conversions_IDSNINAV_stmt->fetchAll();

                    $conversions_IDSNINAV_array = [];
                    foreach($conversions_IDSNINAV as $key => $lead){
                        $conversions_IDSNINAV_array[] = $lead['id'];
                    }
                    $implode_conversions_IDSNINAV = implode(',', $conversions_IDSNINAV_array);
                }else{
                    $conversions_Ucount = 0;
                    $implode_conversions_IDSNINAV = '';
                }

                //appointment_confirmation not in AV
                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$confirmationcondition." Group By calls.lead_id;");
                $appointment_confirmation_stmt->execute();
                $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
    
                $appointment_confirmation_array = [];
                foreach($appointment_confirmation_ids as $key => $lead){
                    $appointment_confirmation_array[] = $lead['id'];
                }
                $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);

                if($implode_appointment_confirmation != "")
                {                    
                    if($implode_conversions_IDSNINAV != "" ){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation,$implode_conversions_IDSNINAV]);
                    }
                    if($implode_appointment_visit_IDSNINAS != ""){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                    }
                }else{
                    if($implode_conversions_IDSNINAV != "" ){
                        
                        $implode_appointment_confirmation = $implode_conversions_IDSNINAV;
                    }else if($implode_appointment_visit_IDSNINAS != ""){
                        
                        $implode_appointment_confirmation = $implode_appointment_visit_IDSNINAS;
                    }
                    if($implode_appointment_visit_IDSNINAS != "" && $implode_conversions_IDSNINAV == ""){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                    }
                }
                
                //appointment_visit not in Schedule
                if($implode_appointment_confirmation != ''){
                    $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' and leads.lead_source_id='".$source."'".$statecondition." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_stmt->execute();
                    $appointment_visit = $appointment_visit_stmt->fetchAll();
                    
                    $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = '';
                }

                $scheduled1 = $scheduled1 + $appointment_visit_Ucount + $conversions_Ucount;
                $visited1 = $visited1 + $conversions_Ucount;

                $scheduled[] = array('source' => $source_name['name'], 'lead_count' => $scheduled1);
                $visited[] = array('source' => $source_name['name'], 'lead_count' => $visited1);

                }   
            
            }else{
                //Get Leads
                $lms_leads_stmt = $lms_pdo->prepare("SELECT COALESCE(COUNT(leads.id), 0) AS lead_count FROM `leads` LEFT JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id ".$condition1." and lead_statuses.is_stage IN ('notknows', 'qualified', 'disqualified');");
                // dd($lms_leads_stmt);
                $lms_leads_stmt->execute();
                $lms_leads = $lms_leads_stmt->fetchAll();
                $lms_data['lms_leads'] = $lms_leads;

                // For Today Lead Count
                $lms_leads_stmt = $lms_pdo->prepare("SELECT COALESCE(COUNT(leads.id), 0) AS lead_count FROM `leads` where date(leads.created_at)='".$created_date_filter."';");
                $lms_leads_stmt->execute();
                $lms_leads = $lms_leads_stmt->fetchAll();
                $today_lead_arr[] = array( 'lead_count' => $lms_leads['0']['lead_count']);

                // For Duplicate Lead Count
                $lms_duplicate_count_stmt = $lms_pdo->prepare("SELECT COALESCE(COUNT(DISTINCT duplicateleads.phone), 0) AS lead_count FROM duplicateleads ". $duplicatelogcondition1.";");
                // dd($lms_duplicate_count_stmt);
                $lms_duplicate_count_stmt->execute();
                $lms_duplicate_count = $lms_duplicate_count_stmt->fetch();
                $duplicate_lead_arr[] = array('lead_count' => $lms_duplicate_count['lead_count']);

                //Get Leads where status is Conversions
                $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$condition5." ORDER BY status_logs.created_at DESC;");
                // dd($lms_leads_status_conversions_stmt);
                $lms_leads_status_conversions_stmt->execute();
                $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                $conversions['count'] = $lms_leads_status_conversions['0']['count'];

                //Get Leads where status is Qualified
                $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses. is_stage = 'qualified' ".$condition.";");
                $lms_leads_status_qualified_stmt->execute();
                $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
                $qualified['count'] = $lms_leads_status_qualified['0']['count'];

                //Get Leads where status is DisQualified
                $lms_leads_status_disQualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'disqualified' ".$condition." ORDER BY leads.created_at DESC;");
                $lms_leads_status_disQualified_stmt->execute();
                $lms_leads_status_disQualified = $lms_leads_status_disQualified_stmt->fetchAll();
                $disQualified['count'] = $lms_leads_status_disQualified['0']['count'];

                //Get Leads where status is Not Known
                $lms_leads_status_notKnown_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses. is_stage = 'notknows' ".$condition." ;");
                $lms_leads_status_notKnown_stmt->execute();
                $lms_leads_status_notKnown = $lms_leads_status_notKnown_stmt->fetchAll();
                $notKnown['count'] = $lms_leads_status_notKnown['0']['count'];

                //Get Leads where status is Appointment Scheduled
                $lms_leads_status_scheduled_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                // dd($lms_leads_status_scheduled_stmt);

                $lms_leads_status_scheduled_stmt->execute();
                $lms_leads_status_scheduled = $lms_leads_status_scheduled_stmt->fetchAll();
                $scheduled['count'] = $lms_leads_status_scheduled['0']['count'];

                //Get Leads where status is Appointment Visited
                $lms_leads_status_visited_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_visited_stmt->execute();
                $lms_leads_status_visited = $lms_leads_status_visited_stmt->fetchAll();
                $visited['count'] = $lms_leads_status_visited['0']['count'];

                //Get Leads where status is Appointment Missed
                $lms_leads_status_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_missed_stmt->execute();
                $lms_leads_status_missed = $lms_leads_status_missed_stmt->fetchAll();
                $missed['count'] = $lms_leads_status_missed['0']['count'];

                        //Get Leads where status is Appointment Visit not in Schedule
                        $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                        $appointment_schedule_ids_stmt->execute();
                        $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();
                
                        $appointment_schedule_array = [];
                        foreach($appointment_schedule_ids as $key => $lead){
                            $appointment_schedule_array[] = $lead['id'];
                        }
                        $implode_appointment_schedule = implode(',', $appointment_schedule_array);
                        //appointment_visit not in Schedule
                        if($implode_appointment_schedule != ''){
                
                            $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                            $appointment_visit_stmt->execute();
                            $appointment_visit = $appointment_visit_stmt->fetchAll();
                            $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                                    
                            //AV IDS Not in AS
                            $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                            $appointment_visit_IDSNINAS_stmt->execute();
                            $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();
            
                            $appointment_visit_IDSNINAS_array = [];
                            foreach($appointment_visit_IDSNINAS as $key => $lead){
                                $appointment_visit_IDSNINAS_array[] = $lead['id'];
                            }
                            $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);
                        
                        }else{
                            $appointment_visit_Ucount = 0 ;
                            $implode_appointment_visit_IDSNINAS = '';
                        }
                        //Get Leads where status Conversions not in Appointment Visit 
                        $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                        $appointment_visit_stmt->execute();
                        $appointment_visit = $appointment_visit_stmt->fetchAll();
        
                        $appointment_visit_array = [];
                        foreach($appointment_visit as $key => $lead){
                            $appointment_visit_array[] = $lead['id'];
                        }
                        $implode_appointment_visit = implode(',', $appointment_visit_array);
                
                        //conversions not in visit
        
                        if($implode_appointment_visit != ''){
                            $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                            $conversions_stmt->execute();
                            $conversions1 = $conversions_stmt->fetchAll();
                            $conversions_Ucount =  $conversions1['0']['count'] ;
            
                            // conversions IDS Not in AV
                            $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                            $conversions_IDSNINAV_stmt->execute();
                            $conversions_IDSNINAV = $conversions_IDSNINAV_stmt->fetchAll();
            
                            $conversions_IDSNINAV_array = [];
                            foreach($conversions_IDSNINAV as $key => $lead){
                                $conversions_IDSNINAV_array[] = $lead['id'];
                            }
                            $implode_conversions_IDSNINAV = implode(',', $conversions_IDSNINAV_array);
                        }else{
                            $conversions_Ucount = 0;
                            $implode_conversions_IDSNINAV = '';
                        }
                        
                        //appointment_confirmation not in AV
                
                        $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$confirmationcondition." Group By calls.lead_id;");
                        $appointment_confirmation_stmt->execute();
                        $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
            
                        $appointment_confirmation_array = [];
                        foreach($appointment_confirmation_ids as $key => $lead){
                            $appointment_confirmation_array[] = $lead['id'];
                        }
                        $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);
        
                        if($implode_appointment_confirmation != "")
                        {                    
                            if($implode_conversions_IDSNINAV != "" ){
                                
                                $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation,$implode_conversions_IDSNINAV]);
                            }
                            if($implode_appointment_visit_IDSNINAS != ""){
                                
                                $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                            }
                        }else{
                            if($implode_conversions_IDSNINAV != "" ){
                                
                                $implode_appointment_confirmation = $implode_conversions_IDSNINAV;
                            }else if($implode_appointment_visit_IDSNINAS != ""){
                                
                                $implode_appointment_confirmation = $implode_appointment_visit_IDSNINAS;
                            }
                            if($implode_appointment_visit_IDSNINAS != "" && $implode_conversions_IDSNINAV == ""){
                                
                                $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                            }
                        }
                                
                        //appointment_visit not in Schedule
        
                        if($implode_appointment_confirmation != ''){
                            $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                            $appointment_visit_stmt->execute();
                            $appointment_visit = $appointment_visit_stmt->fetchAll();
            
                            $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                        }else{
                            $appointment_visit_count_NotIN_AC = 0;
                        }
                        $scheduled['count'] = $scheduled['count'] + $appointment_visit_Ucount + $conversions_Ucount;
                        $visited['count'] = $visited['count'] + $conversions_Ucount;

            }

            //Scheduled Callback
            $lms_callback_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`status` = 1".$Callback_condition." group by calls.lead_id;");
            // dd($lms_callback_stmt);
            $lms_callback_stmt->execute();
            $lms_callback = $lms_callback_stmt->fetchAll();
            $lms_data['lms_callback'] = $lms_callback;

            //Total Incoming Call
            $lms_Incoming_stmt = $lms_pdo->prepare("SELECT COUNT(id) as count FROM `leads` WHERE `lead_source_id` IN (SELECT `id` FROM `lead_sources` WHERE `name` LIKE '%ivr%') ".$conditionIncoming.";");
            $lms_Incoming_stmt->execute();
            $lms_Incoming = $lms_Incoming_stmt->fetchAll();
            $lms_data['lms_Incoming'] = $lms_Incoming[0]['count'];

            //Total Outgoing Call
            $lms_Outgoing_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`status` = 0".$Callback_condition." group by calls.lead_id;");
            // dd($lms_Outgoing_stmt);
            $lms_Outgoing_stmt->execute();
            $lms_Outgoing = $lms_Outgoing_stmt->fetchAll();
            
            $lms_data['lms_Outgoing'] = $lms_Outgoing;
            $lms_data['duplicate_lead_arr'] = $duplicate_lead_arr;
            $lms_data['today_lead_source_count'] = $today_lead_arr;
            $lms_data['lms_leads_status_conversions'] = $conversions;
            $lms_data['lms_leads_status_qualified'] = $qualified;
            $lms_data['lms_leads_status_disQualified'] = $disQualified;
            $lms_data['lms_leads_status_notKnown'] = $notKnown;
            $lms_data['lms_leads_status_scheduled'] = $scheduled;
            $lms_data['lms_leads_status_visited'] = $visited;
            $lms_data['lms_leads_status_missed'] = $missed;
        }

        $lms_data['filters'] = $filter_arr; 
        $lms_data['lms_client_id'] = $lms_client_id;
        $lms_data['lms_url'] = $this->client_url;
        $lms_data['lms_client_name'] = $this->client->name;

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.lms.lms-step-2", compact("lms_data"));
    }

    public function leadReport(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

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
            //  $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            
            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                
                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
           
            }else{
                $selected_sources = null;
                $implode_selected_sources = '';
                $filter_arr['filter_source'] = null;
            }
            
            // filter condition for leads
            $condition = "";
            $condition = " where leads.lead_source_id IN (".$implode_selected_sources.") ";

            // filter condition for leads log
            $logcondition = "";
            $log1condition = "";
            $duplicatelogcondition = "";

            $created_date_filter = date('Y-m-d');

            // Creating sql condition for filters according to dates and stages
            if(isset($_POST['lms_stages']) && !empty($_POST['lms_stages'])){
                $implode_selected_stages = implode(',', $_POST['lms_stages']);
                $filter_arr['filter_stage'] = $_POST['lms_stages'];
                $condition .= " and leads.lead_status_id IN (".$implode_selected_stages.") ";
                $logcondition .= " and status_logs.lead_status_id  IN (".$implode_selected_stages.") ";
            }

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

                $dates = explode(' to ', $_POST['lms_daterange']);
                $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($dates[1] .' +1 day'));

                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

            }else{
                $lms_data['lms_daterange'] = null;
            }


            if(isset($_POST['lms_updateddaterange']) && !empty($_POST['lms_updateddaterange'])){
                
                $lms_data['lms_updateddaterange'] = $_POST['lms_updateddaterange'];

                $dates = explode(' to ', $_POST['lms_updateddaterange']);
                $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($dates[1] .' +1 day'));

                $condition .= " and leads.updated_at >= '".$start_date."' and leads.updated_at <= '".$end_date."'";

            }else{
                $lms_data['lms_updateddaterange'] = null;
            }

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;

            //Get Lead Conversions Count
            $avgConversionDaysArr = [];
            $LeadHighestLowestStatusOrderarr = [];

            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){

                foreach($selected_sources as $key => $source){

                    //Avg. Conversion Days
                        $days = [];
                    // For source name
                    $source_stmt = $lms_pdo->prepare("SELECT name FROM lead_sources where id='".$source."';");
                    $source_stmt->execute();
                    $source_name = $source_stmt->fetch();
        
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT DISTINCT leads.id FROM `leads`inner join lead_statuses on leads.lead_status_id=lead_statuses.id where lead_statuses.converted = 'yes' and leads.lead_source_id='".$source."' ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();

                    if(!empty($lms_leads_status_conversions)){

                        foreach($lms_leads_status_conversions as $key => $lead){
                            
                            $leadCretedDate_stmt = $lms_pdo->prepare("SELECT created_at FROM leads WHERE id = '".$lead['id']. "';");
                            $leadCretedDate_stmt->execute();
                            $leadCretedDate = $leadCretedDate_stmt->fetchAll();
    
                            $leadConversionDate_stmt = $lms_pdo->prepare("SELECT status_logs.created_at FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and lead_id = '".$lead['id']. "';");
                            $leadConversionDate_stmt->execute();
                            $leadConversionDate = $leadConversionDate_stmt->fetchAll();
    
                            if(!empty($leadCretedDate) && !empty($leadConversionDate)){
    
                                $date1 = Carbon::parse($leadConversionDate['0']['created_at']);
                                $date2 = Carbon::parse($leadCretedDate['0']['created_at']);
                                $daysDifference = $date1->diffInDays($date2);
                                $days[] = $daysDifference;
                            }
                            
    
                        }

                        if(array_sum($days) > 0){
                            $avgConversionDays =Round(array_sum($days) / count($lms_leads_status_conversions), 2);
                        }else{
                            $avgConversionDays = 0;
                        }

                        // // Avg. Highest Stage Conversion Day
                        $LeadStatusOrder = [];

                        foreach($lms_leads_status_conversions as $key => $lead){
                            
                            $currentLeadStatus_stmt = $lms_pdo->prepare("SELECT lead_statuses.status_order FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE leads.id = '".$lead['id']. "';");
                            $currentLeadStatus_stmt->execute();
                            $currentLeadStatus = $currentLeadStatus_stmt->fetchAll()['0']['0'];

                            $LeadSecondHigestStatus_stmt = $lms_pdo->prepare("SELECT lead_statuses.status_order FROM status_logs inner join lead_statuses on status_logs.lead_status_id=lead_statuses.id where lead_id = '".$lead['id']. "' AND status_logs.field_change = '0' ORDER BY `status_logs`.`created_at` DESC ;");
                            $LeadSecondHigestStatus_stmt->execute();
                            $LeadSecondHigestStatus = $LeadSecondHigestStatus_stmt->fetchAll();
                            
                            $statusOrders = array_column($LeadSecondHigestStatus, 'status_order');
                            rsort($statusOrders);
                            $secondHighest = '';
                            if (isset($statusOrders[1])) {
                                $secondHighest = $statusOrders[1];
                            }
                            $LeadStatusOrder[] = array('source' => $source_name['name'],'Lead_id' => $lead['id'], 'secondHigheststatusOrder' => $secondHighest);
                        }

                        $daysHighest = [];
                        $daysLowest = [];
                        $HighestLeadCount = 0;
                        $LowestLeadCount = 0;

                        foreach($LeadStatusOrder as $key => $lead){
                            $leadCretedDate_stmt = $lms_pdo->prepare("SELECT created_at FROM leads WHERE id = '".$lead['Lead_id']. "';");
                            $leadCretedDate_stmt->execute();
                            $leadCretedDate = $leadCretedDate_stmt->fetchAll();
                            $leadConversionDate_stmt = $lms_pdo->prepare("SELECT status_logs.created_at FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and lead_id = '".$lead['Lead_id']. "';");
                            $leadConversionDate_stmt->execute();
                            $leadConversionDate = $leadConversionDate_stmt->fetchAll();
                            if(!empty($leadCretedDate) && !empty($leadConversionDate && $lead['secondHigheststatusOrder'] > $currentLeadStatus/2 )){
                                $date1 = Carbon::parse($leadConversionDate['0']['created_at']);
                                $date2 = Carbon::parse($leadCretedDate['0']['created_at']);
                                $daysDifference = $date1->diffInDays($date2);
                                $daysHighest[] = $daysDifference;
                                $HighestLeadCount++;
                            }
                            if(!empty($leadCretedDate) && !empty($leadConversionDate && $lead['secondHigheststatusOrder'] <= $currentLeadStatus/2 )){
                                $date1 = Carbon::parse($leadConversionDate['0']['created_at']);
                                $date2 = Carbon::parse($leadCretedDate['0']['created_at']);
                                $daysDifference = $date1->diffInDays($date2);
                                $daysLowest[] = $daysDifference;
                                $LowestLeadCount++ ;
                            }
                        }

                        if(array_sum($daysHighest) > 0){
                            $avgHighestConversionDays =Round(array_sum($daysHighest) / $HighestLeadCount, 2);
                        }else{
                            $avgHighestConversionDays = 0;
                        }
                        if(array_sum($daysLowest) > 0){
                            $avgLowestConversionDays =Round(array_sum($daysLowest) / $LowestLeadCount, 2);
                        }else{
                            $avgLowestConversionDays = 0;
                        }
                    }else{
                        $avgConversionDays = 0;
                        $avgHighestConversionDays = 0;
                        $avgLowestConversionDays = 0;
                    }

                    $avgConversionDaysArr[] = array('source' => $source_name['name'], 'avgConversionDays' => $avgConversionDays);

                    $LeadHighestLowestStatusOrderarr[] = array('source' => $source_name['name'], 'avgHighestConversionDays' => $avgHighestConversionDays, 'avgLowestConversionDays' => $avgLowestConversionDays);
                }
            }

            $lms_data['avgConversionDaysArr'] = $avgConversionDaysArr;
            $lms_data['LeadHighestLowestStatusOrderarr'] = $LeadHighestLowestStatusOrderarr;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['lms_client_name'] = $this->client->name;
            $lms_data['filters'] = $filter_arr;

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.lms.lms-leadReport", compact("lms_data"));
    }

    public function getStartAndEndDate($monthYear) {

        $dateTime = Carbon::createFromFormat('M, Y', $monthYear);
        $startDate = $dateTime->startOfMonth()->toDateString();
        $endDate = $dateTime->endOfMonth()->toDateString();
        return [$startDate, $endDate];
    }

    public function telecaller(Request $request)
    { 
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

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
            // $servername = "127.0.0.1";
            // $username = "root";
            // $password = "";
            // $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // filter condition for leads
            $Efficiency = "";
            $lms_product = "";
            $lms_source = "";
            $lms_complete_call[0][0] = 0;
            $lms_complete_new_call[0][0] = 0;
            $lms_complete_old_call[0][0] = 0;
            $number_calls = null;
            $working_days = null;

            
            $condition = "";
            $Callback_condition = "";
            $Callback_condition_new_calls = "";
            $Callback_condition_old_calls = "";

            if((isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])) || (isset($_POST['lms_daterange1']) && !empty($_POST['lms_daterange1']))){
                $lms_data['lms_date'] = $_POST['lms_date'];


                if((isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange']))){

                    $date1 = Carbon::createFromFormat('m.y', trim($_POST['lms_daterange']));
                    
                    // Format the date to the desired format
                    $formattedDate = $date1->format('M, Y');
                    $fdate = $formattedDate;
                    
                    $date = self::getStartAndEndDate($fdate);
                    
                    $lms_data['lms_daterange2'] = $_POST['lms_daterange'];
                    $lms_data['lms_daterange'] = $fdate;
                    $lms_data['lms_daterange1'] = null;
                    
                    $start_date = date("Y-m-d 00:00:00", strtotime($date[0]));
                    $end_date = date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));
                    // dd($lms_data);

                }
                
                if( (isset($_POST['lms_daterange1']) && !empty($_POST['lms_daterange1']))){
                    if(strpos($_POST['lms_daterange1'],"to") != false){
                        
                        $date = explode(' to ', $_POST['lms_daterange1']);
                        $date1 = Carbon::createFromFormat('d M, Y', $date[0]);
                        $date2 = Carbon::createFromFormat('d M, Y', $date[1]);
                        
                        $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                        $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                    }else{
                        $date =  $_POST['lms_daterange1'];
                        $date1 = Carbon::createFromFormat('d M, Y', $date);
                        $date2 = Carbon::createFromFormat('d M, Y', $date);
                        
                        $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                        $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                    }
                    
                    $lms_data['lms_daterange'] = null;
                    $lms_data['lms_daterange2'] = null;
                    $lms_data['lms_daterange1'] = $_POST['lms_daterange1'];
                    
                    // dd($lms_data);
                }

                // $date1 = Carbon::createFromFormat('m.y', trim($_POST['lms_daterange']));
                // // Format the date to the desired format
                // $formattedDate = $date1->format('M, Y');
                // $fdate = $formattedDate;

                // $lms_data['lms_daterange1'] = $_POST['lms_daterange'];
                // $lms_data['lms_daterange'] = $fdate;

                // $date = self::getStartAndEndDate($fdate);
                // $currentMonth = date('n');
                // $selectedMonth = date('n', strtotime($date[0]));

                // $start_date = date("Y-m-d 00:00:00", strtotime($date[0]));
                // $end_date = date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));

                $Callback_condition .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."'";
                $Callback_condition_new_calls .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."' and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $Callback_condition_old_calls .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."' and leads.created_at <= '".$start_date."'";
                $condition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";
                

                
                if(isset($_POST['number_calls']) && !empty($_POST['number_calls'])){

                    $calls_perDay = $_POST['number_calls'];

                    if((isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange']))){
                        $inputDate = $_POST['lms_daterange'];
                        $inputMonth = explode('.', $inputDate)[0];
                        $inputYear = explode('.', $inputDate)[1];
                        $currentMonth = Carbon::now()->month;

                        if ($inputMonth == $currentMonth) {
                            $today = Carbon::today();
                            $sundays = 0;
                            for ($day = 1; $day <= $today->day; $day++) {
                                $date = Carbon::create($inputYear, $currentMonth, $day);
                                if ($date->isSunday()) {
                                    $sundays++;
                                }
                            }
                            
                            $daysInMonthExcludingSundays = $today->day - $sundays;
                            
                            if (!$today->isSunday()) {
                                $daysInMonthExcludingSundays--;
                            }
        
                            $working_days = $daysInMonthExcludingSundays;
                            $number_calls = $calls_perDay * $working_days;
        
                        } else {
                            $MonthStartDate = Carbon::create($inputYear, $inputMonth, 1);
                            $MonthEndDate = $MonthStartDate->copy()->endOfMonth();
        
                            $period = CarbonPeriod::create($MonthStartDate, $MonthEndDate);
        
                            $working_days = $period->filter(function ($date) {
                                return !$date->isSunday();
                            })->count();
        
                            $number_calls = $calls_perDay * $working_days;
                        }
                    }
                    
                    if((isset($_POST['lms_daterange1']) && !empty($_POST['lms_daterange1']))){
                        if(strpos($_POST['lms_daterange1'],"to") != false){
                            
                            $inputDateRange = $_POST['lms_daterange1'];
                            $dateParts = explode(' to ', $inputDateRange);

                            // Parse the start and end dates
                            $startDate = Carbon::createFromFormat('d M, Y', trim($dateParts[0]));
                            $endDate = Carbon::createFromFormat('d M, Y', trim($dateParts[1]));

                            // Create a period from the start date to the end date
                            $period = CarbonPeriod::create($startDate, $endDate);

                            // Filter out Sundays to get only working days
                            $working_days = $period->filter(function ($date) {
                                return !$date->isSunday();
                            })->count();

                            $number_calls = $calls_perDay * $working_days;

                        }else{
                            $working_days = 1;
                            $number_calls = $calls_perDay * $working_days;
                        }
                        
                    }
                    // dd($working_days) ;
                    

                    $lms_data['number_calls'] = $_POST['number_calls'];
                }else{
                    $working_days = null;
                    $lms_data['number_calls'] = null;
                }

                
            }else{
                $lms_data['lms_daterange'] = null;
                $lms_data['lms_daterange1'] = null;
                $lms_data['lms_daterange2'] = null;
                $lms_data['number_calls'] = null;
                $lms_data['lms_date'] = null;

            }

            $filter_telecaller_arr = [];
            if(isset($_POST['lms_telecallers']) && !empty($_POST['lms_telecallers'])){

                $selected_telecallers = $_POST['lms_telecallers'];
                $filter_telecaller_arr['filter_telecaller'] = $selected_telecallers;
                $Callback_condition .= " and `calls`.`telecallerid` = (".$selected_telecallers.")";
                $Callback_condition_new_calls .= " and `calls`.`telecallerid` = (".$selected_telecallers.")";
                $Callback_condition_old_calls .= " and `calls`.`telecallerid` = (".$selected_telecallers.")";
                $condition .= " and calls.telecallerid = (".$selected_telecallers.")";
        
            }

            //Get Lead telecaller
            $lms_source_stmt = $lms_pdo->prepare("SELECT name as lead_telecaller_name, id as lead_telecaller_id FROM `users` WHERE `department_id` = '1';");
            $lms_source_stmt->execute();
            $lms_telecaller = $lms_source_stmt->fetchAll();
            $lms_data['lead_telecallers'] = $lms_telecaller;

            // $lms_data['filters_telecaller'] = $filter_telecaller_arr;


            if((isset($_POST['lms_telecallers']) && !empty($_POST['lms_telecallers'])) && ((isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])) || (isset($_POST['lms_daterange1']) && !empty($_POST['lms_daterange1'])))){

                $lms_complete_call_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id WHERE `calls`.`status` = 0 ".$Callback_condition." ;");
                // dd($lms_complete_call_stmt);
                $lms_complete_call_stmt->execute();
                $lms_complete_call = $lms_complete_call_stmt->fetchAll();

                //Call Funnel
               {
                 //Get Leads where status is not known
                 $lms_leads_status_notknown_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition." ;");
                 $lms_leads_status_notknown_stmt->execute();
                 $lms_leads_status_notknown = $lms_leads_status_notknown_stmt->fetchAll();
 
                 //Get Leads where status is Qualified
                 $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition." ;");
                 $lms_leads_status_qualified_stmt->execute();
                 $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
 
                 //Get Leads where status is Dis Qualified
                 $lms_leads_status_disQualified_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition." ;");
                 $lms_leads_status_disQualified_stmt->execute();
                 $lms_leads_status_disQualified = $lms_leads_status_disQualified_stmt->fetchAll();
 
                 //Get Leads where status is Conversions
                 $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." ;");
                 // dd($lms_leads_status_conversions_stmt);
                 $lms_leads_status_conversions_stmt->execute();
                 $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                 $lms_data['lms_leads_status_conversions'] = $lms_leads_status_conversions['0']['0'];
 
                 //Get Leads where status is Appointment Missed
                 $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                 $lms_leads_status_appointment_missed_stmt->execute();
                 $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
 
                 //Get Leads where status is Appointment Visit
                 $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                 $lms_leads_status_appointment_visit_stmt->execute();
                 $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                 $lms_data['lms_leads_status_appointment_visit'] = $lms_leads_status_appointment_visit['0']['0'];
 
                 //Get Leads where status is Appointment Schedule
                 $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                 $lms_leads_status_appointment_schedule_stmt->execute();
                 $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                 $lms_data['lms_leads_status_appointment_schedule'] = $lms_leads_status_appointment_schedule['0']['0'];
 
                 //Get Leads where status is Appointment Visit not in Schedule
                 $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                 
                 $appointment_schedule_ids_stmt->execute();
                 $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();
                 // dd($appointment_schedule_ids);
                 $appointment_schedule_array = [];
                 foreach($appointment_schedule_ids as $key => $lead){
                     $appointment_schedule_array[] = $lead['id'];
                 }
                 $implode_appointment_schedule = implode(',', $appointment_schedule_array);
 
                 //appointment_visit not in Schedule
                 if($implode_appointment_schedule != ''){
 
                     $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");                    
                     $appointment_visit_stmt->execute();
                     $appointment_visit = $appointment_visit_stmt->fetchAll();
                     $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                     
                     //AV IDS Not in AS
                     $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                     $appointment_visit_IDSNINAS_stmt->execute();
                     $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();
 
                     $appointment_visit_IDSNINAS_array = [];
                     foreach($appointment_visit_IDSNINAS as $key => $lead){
                         $appointment_visit_IDSNINAS_array[] = $lead['id'];
                     }
                     $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);
                 
                 }else{
                     $appointment_visit_Ucount = 0 ;
                     $implode_appointment_visit_IDSNINAS = '';
                 }
 
                  //Get Leads where status Conversions not in Appointment Visit 
                  $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                  $appointment_visit_stmt->execute();
                  $appointment_visit = $appointment_visit_stmt->fetchAll();
 
                  $appointment_visit_array = [];
                  foreach($appointment_visit as $key => $lead){
                      $appointment_visit_array[] = $lead['id'];
                  }
                  $implode_appointment_visit = implode(',', $appointment_visit_array);
 
                 //conversions not in visit
 
                 if($implode_appointment_visit != ''){
                     $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                     $conversions_stmt->execute();
                     $conversions = $conversions_stmt->fetchAll();
                     $conversions_Ucount =  $conversions['0']['count'] ;
 
                     // conversions IDS Not in AV
                     $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                     $conversions_IDSNINAV_stmt->execute();
                     $conversions_IDSNINAV = $conversions_IDSNINAV_stmt->fetchAll();
 
                     $conversions_IDSNINAV_array = [];
                     foreach($conversions_IDSNINAV as $key => $lead){
                         $conversions_IDSNINAV_array[] = $lead['id'];
                     }
                     $implode_conversions_IDSNINAV = implode(',', $conversions_IDSNINAV_array);
                 }else{
                     $conversions_Ucount = $lms_data['lms_leads_status_conversions'];
                     $implode_conversions_IDSNINAV = '';
                 }
 
                 //appointment_confirmation not in AV
 
                 $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition." and status_logs.field_change = '0' Group By calls.lead_id;");
                 $appointment_confirmation_stmt->execute();
                 $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
     
                 $appointment_confirmation_array = [];
                 foreach($appointment_confirmation_ids as $key => $lead){
                     $appointment_confirmation_array[] = $lead['id'];
                 }
                 $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);
 
                 if($implode_appointment_confirmation != "")
                 {                    
                     if($implode_conversions_IDSNINAV != "" ){
                         
                         $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation,$implode_conversions_IDSNINAV]);
                     }
                     if($implode_appointment_visit_IDSNINAS != ""){
                         
                         $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                     }
                 }else{
                     if($implode_conversions_IDSNINAV != "" ){
                         
                         $implode_appointment_confirmation = $implode_conversions_IDSNINAV;
                     }else if($implode_appointment_visit_IDSNINAS != ""){
                         
                         $implode_appointment_confirmation = $implode_appointment_visit_IDSNINAS;
                     }
                     if($implode_appointment_visit_IDSNINAS != "" && $implode_conversions_IDSNINAV == ""){
                         
                         $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                     }
                 }
 
                 //appointment_visit not in Schedule
 
                 if($implode_appointment_confirmation != ''){
                     $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                     // dd($appointment_visit_stmt);
 
                     $appointment_visit_stmt->execute();
                     $appointment_visit = $appointment_visit_stmt->fetchAll();
 
                     $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                 }else{
                     $appointment_visit_count_NotIN_AC = 0;
                 }
 
                 $lms_data['lms_leads_status_appointment_schedule'] = $lms_data['lms_leads_status_appointment_schedule'] + $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;
 
                 $lms_data['lms_leads_status_appointment_visit'] = $lms_data['lms_leads_status_appointment_visit'] + $conversions_Ucount;
 
                 //Get Leads where status is Interested
                 $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                 $lms_leads_status_interested_stmt->execute();
                 $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
                 $lms_data['lms_leads_status_interested'] = $lms_leads_status_interested['0']['0'] + $lms_data['lms_leads_status_appointment_schedule'];
 
                 //Get Leads where status is Confirmation
                 $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                 $lms_leads_status_appointment_confirmation_stmt->execute();
                 $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
                 
                 $lms_data['lms_leads_status_appointment_confirmation'] = $lms_leads_status_appointment_confirmation['0']['count'] + $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;


                 $lms_data['lms_leads_status_notknown'] = $lms_leads_status_notknown['0']['0'];
                 $lms_data['lms_leads_status_qualified'] = $lms_leads_status_qualified['0']['0'];
                 $lms_data['lms_leads_status_disQualified'] = $lms_leads_status_disQualified['0']['0'];
                 $lms_data['lms_leads_status_appointment_missed'] = $lms_leads_status_appointment_missed['0']['0'];
               }

                //Call Funnel new 
                {
                    //Get Leads where status is not known
                    $lms_leads_status_notknown_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_notknown_new_stmt->execute();
                    $lms_leads_status_notknown_new = $lms_leads_status_notknown_new_stmt->fetchAll();
    
                    //Get Leads where status is Qualified
                    $lms_leads_status_qualified_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_qualified_new_stmt->execute();
                    $lms_leads_status_qualified_new = $lms_leads_status_qualified_new_stmt->fetchAll();
    
                    //Get Leads where status is Dis Qualified
                    $lms_leads_status_disQualified_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_disQualified_new_stmt->execute();
                    $lms_leads_status_disQualified_new = $lms_leads_status_disQualified_new_stmt->fetchAll();
    
                    //Get Leads where status is Conversions
                    $lms_leads_status_conversions_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                    // dd($lms_leads_status_conversions_stmt);
                    $lms_leads_status_conversions_new_stmt->execute();
                    $lms_leads_status_conversions_new = $lms_leads_status_conversions_new_stmt->fetchAll();
                    $lms_data['lms_leads_status_conversions_new'] = $lms_leads_status_conversions_new['0']['0'];
    
                    //Get Leads where status is Appointment Missed
                    $lms_leads_status_appointment_missed_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_appointment_missed_new_stmt->execute();
                    $lms_leads_status_appointment_missed_new = $lms_leads_status_appointment_missed_new_stmt->fetchAll();
    
                    //Get Leads where status is Appointment Visit
                    $lms_leads_status_appointment_visit_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_appointment_visit_new_stmt->execute();
                    $lms_leads_status_appointment_visit_new = $lms_leads_status_appointment_visit_new_stmt->fetchAll();
                    $lms_data['lms_leads_status_appointment_visit_new'] = $lms_leads_status_appointment_visit_new['0']['0'];
    
                    //Get Leads where status is Appointment Schedule
                    $lms_leads_status_appointment_schedule_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                    $lms_leads_status_appointment_schedule_new_stmt->execute();
                    $lms_leads_status_appointment_schedule_new = $lms_leads_status_appointment_schedule_new_stmt->fetchAll();
                    $lms_data['lms_leads_status_appointment_schedule_new'] = $lms_leads_status_appointment_schedule_new['0']['0'];
    
                    //Get Leads where status is Appointment Visit not in Schedule
                    $appointment_schedule_ids_new_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                    
                    $appointment_schedule_ids_new_stmt->execute();
                    $appointment_schedule_ids_new = $appointment_schedule_ids_new_stmt->fetchAll();
                    // dd($appointment_schedule_ids);
                    $appointment_schedule_array = [];
                    foreach($appointment_schedule_ids_new as $key => $lead){
                        $appointment_schedule_array[] = $lead['id'];
                    }
                    $implode_appointment_schedule_new = implode(',', $appointment_schedule_array);
    
                    //appointment_visit not in Schedule
                    if($implode_appointment_schedule_new != ''){
    
                        $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_new.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");                    
                        $appointment_visit_new_stmt->execute();
                        $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
                        $appointment_visit_Ucount_new =  $appointment_visit_new['0']['count'] ;
                        
                        //AV IDS Not in AS
                        $appointment_visit_IDSNINAS_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                        $appointment_visit_IDSNINAS_new_stmt->execute();
                        $appointment_visit_IDSNINAS_new = $appointment_visit_IDSNINAS_new_stmt->fetchAll();
    
                        $appointment_visit_IDSNINAS_array = [];
                        foreach($appointment_visit_IDSNINAS_new as $key => $lead){
                            $appointment_visit_IDSNINAS_array[] = $lead['id'];
                        }
                        $implode_appointment_visit_IDSNINAS_new = implode(',', $appointment_visit_IDSNINAS_array);
                    
                    }else{
                        $appointment_visit_Ucount_new = 0 ;
                        $implode_appointment_visit_IDSNINAS_new = '';
                    }
    
                     //Get Leads where status Conversions not in Appointment Visit 
                     $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                     $appointment_visit_new_stmt->execute();
                     $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
    
                     $appointment_visit_array = [];
                     foreach($appointment_visit_new as $key => $lead){
                         $appointment_visit_array[] = $lead['id'];
                     }
                     $implode_appointment_visit_new = implode(',', $appointment_visit_array);
    
                    //conversions not in visit
    
                    if($implode_appointment_visit_new != ''){
                        $conversions_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." and leads.id Not IN (".$implode_appointment_visit_new.") ORDER BY status_logs.created_at DESC;");
                        $conversions_new_stmt->execute();
                        $conversions_new = $conversions_new_stmt->fetchAll();
                        $conversions_Ucount_new =  $conversions_new['0']['count'] ;
    
                        // conversions IDS Not in AV
                        $conversions_IDSNINAV_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." and leads.id Not IN (".$implode_appointment_visit_new.") ORDER BY status_logs.created_at DESC;");
                        $conversions_IDSNINAV_new_stmt->execute();
                        $conversions_IDSNINAV_new = $conversions_IDSNINAV_new_stmt->fetchAll();
    
                        $conversions_IDSNINAV_array = [];
                        foreach($conversions_IDSNINAV_new as $key => $lead){
                            $conversions_IDSNINAV_array[] = $lead['id'];
                        }
                        $implode_conversions_IDSNINAV_new = implode(',', $conversions_IDSNINAV_array);
                    }else{
                        $conversions_Ucount_new = $lms_data['lms_leads_status_conversions_new'];
                        $implode_conversions_IDSNINAV_new = '';
                    }
    
                    //appointment_confirmation not in AV
    
                    $appointment_confirmation_new_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_new_calls." and status_logs.field_change = '0' Group By calls.lead_id;");
                    $appointment_confirmation_new_stmt->execute();
                    $appointment_confirmation_ids_new = $appointment_confirmation_new_stmt->fetchAll();
        
                    $appointment_confirmation_array = [];
                    foreach($appointment_confirmation_ids_new as $key => $lead){
                        $appointment_confirmation_array[] = $lead['id'];
                    }
                    $implode_appointment_confirmation_new = implode(',', $appointment_confirmation_array);
    
                    if($implode_appointment_confirmation_new != "")
                    {                    
                        if($implode_conversions_IDSNINAV_new != "" ){
                            
                            $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new,$implode_conversions_IDSNINAV_new]);
                        }
                        if($implode_appointment_visit_IDSNINAS_new != ""){
                            
                            $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new, $implode_appointment_visit_IDSNINAS_new]);
                        }
                    }else{
                        if($implode_conversions_IDSNINAV_new != "" ){
                            
                            $implode_appointment_confirmation_new = $implode_conversions_IDSNINAV_new;
                        }else if($implode_appointment_visit_IDSNINAS_new != ""){
                            
                            $implode_appointment_confirmation_new = $implode_appointment_visit_IDSNINAS_new;
                        }
                        if($implode_appointment_visit_IDSNINAS_new != "" && $implode_conversions_IDSNINAV_new == ""){
                            
                            $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new, $implode_appointment_visit_IDSNINAS_new]);
                        }
                    }
    
                    //appointment_visit not in Schedule
    
                    if($implode_appointment_confirmation_new != ''){
                        $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation_new.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                        // dd($appointment_visit_stmt);
    
                        $appointment_visit_new_stmt->execute();
                        $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
    
                        $appointment_visit_count_NotIN_AC_new =  $appointment_visit_new['0']['count'] ;
                    }else{
                        $appointment_visit_count_NotIN_AC_new = 0;
                    }
    
                    $lms_data['lms_leads_status_appointment_schedule_new'] = $lms_data['lms_leads_status_appointment_schedule_new'] + $appointment_visit_Ucount_new + $conversions_Ucount_new + $appointment_visit_count_NotIN_AC_new;
    
                    $lms_data['lms_leads_status_appointment_visit_new'] = $lms_data['lms_leads_status_appointment_visit_new'] + $conversions_Ucount_new;
    
                    //Get Leads where status is Interested
                    $lms_leads_status_interested_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                    $lms_leads_status_interested_new_stmt->execute();
                    $lms_leads_status_interested_new = $lms_leads_status_interested_new_stmt->fetchAll();
                    $lms_data['lms_leads_status_interested_new'] = $lms_leads_status_interested_new['0']['0'] + $lms_data['lms_leads_status_appointment_schedule_new'];
    
                    //Get Leads where status is Confirmation
                    $lms_leads_status_appointment_confirmation_new_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_new_calls." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                    $lms_leads_status_appointment_confirmation_new_stmt->execute();
                    $lms_leads_status_appointment_confirmation_new = $lms_leads_status_appointment_confirmation_new_stmt->fetchAll();
                    
                    $lms_data['lms_leads_status_appointment_confirmation_new'] = $lms_leads_status_appointment_confirmation_new['0']['count'] + $appointment_visit_Ucount_new + $conversions_Ucount_new + $appointment_visit_count_NotIN_AC_new;

                    $lms_data['lms_leads_status_notknown_new'] = $lms_leads_status_notknown_new['0']['0'];
                    $lms_data['lms_leads_status_qualified_new'] = $lms_leads_status_qualified_new['0']['0'];
                    $lms_data['lms_leads_status_disQualified_new'] = $lms_leads_status_disQualified_new['0']['0'];
                    $lms_data['lms_leads_status_appointment_missed_new'] = $lms_leads_status_appointment_missed_new['0']['0'];
                }

                //Call Funnel old
                {
                    //Get Leads where status is not known
                    $lms_leads_status_notknown_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_notknown_old_stmt->execute();
                    $lms_leads_status_notknown_old = $lms_leads_status_notknown_old_stmt->fetchAll();
    
                    //Get Leads where status is Qualified
                    $lms_leads_status_qualified_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_qualified_old_stmt->execute();
                    $lms_leads_status_qualified_old = $lms_leads_status_qualified_old_stmt->fetchAll();
    
                    //Get Leads where status is Dis Qualified
                    $lms_leads_status_disQualified_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_disQualified_old_stmt->execute();
                    $lms_leads_status_disQualified_old = $lms_leads_status_disQualified_old_stmt->fetchAll();
    
                    //Get Leads where status is Conversions
                    $lms_leads_status_conversions_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                    // dd($lms_leads_status_conversions_stmt);
                    $lms_leads_status_conversions_old_stmt->execute();
                    $lms_leads_status_conversions_old = $lms_leads_status_conversions_old_stmt->fetchAll();
                    $lms_data['lms_leads_status_conversions_old'] = $lms_leads_status_conversions_old['0']['0'];
    
                    //Get Leads where status is Appointment Missed
                    $lms_leads_status_appointment_missed_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_appointment_missed_old_stmt->execute();
                    $lms_leads_status_appointment_missed_old = $lms_leads_status_appointment_missed_old_stmt->fetchAll();
    
                    //Get Leads where status is Appointment Visit
                    $lms_leads_status_appointment_visit_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_appointment_visit_old_stmt->execute();
                    $lms_leads_status_appointment_visit_old = $lms_leads_status_appointment_visit_old_stmt->fetchAll();
                    $lms_data['lms_leads_status_appointment_visit_old'] = $lms_leads_status_appointment_visit_old['0']['0'];
    
                    //Get Leads where status is Appointment Schedule
                    $lms_leads_status_appointment_schedule_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                    $lms_leads_status_appointment_schedule_old_stmt->execute();
                    $lms_leads_status_appointment_schedule_old = $lms_leads_status_appointment_schedule_old_stmt->fetchAll();
                    $lms_data['lms_leads_status_appointment_schedule_old'] = $lms_leads_status_appointment_schedule_old['0']['0'];
    
                    //Get Leads where status is Appointment Visit not in Schedule
                    $appointment_schedule_ids_old_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                    
                    $appointment_schedule_ids_old_stmt->execute();
                    $appointment_schedule_ids_old = $appointment_schedule_ids_old_stmt->fetchAll();
                    // dd($appointment_schedule_ids);
                    $appointment_schedule_array = [];
                    foreach($appointment_schedule_ids_old as $key => $lead){
                        $appointment_schedule_array[] = $lead['id'];
                    }
                    $implode_appointment_schedule_old = implode(',', $appointment_schedule_array);
    
                    //appointment_visit not in Schedule
                    if($implode_appointment_schedule_old != ''){
    
                        $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");                    
                        $appointment_visit_old_stmt->execute();
                        $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
                        $appointment_visit_Ucount_old =  $appointment_visit_old['0']['count'] ;
                        
                        //AV IDS Not in AS
                        $appointment_visit_IDSNINAS_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                        $appointment_visit_IDSNINAS_old_stmt->execute();
                        $appointment_visit_IDSNINAS_old = $appointment_visit_IDSNINAS_old_stmt->fetchAll();
    
                        $appointment_visit_IDSNINAS_array = [];
                        foreach($appointment_visit_IDSNINAS_old as $key => $lead){
                            $appointment_visit_IDSNINAS_array[] = $lead['id'];
                        }
                        $implode_appointment_visit_IDSNINAS_old = implode(',', $appointment_visit_IDSNINAS_array);
                    
                    }else{
                        $appointment_visit_Ucount_old = 0 ;
                        $implode_appointment_visit_IDSNINAS_old = '';
                    }
    
                     //Get Leads where status Conversions not in Appointment Visit 
                     $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                     $appointment_visit_old_stmt->execute();
                     $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
    
                     $appointment_visit_array = [];
                     foreach($appointment_visit_old as $key => $lead){
                         $appointment_visit_array[] = $lead['id'];
                     }
                     $implode_appointment_visit_old = implode(',', $appointment_visit_array);
    
                    //conversions not in visit
    
                    if($implode_appointment_visit_old != ''){
                        $conversions_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." and leads.id Not IN (".$implode_appointment_visit_old.") ORDER BY status_logs.created_at DESC;");
                        $conversions_old_stmt->execute();
                        $conversions_old = $conversions_old_stmt->fetchAll();
                        $conversions_Ucount_old =  $conversions_old['0']['count'] ;
    
                        // conversions IDS Not in AV
                        $conversions_IDSNINAV_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." and leads.id Not IN (".$implode_appointment_visit_old.") ORDER BY status_logs.created_at DESC;");
                        $conversions_IDSNINAV_old_stmt->execute();
                        $conversions_IDSNINAV_old = $conversions_IDSNINAV_old_stmt->fetchAll();
    
                        $conversions_IDSNINAV_array = [];
                        foreach($conversions_IDSNINAV_old as $key => $lead){
                            $conversions_IDSNINAV_array[] = $lead['id'];
                        }
                        $implode_conversions_IDSNINAV_old = implode(',', $conversions_IDSNINAV_array);
                    }else{
                        $conversions_Ucount_old = $lms_data['lms_leads_status_conversions_old'];
                        $implode_conversions_IDSNINAV_old = '';
                    }
    
                    //appointment_confirmation not in AV
    
                    $appointment_confirmation_old_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_old_calls." and status_logs.field_change = '0' Group By calls.lead_id;");
                    $appointment_confirmation_old_stmt->execute();
                    $appointment_confirmation_ids_old = $appointment_confirmation_old_stmt->fetchAll();
        
                    $appointment_confirmation_array = [];
                    foreach($appointment_confirmation_ids_old as $key => $lead){
                        $appointment_confirmation_array[] = $lead['id'];
                    }
                    $implode_appointment_confirmation_old = implode(',', $appointment_confirmation_array);
    
                    if($implode_appointment_confirmation_old != "")
                    {                    
                        if($implode_conversions_IDSNINAV_old != "" ){
                            
                            $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old,$implode_conversions_IDSNINAV_old]);
                        }
                        if($implode_appointment_visit_IDSNINAS_old != ""){
                            
                            $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old, $implode_appointment_visit_IDSNINAS_old]);
                        }
                    }else{
                        if($implode_conversions_IDSNINAV_old != "" ){
                            
                            $implode_appointment_confirmation_old = $implode_conversions_IDSNINAV_old;
                        }else if($implode_appointment_visit_IDSNINAS_old != ""){
                            
                            $implode_appointment_confirmation_old = $implode_appointment_visit_IDSNINAS_old;
                        }
                        if($implode_appointment_visit_IDSNINAS_old != "" && $implode_conversions_IDSNINAV_old == ""){
                            
                            $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old, $implode_appointment_visit_IDSNINAS_old]);
                        }
                    }
    
                    //appointment_visit not in Schedule
    
                    if($implode_appointment_confirmation_old != ''){
                        $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                        // dd($appointment_visit_stmt);
    
                        $appointment_visit_old_stmt->execute();
                        $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
    
                        $appointment_visit_count_NotIN_AC_old =  $appointment_visit_old['0']['count'] ;
                    }else{
                        $appointment_visit_count_NotIN_AC_old = 0;
                    }
    
                    $lms_data['lms_leads_status_appointment_schedule_old'] = $lms_data['lms_leads_status_appointment_schedule_old'] + $appointment_visit_Ucount_old + $conversions_Ucount_old + $appointment_visit_count_NotIN_AC_old;
    
                    $lms_data['lms_leads_status_appointment_visit_old'] = $lms_data['lms_leads_status_appointment_visit_old'] + $conversions_Ucount_old;
    
                    //Get Leads where status is Interested
                    $lms_leads_status_interested_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                    $lms_leads_status_interested_old_stmt->execute();
                    $lms_leads_status_interested_old = $lms_leads_status_interested_old_stmt->fetchAll();
                    $lms_data['lms_leads_status_interested_old'] = $lms_leads_status_interested_old['0']['0'] + $lms_data['lms_leads_status_appointment_schedule_old'];
    
                    //Get Leads where status is Confirmation
                    $lms_leads_status_appointment_confirmation_old_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_old_calls." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                    $lms_leads_status_appointment_confirmation_old_stmt->execute();
                    $lms_leads_status_appointment_confirmation_old = $lms_leads_status_appointment_confirmation_old_stmt->fetchAll();
                    
                    $lms_data['lms_leads_status_appointment_confirmation_old'] = $lms_leads_status_appointment_confirmation_old['0']['count'] + $appointment_visit_Ucount_old + $conversions_Ucount_old + $appointment_visit_count_NotIN_AC_old;

                    $lms_data['lms_leads_status_notknown_old'] = $lms_leads_status_notknown_old['0']['0'];
                    $lms_data['lms_leads_status_qualified_old'] = $lms_leads_status_qualified_old['0']['0'];
                    $lms_data['lms_leads_status_disQualified_old'] = $lms_leads_status_disQualified_old['0']['0'];
                    $lms_data['lms_leads_status_appointment_missed_old'] = $lms_leads_status_appointment_missed_old['0']['0'];
                }

                $lms_complete_new_call_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id WHERE `status` = 0 ".$Callback_condition_new_calls." ;");
                $lms_complete_new_call_stmt->execute();
                $lms_complete_new_call = $lms_complete_new_call_stmt->fetchAll();

                $lms_complete_new_call_field_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id WHERE `status` = 0 and status_logs.field_change = '1' ".$Callback_condition_new_calls." ;");
                $lms_complete_new_call_field_stmt->execute();
                $lms_complete_new_call_field = $lms_complete_new_call_field_stmt->fetchAll();

                $lms_complete_new_call_state_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id WHERE `status` = 0 and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                $lms_complete_new_call_state_stmt->execute();
                $lms_complete_new_call_state = $lms_complete_new_call_state_stmt->fetchAll();

                $lms_complete_old_call_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id WHERE `status` = 0 ".$Callback_condition_old_calls." ;");
                $lms_complete_old_call_stmt->execute();
                $lms_complete_old_call = $lms_complete_old_call_stmt->fetchAll();

                $lms_complete_old_call_state_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id WHERE `status` = 0 and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                $lms_complete_old_call_state_stmt->execute();
                $lms_complete_old_call_state = $lms_complete_old_call_state_stmt->fetchAll();

                $lms_complete_old_call_field_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id WHERE `status` = 0 and status_logs.field_change = '1'".$Callback_condition_old_calls." ;");
                $lms_complete_old_call_field_stmt->execute();
                $lms_complete_old_call_field = $lms_complete_old_call_field_stmt->fetchAll();

              
                $lms_data['number_new_calls_field_change'] = $lms_complete_new_call_field['0']['0'];
                $lms_data['number_new_calls_state_change'] = $lms_complete_new_call_state['0']['0'];
                $lms_data['number_old_calls_field_change'] = $lms_complete_old_call_field['0']['0'];
                $lms_data['number_old_calls_state_change'] = $lms_complete_old_call_state['0']['0'];


                if(!empty($lms_complete_call)){
                    $Efficiency = Round( ($lms_complete_call['0']['0'] / ($number_calls)) * 100 , 2);
                }else{
                    $Efficiency = 0;
                }
                
                $lms_product_call_stmt = $lms_pdo->prepare("SELECT leads.product_id ,COUNT(DISTINCT calls.lead_id) AS product_count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE calls.status = 0  and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." GROUP BY leads.product_id ORDER BY product_count DESC;");
                $lms_product_call_stmt->execute();
                $lms_product_call = $lms_product_call_stmt->fetchAll();
                // dd($lms_product_call);

                if(!empty($lms_product_call)){
                    $max_product_count = -1; 
                    $max_product_id = null;

                    foreach ($lms_product_call as $item) {
                        if ($item['product_count'] > $max_product_count) {
                            $max_product_count = $item['product_count'];
                            $max_product_id = $item['product_id'];
                        }
                    }
                    $lms_product_stmt = $lms_pdo->prepare("SELECT name FROM `products` WHERE `id` = '$max_product_id';");
                    $lms_product_stmt->execute();
                    $lms_product_data = $lms_product_stmt->fetchAll();
                    $lms_product = $lms_product_data['0']['name'];
                }else{
                    $lms_product = "No leads have been converted by telecaller yet.";
                }

                $lms_source_call_stmt = $lms_pdo->prepare("SELECT lead_sources.name, leads.lead_source_id ,COUNT(DISTINCT calls.lead_id) AS source_count FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_sources on leads.lead_source_id = lead_sources.id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE calls.status = 0  and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." GROUP BY leads.lead_source_id ORDER BY source_count DESC;");
                // dd($lms_source_call_stmt);

                $lms_source_call_stmt->execute();
                $lms_source_call = $lms_source_call_stmt->fetchAll();
                $lms_data['lms_source_call'] = $lms_source_call;


                if(!empty($lms_source_call)){

                    $max_source_count = -1; 
                    $max_source_id = null;

                    foreach ($lms_source_call as $item) {
                        if ($item['source_count'] > $max_source_count) {
                            $max_source_count = $item['source_count'];
                            $max_source_id = $item['lead_source_id'];
                        }
                    }

                    $lms_source_stmt = $lms_pdo->prepare("SELECT name FROM `lead_sources` WHERE `id` = '$max_source_id';");
                    $lms_source_stmt->execute();
                    $lms_source_data = $lms_source_stmt->fetchAll();

                    $lms_source = $lms_source_data['0']['name'];
                }else{
                    $lms_source = "No leads have been converted by telecaller yet.";
                }

                $lms_data['overall_call'] = false;

            } else if((isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])) || (isset($_POST['lms_daterange1']) && !empty($_POST['lms_daterange1']))){

                $lms_complete_call_stmt = $lms_pdo->prepare("SELECT Count(id) FROM `calls` WHERE `status` = 0 ".$Callback_condition." ;");
                $lms_complete_call_stmt->execute();
                $lms_complete_call = $lms_complete_call_stmt->fetchAll();

                //Call Funnel
               {
                //Get Leads where status is not known
                $lms_leads_status_notknown_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition." ;");
                $lms_leads_status_notknown_stmt->execute();
                $lms_leads_status_notknown = $lms_leads_status_notknown_stmt->fetchAll();

                //Get Leads where status is Qualified
                $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition." ;");
                $lms_leads_status_qualified_stmt->execute();
                $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();

                //Get Leads where status is Dis Qualified
                $lms_leads_status_disQualified_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition." ;");
                $lms_leads_status_disQualified_stmt->execute();
                $lms_leads_status_disQualified = $lms_leads_status_disQualified_stmt->fetchAll();

                //Get Leads where status is Conversions
                $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." ;");
                // dd($lms_leads_status_conversions_stmt);
                $lms_leads_status_conversions_stmt->execute();
                $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                $lms_data['lms_leads_status_conversions'] = $lms_leads_status_conversions['0']['0'];

                //Get Leads where status is Appointment Missed
                $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                $lms_leads_status_appointment_missed_stmt->execute();
                $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();

                //Get Leads where status is Appointment Visit
                $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                $lms_leads_status_appointment_visit_stmt->execute();
                $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_visit'] = $lms_leads_status_appointment_visit['0']['0'];

                //Get Leads where status is Appointment Schedule
                $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition." ;");
                $lms_leads_status_appointment_schedule_stmt->execute();
                $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_schedule'] = $lms_leads_status_appointment_schedule['0']['0'];

                //Get Leads where status is Appointment Visit not in Schedule
                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();
                // dd($appointment_schedule_ids);
                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);

                //appointment_visit not in Schedule
                if($implode_appointment_schedule != ''){

                    $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");                    
                    $appointment_visit_stmt->execute();
                    $appointment_visit = $appointment_visit_stmt->fetchAll();
                    $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                    
                    //AV IDS Not in AS
                    $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_IDSNINAS_stmt->execute();
                    $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();

                    $appointment_visit_IDSNINAS_array = [];
                    foreach($appointment_visit_IDSNINAS as $key => $lead){
                        $appointment_visit_IDSNINAS_array[] = $lead['id'];
                    }
                    $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);
                
                }else{
                    $appointment_visit_Ucount = 0 ;
                    $implode_appointment_visit_IDSNINAS = '';
                }

                 //Get Leads where status Conversions not in Appointment Visit 
                 $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                 $appointment_visit_stmt->execute();
                 $appointment_visit = $appointment_visit_stmt->fetchAll();

                 $appointment_visit_array = [];
                 foreach($appointment_visit as $key => $lead){
                     $appointment_visit_array[] = $lead['id'];
                 }
                 $implode_appointment_visit = implode(',', $appointment_visit_array);

                //conversions not in visit

                if($implode_appointment_visit != ''){
                    $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                    $conversions_stmt->execute();
                    $conversions = $conversions_stmt->fetchAll();
                    $conversions_Ucount =  $conversions['0']['count'] ;

                    // conversions IDS Not in AV
                    $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                    $conversions_IDSNINAV_stmt->execute();
                    $conversions_IDSNINAV = $conversions_IDSNINAV_stmt->fetchAll();

                    $conversions_IDSNINAV_array = [];
                    foreach($conversions_IDSNINAV as $key => $lead){
                        $conversions_IDSNINAV_array[] = $lead['id'];
                    }
                    $implode_conversions_IDSNINAV = implode(',', $conversions_IDSNINAV_array);
                }else{
                    $conversions_Ucount = $lms_data['lms_leads_status_conversions'];
                    $implode_conversions_IDSNINAV = '';
                }

                //appointment_confirmation not in AV

                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition." and status_logs.field_change = '0' Group By calls.lead_id;");
                $appointment_confirmation_stmt->execute();
                $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
    
                $appointment_confirmation_array = [];
                foreach($appointment_confirmation_ids as $key => $lead){
                    $appointment_confirmation_array[] = $lead['id'];
                }
                $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);

                if($implode_appointment_confirmation != "")
                {                    
                    if($implode_conversions_IDSNINAV != "" ){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation,$implode_conversions_IDSNINAV]);
                    }
                    if($implode_appointment_visit_IDSNINAS != ""){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                    }
                }else{
                    if($implode_conversions_IDSNINAV != "" ){
                        
                        $implode_appointment_confirmation = $implode_conversions_IDSNINAV;
                    }else if($implode_appointment_visit_IDSNINAS != ""){
                        
                        $implode_appointment_confirmation = $implode_appointment_visit_IDSNINAS;
                    }
                    if($implode_appointment_visit_IDSNINAS != "" && $implode_conversions_IDSNINAV == ""){
                        
                        $implode_appointment_confirmation = implode(',', [$implode_appointment_confirmation, $implode_appointment_visit_IDSNINAS]);
                    }
                }

                //appointment_visit not in Schedule

                if($implode_appointment_confirmation != ''){
                    $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                    // dd($appointment_visit_stmt);

                    $appointment_visit_stmt->execute();
                    $appointment_visit = $appointment_visit_stmt->fetchAll();

                    $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = 0;
                }

                $lms_data['lms_leads_status_appointment_schedule'] = $lms_data['lms_leads_status_appointment_schedule'] + $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;

                $lms_data['lms_leads_status_appointment_visit'] = $lms_data['lms_leads_status_appointment_visit'] + $conversions_Ucount;

                //Get Leads where status is Interested
                $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_interested_stmt->execute();
                $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
                $lms_data['lms_leads_status_interested'] = $lms_leads_status_interested['0']['0'] + $lms_data['lms_leads_status_appointment_schedule'];

                //Get Leads where status is Confirmation
                $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                $lms_leads_status_appointment_confirmation_stmt->execute();
                $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
                
                $lms_data['lms_leads_status_appointment_confirmation'] = $lms_leads_status_appointment_confirmation['0']['count'] + $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;


                $lms_data['lms_leads_status_notknown'] = $lms_leads_status_notknown['0']['0'];
                $lms_data['lms_leads_status_qualified'] = $lms_leads_status_qualified['0']['0'];
                $lms_data['lms_leads_status_disQualified'] = $lms_leads_status_disQualified['0']['0'];
                $lms_data['lms_leads_status_appointment_missed'] = $lms_leads_status_appointment_missed['0']['0'];
               }

               //Call Funnel new 
               {
                   //Get Leads where status is not known
                   $lms_leads_status_notknown_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_notknown_new_stmt->execute();
                   $lms_leads_status_notknown_new = $lms_leads_status_notknown_new_stmt->fetchAll();
   
                   //Get Leads where status is Qualified
                   $lms_leads_status_qualified_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_qualified_new_stmt->execute();
                   $lms_leads_status_qualified_new = $lms_leads_status_qualified_new_stmt->fetchAll();
   
                   //Get Leads where status is Dis Qualified
                   $lms_leads_status_disQualified_new_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_disQualified_new_stmt->execute();
                   $lms_leads_status_disQualified_new = $lms_leads_status_disQualified_new_stmt->fetchAll();
   
                   //Get Leads where status is Conversions
                   $lms_leads_status_conversions_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                   // dd($lms_leads_status_conversions_stmt);
                   $lms_leads_status_conversions_new_stmt->execute();
                   $lms_leads_status_conversions_new = $lms_leads_status_conversions_new_stmt->fetchAll();
                   $lms_data['lms_leads_status_conversions_new'] = $lms_leads_status_conversions_new['0']['0'];
   
                   //Get Leads where status is Appointment Missed
                   $lms_leads_status_appointment_missed_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_appointment_missed_new_stmt->execute();
                   $lms_leads_status_appointment_missed_new = $lms_leads_status_appointment_missed_new_stmt->fetchAll();
   
                   //Get Leads where status is Appointment Visit
                   $lms_leads_status_appointment_visit_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_appointment_visit_new_stmt->execute();
                   $lms_leads_status_appointment_visit_new = $lms_leads_status_appointment_visit_new_stmt->fetchAll();
                   $lms_data['lms_leads_status_appointment_visit_new'] = $lms_leads_status_appointment_visit_new['0']['0'];
   
                   //Get Leads where status is Appointment Schedule
                   $lms_leads_status_appointment_schedule_new_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ;");
                   $lms_leads_status_appointment_schedule_new_stmt->execute();
                   $lms_leads_status_appointment_schedule_new = $lms_leads_status_appointment_schedule_new_stmt->fetchAll();
                   $lms_data['lms_leads_status_appointment_schedule_new'] = $lms_leads_status_appointment_schedule_new['0']['0'];
   
                   //Get Leads where status is Appointment Visit not in Schedule
                   $appointment_schedule_ids_new_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                   
                   $appointment_schedule_ids_new_stmt->execute();
                   $appointment_schedule_ids_new = $appointment_schedule_ids_new_stmt->fetchAll();
                   // dd($appointment_schedule_ids);
                   $appointment_schedule_array = [];
                   foreach($appointment_schedule_ids_new as $key => $lead){
                       $appointment_schedule_array[] = $lead['id'];
                   }
                   $implode_appointment_schedule_new = implode(',', $appointment_schedule_array);
   
                   //appointment_visit not in Schedule
                   if($implode_appointment_schedule_new != ''){
   
                       $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_new.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");                    
                       $appointment_visit_new_stmt->execute();
                       $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
                       $appointment_visit_Ucount_new =  $appointment_visit_new['0']['count'] ;
                       
                       //AV IDS Not in AS
                       $appointment_visit_IDSNINAS_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                       $appointment_visit_IDSNINAS_new_stmt->execute();
                       $appointment_visit_IDSNINAS_new = $appointment_visit_IDSNINAS_new_stmt->fetchAll();
   
                       $appointment_visit_IDSNINAS_array = [];
                       foreach($appointment_visit_IDSNINAS_new as $key => $lead){
                           $appointment_visit_IDSNINAS_array[] = $lead['id'];
                       }
                       $implode_appointment_visit_IDSNINAS_new = implode(',', $appointment_visit_IDSNINAS_array);
                   
                   }else{
                       $appointment_visit_Ucount_new = 0 ;
                       $implode_appointment_visit_IDSNINAS_new = '';
                   }
   
                    //Get Leads where status Conversions not in Appointment Visit 
                    $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_new_stmt->execute();
                    $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
   
                    $appointment_visit_array = [];
                    foreach($appointment_visit_new as $key => $lead){
                        $appointment_visit_array[] = $lead['id'];
                    }
                    $implode_appointment_visit_new = implode(',', $appointment_visit_array);
   
                   //conversions not in visit
   
                   if($implode_appointment_visit_new != ''){
                       $conversions_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." and leads.id Not IN (".$implode_appointment_visit_new.") ORDER BY status_logs.created_at DESC;");
                       $conversions_new_stmt->execute();
                       $conversions_new = $conversions_new_stmt->fetchAll();
                       $conversions_Ucount_new =  $conversions_new['0']['count'] ;
   
                       // conversions IDS Not in AV
                       $conversions_IDSNINAV_new_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_new_calls." and leads.id Not IN (".$implode_appointment_visit_new.") ORDER BY status_logs.created_at DESC;");
                       $conversions_IDSNINAV_new_stmt->execute();
                       $conversions_IDSNINAV_new = $conversions_IDSNINAV_new_stmt->fetchAll();
   
                       $conversions_IDSNINAV_array = [];
                       foreach($conversions_IDSNINAV_new as $key => $lead){
                           $conversions_IDSNINAV_array[] = $lead['id'];
                       }
                       $implode_conversions_IDSNINAV_new = implode(',', $conversions_IDSNINAV_array);
                   }else{
                       $conversions_Ucount_new = $lms_data['lms_leads_status_conversions_new'];
                       $implode_conversions_IDSNINAV_new = '';
                   }
   
                   //appointment_confirmation not in AV
   
                   $appointment_confirmation_new_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_new_calls." and status_logs.field_change = '0' Group By calls.lead_id;");
                   $appointment_confirmation_new_stmt->execute();
                   $appointment_confirmation_ids_new = $appointment_confirmation_new_stmt->fetchAll();
       
                   $appointment_confirmation_array = [];
                   foreach($appointment_confirmation_ids_new as $key => $lead){
                       $appointment_confirmation_array[] = $lead['id'];
                   }
                   $implode_appointment_confirmation_new = implode(',', $appointment_confirmation_array);
   
                   if($implode_appointment_confirmation_new != "")
                   {                    
                       if($implode_conversions_IDSNINAV_new != "" ){
                           
                           $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new,$implode_conversions_IDSNINAV_new]);
                       }
                       if($implode_appointment_visit_IDSNINAS_new != ""){
                           
                           $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new, $implode_appointment_visit_IDSNINAS_new]);
                       }
                   }else{
                       if($implode_conversions_IDSNINAV_new != "" ){
                           
                           $implode_appointment_confirmation_new = $implode_conversions_IDSNINAV_new;
                       }else if($implode_appointment_visit_IDSNINAS_new != ""){
                           
                           $implode_appointment_confirmation_new = $implode_appointment_visit_IDSNINAS_new;
                       }
                       if($implode_appointment_visit_IDSNINAS_new != "" && $implode_conversions_IDSNINAV_new == ""){
                           
                           $implode_appointment_confirmation_new = implode(',', [$implode_appointment_confirmation_new, $implode_appointment_visit_IDSNINAS_new]);
                       }
                   }
   
                   //appointment_visit not in Schedule
   
                   if($implode_appointment_confirmation_new != ''){
                       $appointment_visit_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation_new.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                       // dd($appointment_visit_stmt);
   
                       $appointment_visit_new_stmt->execute();
                       $appointment_visit_new = $appointment_visit_new_stmt->fetchAll();
   
                       $appointment_visit_count_NotIN_AC_new =  $appointment_visit_new['0']['count'] ;
                   }else{
                       $appointment_visit_count_NotIN_AC_new = 0;
                   }
   
                   $lms_data['lms_leads_status_appointment_schedule_new'] = $lms_data['lms_leads_status_appointment_schedule_new'] + $appointment_visit_Ucount_new + $conversions_Ucount_new + $appointment_visit_count_NotIN_AC_new;
   
                   $lms_data['lms_leads_status_appointment_visit_new'] = $lms_data['lms_leads_status_appointment_visit_new'] + $conversions_Ucount_new;
   
                   //Get Leads where status is Interested
                   $lms_leads_status_interested_new_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition_new_calls." ORDER BY status_logs.created_at DESC;");
                   $lms_leads_status_interested_new_stmt->execute();
                   $lms_leads_status_interested_new = $lms_leads_status_interested_new_stmt->fetchAll();
                   $lms_data['lms_leads_status_interested_new'] = $lms_leads_status_interested_new['0']['0'] + $lms_data['lms_leads_status_appointment_schedule_new'];
   
                   //Get Leads where status is Confirmation
                   $lms_leads_status_appointment_confirmation_new_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_new_calls." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                   $lms_leads_status_appointment_confirmation_new_stmt->execute();
                   $lms_leads_status_appointment_confirmation_new = $lms_leads_status_appointment_confirmation_new_stmt->fetchAll();
                   
                   $lms_data['lms_leads_status_appointment_confirmation_new'] = $lms_leads_status_appointment_confirmation_new['0']['count'] + $appointment_visit_Ucount_new + $conversions_Ucount_new + $appointment_visit_count_NotIN_AC_new;

                   $lms_data['lms_leads_status_notknown_new'] = $lms_leads_status_notknown_new['0']['0'];
                   $lms_data['lms_leads_status_qualified_new'] = $lms_leads_status_qualified_new['0']['0'];
                   $lms_data['lms_leads_status_disQualified_new'] = $lms_leads_status_disQualified_new['0']['0'];
                   $lms_data['lms_leads_status_appointment_missed_new'] = $lms_leads_status_appointment_missed_new['0']['0'];
               }

               //Call Funnel old
               {
                   //Get Leads where status is not known
                   $lms_leads_status_notknown_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'notknows' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_notknown_old_stmt->execute();
                   $lms_leads_status_notknown_old = $lms_leads_status_notknown_old_stmt->fetchAll();
   
                   //Get Leads where status is Qualified
                   $lms_leads_status_qualified_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'qualified' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_qualified_old_stmt->execute();
                   $lms_leads_status_qualified_old = $lms_leads_status_qualified_old_stmt->fetchAll();
   
                   //Get Leads where status is Dis Qualified
                   $lms_leads_status_disQualified_old_stmt = $lms_pdo->prepare("SELECT Count(calls.id) FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_statuses on leads.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.is_stage = 'disqualified' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_disQualified_old_stmt->execute();
                   $lms_leads_status_disQualified_old = $lms_leads_status_disQualified_old_stmt->fetchAll();
   
                   //Get Leads where status is Conversions
                   $lms_leads_status_conversions_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                   // dd($lms_leads_status_conversions_stmt);
                   $lms_leads_status_conversions_old_stmt->execute();
                   $lms_leads_status_conversions_old = $lms_leads_status_conversions_old_stmt->fetchAll();
                   $lms_data['lms_leads_status_conversions_old'] = $lms_leads_status_conversions_old['0']['0'];
   
                   //Get Leads where status is Appointment Missed
                   $lms_leads_status_appointment_missed_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->missed_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_appointment_missed_old_stmt->execute();
                   $lms_leads_status_appointment_missed_old = $lms_leads_status_appointment_missed_old_stmt->fetchAll();
   
                   //Get Leads where status is Appointment Visit
                   $lms_leads_status_appointment_visit_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_appointment_visit_old_stmt->execute();
                   $lms_leads_status_appointment_visit_old = $lms_leads_status_appointment_visit_old_stmt->fetchAll();
                   $lms_data['lms_leads_status_appointment_visit_old'] = $lms_leads_status_appointment_visit_old['0']['0'];
   
                   //Get Leads where status is Appointment Schedule
                   $lms_leads_status_appointment_schedule_old_stmt = $lms_pdo->prepare("SELECT Count(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ;");
                   $lms_leads_status_appointment_schedule_old_stmt->execute();
                   $lms_leads_status_appointment_schedule_old = $lms_leads_status_appointment_schedule_old_stmt->fetchAll();
                   $lms_data['lms_leads_status_appointment_schedule_old'] = $lms_leads_status_appointment_schedule_old['0']['0'];
   
                   //Get Leads where status is Appointment Visit not in Schedule
                   $appointment_schedule_ids_old_stmt = $lms_pdo->prepare("SELECT  status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->scheduled_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                   
                   $appointment_schedule_ids_old_stmt->execute();
                   $appointment_schedule_ids_old = $appointment_schedule_ids_old_stmt->fetchAll();
                   // dd($appointment_schedule_ids);
                   $appointment_schedule_array = [];
                   foreach($appointment_schedule_ids_old as $key => $lead){
                       $appointment_schedule_array[] = $lead['id'];
                   }
                   $implode_appointment_schedule_old = implode(',', $appointment_schedule_array);
   
                   //appointment_visit not in Schedule
                   if($implode_appointment_schedule_old != ''){
   
                       $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");                    
                       $appointment_visit_old_stmt->execute();
                       $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
                       $appointment_visit_Ucount_old =  $appointment_visit_old['0']['count'] ;
                       
                       //AV IDS Not in AS
                       $appointment_visit_IDSNINAS_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.lead_id Not IN (".$implode_appointment_schedule_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                       $appointment_visit_IDSNINAS_old_stmt->execute();
                       $appointment_visit_IDSNINAS_old = $appointment_visit_IDSNINAS_old_stmt->fetchAll();
   
                       $appointment_visit_IDSNINAS_array = [];
                       foreach($appointment_visit_IDSNINAS_old as $key => $lead){
                           $appointment_visit_IDSNINAS_array[] = $lead['id'];
                       }
                       $implode_appointment_visit_IDSNINAS_old = implode(',', $appointment_visit_IDSNINAS_array);
                   
                   }else{
                       $appointment_visit_Ucount_old = 0 ;
                       $implode_appointment_visit_IDSNINAS_old = '';
                   }
   
                    //Get Leads where status Conversions not in Appointment Visit 
                    $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->visited_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                    $appointment_visit_old_stmt->execute();
                    $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
   
                    $appointment_visit_array = [];
                    foreach($appointment_visit_old as $key => $lead){
                        $appointment_visit_array[] = $lead['id'];
                    }
                    $implode_appointment_visit_old = implode(',', $appointment_visit_array);
   
                   //conversions not in visit
   
                   if($implode_appointment_visit_old != ''){
                       $conversions_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted = 'yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." and leads.id Not IN (".$implode_appointment_visit_old.") ORDER BY status_logs.created_at DESC;");
                       $conversions_old_stmt->execute();
                       $conversions_old = $conversions_old_stmt->fetchAll();
                       $conversions_Ucount_old =  $conversions_old['0']['count'] ;
   
                       // conversions IDS Not in AV
                       $conversions_IDSNINAV_old_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.converted='yes' and status_logs.field_change = '0' ".$Callback_condition_old_calls." and leads.id Not IN (".$implode_appointment_visit_old.") ORDER BY status_logs.created_at DESC;");
                       $conversions_IDSNINAV_old_stmt->execute();
                       $conversions_IDSNINAV_old = $conversions_IDSNINAV_old_stmt->fetchAll();
   
                       $conversions_IDSNINAV_array = [];
                       foreach($conversions_IDSNINAV_old as $key => $lead){
                           $conversions_IDSNINAV_array[] = $lead['id'];
                       }
                       $implode_conversions_IDSNINAV_old = implode(',', $conversions_IDSNINAV_array);
                   }else{
                       $conversions_Ucount_old = $lms_data['lms_leads_status_conversions_old'];
                       $implode_conversions_IDSNINAV_old = '';
                   }
   
                   //appointment_confirmation not in AV
   
                   $appointment_confirmation_old_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE `calls`.`status` = 0 and calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_old_calls." and status_logs.field_change = '0' Group By calls.lead_id;");
                   $appointment_confirmation_old_stmt->execute();
                   $appointment_confirmation_ids_old = $appointment_confirmation_old_stmt->fetchAll();
       
                   $appointment_confirmation_array = [];
                   foreach($appointment_confirmation_ids_old as $key => $lead){
                       $appointment_confirmation_array[] = $lead['id'];
                   }
                   $implode_appointment_confirmation_old = implode(',', $appointment_confirmation_array);
   
                   if($implode_appointment_confirmation_old != "")
                   {                    
                       if($implode_conversions_IDSNINAV_old != "" ){
                           
                           $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old,$implode_conversions_IDSNINAV_old]);
                       }
                       if($implode_appointment_visit_IDSNINAS_old != ""){
                           
                           $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old, $implode_appointment_visit_IDSNINAS_old]);
                       }
                   }else{
                       if($implode_conversions_IDSNINAV_old != "" ){
                           
                           $implode_appointment_confirmation_old = $implode_conversions_IDSNINAV_old;
                       }else if($implode_appointment_visit_IDSNINAS_old != ""){
                           
                           $implode_appointment_confirmation_old = $implode_appointment_visit_IDSNINAS_old;
                       }
                       if($implode_appointment_visit_IDSNINAS_old != "" && $implode_conversions_IDSNINAV_old == ""){
                           
                           $implode_appointment_confirmation_old = implode(',', [$implode_appointment_confirmation_old, $implode_appointment_visit_IDSNINAS_old]);
                       }
                   }
   
                   //appointment_visit not in Schedule
   
                   if($implode_appointment_confirmation_old != ''){
                       $appointment_visit_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation_old.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                       // dd($appointment_visit_stmt);
   
                       $appointment_visit_old_stmt->execute();
                       $appointment_visit_old = $appointment_visit_old_stmt->fetchAll();
   
                       $appointment_visit_count_NotIN_AC_old =  $appointment_visit_old['0']['count'] ;
                   }else{
                       $appointment_visit_count_NotIN_AC_old = 0;
                   }
   
                   $lms_data['lms_leads_status_appointment_schedule_old'] = $lms_data['lms_leads_status_appointment_schedule_old'] + $appointment_visit_Ucount_old + $conversions_Ucount_old + $appointment_visit_count_NotIN_AC_old;
   
                   $lms_data['lms_leads_status_appointment_visit_old'] = $lms_data['lms_leads_status_appointment_visit_old'] + $conversions_Ucount_old;
   
                   //Get Leads where status is Interested
                   $lms_leads_status_interested_old_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) FROM `calls` INNER join leads on leads.id = calls.lead_id INNER JOIN status_logs ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs.lead_status_id = lead_statuses.id WHERE `calls`.`status` = 0 and lead_statuses.slug IN (".$this->client->interested_slug.") and status_logs.field_change = '0' ".$Callback_condition_old_calls." ORDER BY status_logs.created_at DESC;");
                   $lms_leads_status_interested_old_stmt->execute();
                   $lms_leads_status_interested_old = $lms_leads_status_interested_old_stmt->fetchAll();
                   $lms_data['lms_leads_status_interested_old'] = $lms_leads_status_interested_old['0']['0'] + $lms_data['lms_leads_status_appointment_schedule_old'];
   
                   //Get Leads where status is Confirmation
                   $lms_leads_status_appointment_confirmation_old_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls LEFT JOIN status_logs ON status_logs.lead_id = calls.lead_id LEFT JOIN leads ON leads.id = calls.lead_id WHERE calls.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) and status_logs.lead_status_id IN ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN (".$this->client->scheduled_slug.") ) ".$Callback_condition_old_calls." and status_logs.field_change = '0' Group By calls.lead_id) AS subquery;");
                   $lms_leads_status_appointment_confirmation_old_stmt->execute();
                   $lms_leads_status_appointment_confirmation_old = $lms_leads_status_appointment_confirmation_old_stmt->fetchAll();
                   
                   $lms_data['lms_leads_status_appointment_confirmation_old'] = $lms_leads_status_appointment_confirmation_old['0']['count'] + $appointment_visit_Ucount_old + $conversions_Ucount_old + $appointment_visit_count_NotIN_AC_old;

                   $lms_data['lms_leads_status_notknown_old'] = $lms_leads_status_notknown_old['0']['0'];
                   $lms_data['lms_leads_status_qualified_old'] = $lms_leads_status_qualified_old['0']['0'];
                   $lms_data['lms_leads_status_disQualified_old'] = $lms_leads_status_disQualified_old['0']['0'];
                   $lms_data['lms_leads_status_appointment_missed_old'] = $lms_leads_status_appointment_missed_old['0']['0'];
               }
    
                $Efficiency = 0;

                $lms_product_call_stmt = $lms_pdo->prepare("SELECT leads.product_id ,COUNT(*) AS product_count FROM `calls` INNER join leads on leads.id = calls.lead_id WHERE calls.status = 0  ".$condition." GROUP BY leads.product_id;");
                $lms_product_call_stmt->execute();
                $lms_product_call = $lms_product_call_stmt->fetchAll();

                if(!empty($lms_product_call)){
                    $max_product_count = -1; 
                    $max_product_id = null;

                    foreach ($lms_product_call as $item) {
                        if ($item['product_count'] > $max_product_count) {
                            $max_product_count = $item['product_count'];
                            $max_product_id = $item['product_id'];
                        }
                    }
                    
                    $lms_product_stmt = $lms_pdo->prepare("SELECT name FROM `products` WHERE `id` = '$max_product_id';");
                    $lms_product_stmt->execute();
                    $lms_product_data = $lms_product_stmt->fetchAll();
                    $lms_product = $lms_product_data['0']['name'];
                }

                $lms_source_call_stmt = $lms_pdo->prepare("SELECT lead_sources.name, leads.lead_source_id ,COUNT(*) AS source_count FROM `calls` INNER join leads on leads.id = calls.lead_id inner join lead_sources on leads.lead_source_id = lead_sources.id WHERE calls.status = 0  ".$condition." GROUP BY leads.lead_source_id;");
                $lms_source_call_stmt->execute();
                $lms_source_call = $lms_source_call_stmt->fetchAll();
                $lms_data['lms_source_call'] = $lms_source_call;

                if(!empty($lms_source_call)){
                    $max_source_count = -1; 
                    $max_source_id = null;
                    foreach ($lms_source_call as $item) {
                        if ($item['source_count'] > $max_source_count) {
                            $max_source_count = $item['source_count'];
                            $max_source_id = $item['lead_source_id'];
                        }
                    }

                    $lms_source_stmt = $lms_pdo->prepare("SELECT name FROM `lead_sources` WHERE `id` = '$max_source_id';");
                    $lms_source_stmt->execute();
                    $lms_source_data = $lms_source_stmt->fetchAll();

                    $lms_source = $lms_source_data['0']['name'];
                }

             
                $lms_data['number_new_calls_field_change'] = 0;
                $lms_data['number_new_calls_state_change'] = 0;
                $lms_data['number_old_calls_field_change'] = 0;
                $lms_data['number_old_calls_state_change'] = 0;
                $lms_data['overall_call'] = true;
        
            }else {
                $lms_data['lms_leads_status_notknown'] = 0;
                $lms_data['lms_leads_status_qualified'] = 0;
                $lms_data['lms_leads_status_disQualified'] = 0;
                $lms_data['lms_leads_status_conversions'] = 0;
                $lms_data['lms_leads_status_appointment_missed'] = 0;
                $lms_data['lms_leads_status_interested'] = 0;
                $lms_data['lms_leads_status_appointment_confirmation'] = 0;
                $lms_data['lms_leads_status_appointment_schedule'] = 0;
                $lms_data['lms_leads_status_appointment_visit'] = 0;
                $lms_data['number_new_calls_field_change'] = 0;
                $lms_data['number_new_calls_state_change'] = 0;
                $lms_data['number_old_calls_field_change'] = 0;
                $lms_data['number_old_calls_state_change'] = 0;
                $lms_data['lms_source_call'] = null;

                $lms_data['lms_leads_status_notknown_new'] = 0;
                $lms_data['lms_leads_status_qualified_new'] = 0;
                $lms_data['lms_leads_status_disQualified_new'] = 0;
                $lms_data['lms_leads_status_conversions_new'] = 0;
                $lms_data['lms_leads_status_appointment_missed_new'] = 0;
                $lms_data['lms_leads_status_interested_new'] = 0;
                $lms_data['lms_leads_status_appointment_confirmation_new'] = 0;
                $lms_data['lms_leads_status_appointment_schedule_new'] = 0;
                $lms_data['lms_leads_status_appointment_visit_new'] = 0;

                $lms_data['lms_leads_status_notknown_old'] = 0;
                $lms_data['lms_leads_status_qualified_old'] = 0;
                $lms_data['lms_leads_status_disQualified_old'] = 0;
                $lms_data['lms_leads_status_conversions_old'] = 0;
                $lms_data['lms_leads_status_appointment_missed_old'] = 0;
                $lms_data['lms_leads_status_interested_old'] = 0;
                $lms_data['lms_leads_status_appointment_confirmation_old'] = 0;
                $lms_data['lms_leads_status_appointment_schedule_old'] = 0;
                $lms_data['lms_leads_status_appointment_visit_old'] = 0;
                $lms_data['overall_call'] = false;

            }
           
           

            $lms_data['number_calls_made'] = $lms_complete_call['0']['0'];
            $lms_data['number_new_calls_made'] = $lms_complete_new_call['0']['0'];

            $lms_data['number_old_calls_made'] = $lms_complete_old_call['0']['0'];

            $lms_data['working_days'] = $working_days;
            $lms_data['Efficiency'] = $Efficiency;
            $lms_data['lms_source'] = $lms_source;
            $lms_data['lms_product'] = $lms_product;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['lms_client_name'] = $this->client->name;
            $lms_data['filter_telecaller_arr'] = $filter_telecaller_arr;
            // dd($lms_data);

            // dd($lms_data);
        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.lms.lms-telecaller", compact("lms_data"));
    }

    public function get_lms_leads_log_data(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        try {
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
            //  $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            
            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                
                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
           
            }else{
                $selected_sources = null;
                $implode_selected_sources = '';
                $filter_arr['filter_source'] = null;
            }
            
            // filter condition for leads
            $condition = "";

            // filter condition for leads log
            $logcondition = "";
            $log1condition = "";
            $duplicatelogcondition = "";

            $created_date_filter = date('Y-m-d');

            // Creating sql condition for filter_telecaller_arr according to dates and stages
            if(isset($_POST['lms_stages']) && !empty($_POST['lms_stages'])){
                $implode_selected_stages = implode(',', $_POST['lms_stages']);
                $filter_arr['filter_stage'] = $_POST['lms_stages'];
                $condition .= " and leads.lead_status_id IN (".$implode_selected_stages.") ";
                $logcondition .= " and status_logs.lead_status_id  IN (".$implode_selected_stages.") ";
            }

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

                $dates = explode(' to ', $_POST['lms_daterange']);
                $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($dates[1] .' +1 day'));

                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $logcondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
                $log1condition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
                $duplicatelogcondition .= " and duplicateleads.created_at >= '".$start_date."' and duplicateleads.created_at <= '".$end_date."'";
            }else{
                $lms_data['lms_daterange'] = null;
            }

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;

            //Get Today Lead Count
            $status_field_lead_arr = array();
            $status_sources_lead_arr = array();
            $status_sources_lead_gradeA_arr = array();
            $status_sources_lead_gradeB_arr = array();
            $status_sources_lead_gradeC_arr = array();
            $status_sources_lead_gradeD_arr = array();
            $status_stages_lead_sources_arr = array();
            $status_log_arr = [];

            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){

                foreach($selected_sources as $key => $source){
                
                    // For Status Log Lead Count for field change
                    $lms_status_log_field = $lms_pdo->prepare("SELECT lead_sources.name as source FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_sources ON leads.lead_source_id = lead_sources.id WHERE leads.lead_source_id ='".$source."' and status_logs.field_change = 1 ".$logcondition." GROUP BY status_logs.lead_id;");
                    $lms_status_log_field->execute();
                    $lms_status_field = $lms_status_log_field->fetch();
                    if ($lms_status_field) {
                        $status_field_lead_arr[] = array('source' => $lms_status_field['source'], 'total_field_count' => $lms_status_log_field->rowCount());
                    } else {
                        $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                        $sourceName->execute();
                        $source_name = $sourceName->fetch();
                        $status_field_lead_arr[] = array('source' => $source_name['name'], 'total_field_count' => 0);
                    }

                    // For Status Log Lead Count for states
                    $lms_status_log_sources = $lms_pdo->prepare("SELECT lead_sources.name as source FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_sources ON leads.lead_source_id = lead_sources.id WHERE leads.lead_source_id ='".$source."' and status_logs.field_change = 0 ".$logcondition." GROUP BY status_logs.lead_id;");
                    $lms_status_log_sources->execute();
                    $lms_status_sources = $lms_status_log_sources->fetch();

                    if ($lms_status_sources) {
                        $status_sources_lead_arr[] = array('source' => $lms_status_sources['source'], 'total_stage_count' => $lms_status_log_sources->rowCount());
                        $status_sources_lead_gradeA_arr[] = array('source' => $lms_status_sources['source'], 'total_stage_count' => $lms_status_log_sources->rowCount());
                        // dd($status_sources_lead_gradeA_arr);
                        $status_sources_lead_gradeB_arr[] = array('source' => $lms_status_sources['source'], 'total_stage_count' => $lms_status_log_sources->rowCount());
                        $status_sources_lead_gradeC_arr[] = array('source' => $lms_status_sources['source'], 'total_stage_count' => $lms_status_log_sources->rowCount());
                        $status_sources_lead_gradeD_arr[] = array('source' => $lms_status_sources['source'], 'total_stage_count' => $lms_status_log_sources->rowCount());

                    } else {
                        $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                        $sourceName->execute();
                        $source_name = $sourceName->fetch();

                        $status_sources_lead_arr[] = array('source' => $source_name['name'], 'total_stage_count' => 0);
                        $status_sources_lead_gradeA_arr[] = array('source' => $source_name['name'], 'total_stage_count' => 0);
                        $status_sources_lead_gradeB_arr[] = array('source' => $source_name['name'], 'total_stage_count' => 0);
                        $status_sources_lead_gradeC_arr[] = array('source' => $source_name['name'], 'total_stage_count' => 0);
                        $status_sources_lead_gradeD_arr[] = array('source' => $source_name['name'], 'total_stage_count' => 0);
                    }

                    //When Stage filter is not apply

                    if(!isset($_POST['lms_stages']) && empty($_POST['lms_stages'])){

                        // Stage wise count
                        $lms_status_stage_count = $lms_pdo->prepare("SELECT stage, COUNT(*) AS count FROM (SELECT lead_statuses.name as stage, COUNT(*) as count FROM status_logs LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id LEFT JOIN leads ON status_logs.lead_id = leads.id WHERE leads.lead_source_id = :source AND status_logs.field_change = 0 ".$log1condition." GROUP BY status_logs.lead_id) AS subquery GROUP BY stage;");
                        $lms_status_stage_count->bindParam(':source', $source, PDO::PARAM_STR);
                        $lms_status_stage_count->execute();
                        $flagstage = 0;
                        
                        while ($row = $lms_status_stage_count->fetch(PDO::FETCH_ASSOC)) {
                            // Process each 
                            $flagstage++;
                            $status_sources_lead_arr[$key][$row['stage']] = array('count' => $row['count']);
                        }
                        $status_sources_lead_arr[$key]['total_Stages'] = array('total_Stages' => $flagstage);

                        //Stage wise and grade wise count 

                        //Grade A
                        $lms_status_stage_gradeA_count = $lms_pdo->prepare("SELECT name, COUNT(*) AS count FROM (SELECT lead_statuses.name as name, COUNT(*) as count FROM status_logs LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id LEFT JOIN leads ON status_logs.lead_id = leads.id WHERE leads.lead_source_id = :source AND status_logs.field_change = 0 AND leads.lead_type_id = 6 ".$log1condition." GROUP BY status_logs.lead_id) AS subquery GROUP BY name;");
                        $lms_status_stage_gradeA_count->bindParam(':source', $source, PDO::PARAM_STR);
                        $lms_status_stage_gradeA_count->execute();
                        $flagstage = 0;

                        while ($row = $lms_status_stage_gradeA_count->fetch(PDO::FETCH_ASSOC)) {
                            // Process each 
                            $flagstage++;
                            $status_sources_lead_gradeA_arr[$key][$row['name']] = array('count' => $row['count']);
                        }
                        $status_sources_lead_gradeA_arr[$key]['total_Stages'] = array('total_Stages' => $flagstage);

                        //Grade B
                        $lms_status_stage_gradeB_count = $lms_pdo->prepare("SELECT name, COUNT(*) AS count FROM (SELECT lead_statuses.name as name, COUNT(*) as count FROM status_logs LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id LEFT JOIN leads ON status_logs.lead_id = leads.id WHERE leads.lead_source_id = :source AND status_logs.field_change = 0 AND leads.lead_type_id = 7 ".$log1condition." GROUP BY status_logs.lead_id) AS subquery GROUP BY name;");
                        $lms_status_stage_gradeB_count->bindParam(':source', $source, PDO::PARAM_STR);
                        $lms_status_stage_gradeB_count->execute();
                        $flagstage = 0;

                        while ($row = $lms_status_stage_gradeB_count->fetch(PDO::FETCH_ASSOC)) {
                            // Process each 
                            $flagstage++;
                            $status_sources_lead_gradeB_arr[$key][$row['name']] = array('count' => $row['count']);
                        }
                        $status_sources_lead_gradeB_arr[$key]['total_Stages'] = array('total_Stages' => $flagstage);

                        //Grade C
                        $lms_status_stage_gradeC_count = $lms_pdo->prepare("SELECT name, COUNT(*) AS count FROM (SELECT lead_statuses.name as name, COUNT(*) as count FROM status_logs LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id LEFT JOIN leads ON status_logs.lead_id = leads.id WHERE leads.lead_source_id = :source AND status_logs.field_change = 0 AND leads.lead_type_id = 8 ".$log1condition." GROUP BY status_logs.lead_id) AS subquery GROUP BY name;");
                        $lms_status_stage_gradeC_count->bindParam(':source', $source, PDO::PARAM_STR);
                        $lms_status_stage_gradeC_count->execute();
                        $flagstage = 0;

                        while ($row = $lms_status_stage_gradeC_count->fetch(PDO::FETCH_ASSOC)) {
                            // Process each 
                            $flagstage++;
                            $status_sources_lead_gradeC_arr[$key][$row['name']] = array('count' => $row['count']);
                        }
                        $status_sources_lead_gradeC_arr[$key]['total_Stages'] = array('total_Stages' => $flagstage);

                        //Grade D
                        $lms_status_stage_gradeD_count = $lms_pdo->prepare("SELECT name, COUNT(*) AS count FROM (SELECT lead_statuses.name as name, COUNT(*) as count FROM status_logs LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id LEFT JOIN leads ON status_logs.lead_id = leads.id WHERE leads.lead_source_id = :source AND status_logs.field_change = 0 AND leads.lead_type_id = 9 ".$log1condition." GROUP BY status_logs.lead_id) AS subquery GROUP BY name;");
                        $lms_status_stage_gradeD_count->bindParam(':source', $source, PDO::PARAM_STR);
                        $lms_status_stage_gradeD_count->execute();
                        $flagstage = 0;
    
                        while ($row = $lms_status_stage_gradeD_count->fetch(PDO::FETCH_ASSOC)) {
                            // Process each 
                            $flagstage++;
                            $status_sources_lead_gradeD_arr[$key][$row['name']] = array('count' => $row['count']);
                        }
                        $status_sources_lead_gradeD_arr[$key]['total_Stages'] = array('total_Stages' => $flagstage);

                    }

                    // For Status Log Lead Count for when state filter
                    if(isset($_POST['lms_stages']) && !empty($_POST['lms_stages'])){
                        foreach($_POST['lms_stages'] as $stage){

                            // Stage wise count
                            $lms_stage_log = $lms_pdo->prepare("SELECT COUNT(*) AS total_count, source, stage FROM ( SELECT lead_sources.name as source, lead_statuses.name as stage, status_logs.lead_status_id, status_logs.field_change FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_sources ON status_logs.lead_source_id = lead_sources.id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE status_logs.lead_source_id = '".$source."' and status_logs.field_change = 0  and status_logs.lead_status_id = '".$stage."'  GROUP BY status_logs.lead_id, lead_sources.name,lead_statuses.name) AS subquery GROUP BY source, stage;");
                            $lms_stage_log->execute();
                            $lms_stage_sources = $lms_stage_log->fetch();

                            if ($lms_stage_sources) {
                                $status_stages_lead_sources_arr[$lms_status_sources['source']][] = array('stage' => $lms_stage_sources['stage'], 'total_count' => $lms_stage_sources['total_count']);
                            } else {
                                $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                                $sourceName->execute();
                                $source_name = $sourceName->fetch();

                                $stateName = $lms_pdo->prepare("SELECT name FROM lead_statuses WHERE id = '".$stage."';");
                                $stateName->execute();
                                $state_name = $stateName->fetch();
                
                                $status_stages_lead_sources_arr[$source_name['name']][] = array('stage' => $state_name['name'], 'total_count' => 0);
                            }

                            //Stage wise and grade wise count 
                            //Grade A
                            $lms_status_stage_gradeA_count = $lms_pdo->prepare("SELECT lead_statuses.name, count(status_logs.lead_status_id) as count FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE status_logs.lead_source_id = '".$source."' AND status_logs.field_change = 0 and leads.lead_type_id = 6 AND status_logs.lead_status_id = '".$stage."' GROUP BY leads.lead_type_id, status_logs.lead_status_id;");
                            $lms_status_stage_gradeA_count->execute();
                            $lms_status_stage_gradeA_count = $lms_status_stage_gradeA_count->fetch();

                            if ($lms_status_stage_gradeA_count) {
                                $status_sources_lead_gradeA_arr[$key][$lms_status_stage_gradeA_count['name']] = array( 'count' => $lms_status_stage_gradeA_count['count']);
                            }else {
                                $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                                $sourceName->execute();
                                $source_name = $sourceName->fetch();

                                $stateName = $lms_pdo->prepare("SELECT name FROM lead_statuses WHERE id = '".$stage."';");
                                $stateName->execute();
                                $state_name = $stateName->fetch();
                
                                $status_sources_lead_gradeA_arr[$key][$state_name['name']]= array( 'count' => 0);
                            }

                            //Grade B
                            $lms_status_stage_gradeB_count = $lms_pdo->prepare("SELECT lead_statuses.name, count(status_logs.lead_status_id) as count FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE status_logs.lead_source_id = '".$source."' AND status_logs.field_change = 0 and leads.lead_type_id = 7 AND status_logs.lead_status_id = '".$stage."' GROUP BY leads.lead_type_id, status_logs.lead_status_id;");
                            $lms_status_stage_gradeB_count->execute();
                            $lms_status_stage_gradeB_count = $lms_status_stage_gradeB_count->fetch();

                            if ($lms_status_stage_gradeB_count) {
                                $status_sources_lead_gradeB_arr[$key][$lms_status_stage_gradeB_count['name']] = array( 'count' => $lms_status_stage_gradeB_count['count']);
                            } else {
                                $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                                $sourceName->execute();
                                $source_name = $sourceName->fetch();

                                $stateName = $lms_pdo->prepare("SELECT name FROM lead_statuses WHERE id = '".$stage."';");
                                $stateName->execute();
                                $state_name = $stateName->fetch();
                
                                $status_sources_lead_gradeB_arr[$key][$state_name['name']]= array( 'count' => 0);
                            }

                            //Grade C
                            $lms_status_stage_gradeC_count = $lms_pdo->prepare("SELECT lead_statuses.name, count(status_logs.lead_status_id) as count FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE status_logs.lead_source_id = '".$source."' AND status_logs.field_change = 0 and leads.lead_type_id = 8 AND status_logs.lead_status_id = '".$stage."' GROUP BY leads.lead_type_id, status_logs.lead_status_id;");
                            $lms_status_stage_gradeC_count->execute();
                            $lms_status_stage_gradeC_count = $lms_status_stage_gradeC_count->fetch();

                            if ($lms_status_stage_gradeC_count) {
                                $status_sources_lead_gradeC_arr[$key][$lms_status_stage_gradeC_count['name']] = array( 'count' => $lms_status_stage_gradeC_count['count']);
                            } else {
                                $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                                $sourceName->execute();
                                $source_name = $sourceName->fetch();

                                $stateName = $lms_pdo->prepare("SELECT name FROM lead_statuses WHERE id = '".$stage."';");
                                $stateName->execute();
                                $state_name = $stateName->fetch();
                
                                $status_sources_lead_gradeC_arr[$key][$state_name['name']]= array( 'count' => 0);
                            }

                            //Grade D
                            $lms_status_stage_gradeD_count = $lms_pdo->prepare("SELECT lead_statuses.name, count(status_logs.lead_status_id) as count FROM status_logs LEFT JOIN leads ON status_logs.lead_id = leads.id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE status_logs.lead_source_id = '".$source."' AND status_logs.field_change = 0 and leads.lead_type_id = 9 AND status_logs.lead_status_id = '".$stage."' GROUP BY leads.lead_type_id, status_logs.lead_status_id;");
                            $lms_status_stage_gradeD_count->execute();
                            $lms_status_stage_gradeD_count = $lms_status_stage_gradeD_count->fetch();

                            if ($lms_status_stage_gradeD_count) {
                                $status_sources_lead_gradeD_arr[$key][$lms_status_stage_gradeD_count['name']] = array( 'count' => $lms_status_stage_gradeD_count['count']);
                            } else {
                                $sourceName = $lms_pdo->prepare("SELECT name FROM lead_sources WHERE id = '".$source."';");
                                $sourceName->execute();
                                $source_name = $sourceName->fetch();

                                $stateName = $lms_pdo->prepare("SELECT name FROM lead_statuses WHERE id = '".$stage."';");
                                $stateName->execute();
                                $state_name = $stateName->fetch();
                
                                $status_sources_lead_gradeD_arr[$key][$state_name['name']]= array( 'count' => 0);
                            }
                        }
                    }
                }
                foreach ($status_field_lead_arr as $index => $assocArray) {
                    $status_log_arr[$index] = array_merge($assocArray, $status_sources_lead_arr[$index]);
                }
            }

            //data for stage filter on status log data
            $lms_data['status_stages_lead_sources_arr'] = $status_stages_lead_sources_arr;
            $lms_data['status_sources_lead_gradeA_arr'] = $status_sources_lead_gradeA_arr;
            $lms_data['status_sources_lead_gradeB_arr'] = $status_sources_lead_gradeB_arr;
            $lms_data['status_sources_lead_gradeC_arr'] = $status_sources_lead_gradeC_arr;
            $lms_data['status_sources_lead_gradeD_arr'] = $status_sources_lead_gradeD_arr;
            $lms_data['status_log_count'] = $status_log_arr;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['lms_client_name'] = $this->client->name;
            $lms_data['filters'] = $filter_arr;

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.lms.lms-grade", compact("lms_data"));
    }

    public function get_lms_data_dailyreport(Request $request)
    {
        $lms_data = array();
        $error = false;
        $lms_client_id = $this->client_id;
        $lms_url = $this->client_url;
        try {
            $_SESSION['lms_client_check'] = $lms_client_id;
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
            //  $dbName = "jindal";
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $condition = "";
            $condition_update1 = "";
            $condition_update = "";

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){

                // dd($_POST['lms_daterange']);

                if(strpos($_POST['lms_daterange'],"to") != false){

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

                $condition .= " where `leads`.`created_at` >= '".$start_date."' and `leads`.`created_at` <= '".$end_date."'";
                $condition_update1 .= " WHERE `created_at` >= '".$start_date."' and `created_at` <= '".$end_date."'";
                // $condition_update .= " where `leads`.`updated_at` >= '".$start_date."' and `leads`.`updated_at` <= '".$end_date."'";

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];
                $lms_data['lms_lead_from'] = $start_date;
                $lms_data['lms_lead_to'] = $end_date;
            }else{
                $lms_data['lms_daterange'] = null;
                date_default_timezone_set('Asia/Kolkata');

                $lead_from = date('Y-m-d 00:00:00');
                $lead_to = date('Y-m-d H:i:s');

                $condition .= " WHERE `leads`.`created_at` >= '".$lead_from."' and `leads`.`created_at` <= '".$lead_to."'";
                // $condition_update .= " where `leads`.`updated_at` >= '".$lead_from."' and `leads`.`updated_at` <= '".$lead_to."'";
                $condition_update1 .= " WHERE `created_at` >= '".$lead_from."' and `created_at` <= '".$lead_to."'";

                $lms_data['lms_lead_from'] = $lead_from;
                $lms_data['lms_lead_to'] = $lead_to;
            }

            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
                $lms_data['filters'] = $filter_arr;
                $condition .= " and `leads`.`lead_source_id` IN (".$implode_selected_sources.") ";
                $condition_update .= " and `leads`.`lead_source_id` IN (".$implode_selected_sources.") ";
            }else{
                $lms_data['filters'] = null;
            }

            if(isset($_POST['lms_updateddaterange']) && !empty($_POST['lms_updateddaterange'])){
                $dates = explode(' to ', $_POST['lms_updateddaterange']);
                $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($dates[0] .' +1 day'));

                $condition .= " and `status_logs`.`updated_at` >= '".$start_date."' and `status_logs`.`updated_at` <= '".$end_date."'";
                $lms_data['lms_updateddaterange'] = $_POST['lms_updateddaterange'];
            }else{
                $lms_data['lms_updateddaterange'] = null;
            }
        
            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();

            $lms_data['lead_sources'] = $lms_sources;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['lms_client_name'] = $this->client->name;

            $lms_leads_stmt = $lms_pdo->prepare("SELECT `leads`.`id`, `leads`.`name`, `leads`.`phone`, `leads`.`is_potential`, `leads`.`created_at`,MAX(`status_logs`.`created_at`) as `updated_at`, `lead_types`.`name` as `grade`, `lead_sources`.`name` as `source`, `lead_statuses`.`name` as `stage` FROM `leads` LEFT JOIN `lead_types` ON `leads`.`lead_type_id` = `lead_types`.`id` LEFT JOIN `lead_sources` ON `leads`.`lead_source_id` = `lead_sources`.`id` LEFT JOIN `lead_statuses` ON `leads`.`lead_status_id` = `lead_statuses`.`id` LEFT JOIN `status_logs` ON `leads`.`id` = `status_logs`.`lead_id` ".$condition."and lead_statuses.is_stage IN ('notknows', 'qualified', 'disqualified') GROUP BY `leads`.`id`,  `leads`.`name`,  `leads`.`phone`,  `leads`.`is_potential`,  `leads`.`created_at`, `lead_types`.`name`,  `lead_sources`.`name`,  `lead_statuses`.`name`;");
            // dd($lms_leads_stmt);
            $lms_leads_stmt->execute();
            $lms_leads = $lms_leads_stmt->fetchAll();
            $lms_data['lms_daily_leads'] = $lms_leads;

            $lms_leads_state_stmt = $lms_pdo->prepare("SELECT `lead_statuses`.`name` as `stage` ,COUNT(DISTINCT leads.id) as total FROM `leads` LEFT JOIN `lead_types` ON `leads`.`lead_type_id` = `lead_types`.`id` LEFT JOIN `lead_sources` ON `leads`.`lead_source_id` = `lead_sources`.`id` LEFT JOIN `lead_statuses` ON `leads`.`lead_status_id` = `lead_statuses`.`id` LEFT JOIN `status_logs` ON `leads`.`id` = `status_logs`.`lead_id` ".$condition."and lead_statuses.is_stage IN ('notknows', 'qualified', 'disqualified') GROUP BY `lead_statuses`.`name` ORDER BY total DESC;");
            $lms_leads_state_stmt->execute();
            $lms_leads_state = $lms_leads_state_stmt->fetchAll();
            // dd($lms_leads_state);
            $lms_data['lms_daily_leads_state'] = $lms_leads_state;

            $Daily_is_potential = 0;

            foreach($lms_leads as $lead){
                if($lead['is_potential'] == 'yes')
                {
                    $Daily_is_potential++;
                }
            }

            $lms_data['Daily_is_potential'] = $Daily_is_potential;

            $lms_leads_update_stmt = $lms_pdo->prepare("SELECT `leads`.`id`, `leads`.`name`, `leads`.`phone`, `leads`.`is_potential`, `leads`.`created_at`, `latest_status_log`.`created_at` as `updated_at`, `lead_types`.`name` as `grade`, `lead_sources`.`name` as `source`, `lead_statuses`.`name` as `stage` FROM `leads` LEFT JOIN `lead_types` ON `leads`.`lead_type_id` = `lead_types`.`id` LEFT JOIN `lead_sources` ON `leads`.`lead_source_id` = `lead_sources`.`id` LEFT JOIN `lead_statuses` ON `leads`.`lead_status_id` = `lead_statuses`.`id` LEFT JOIN (SELECT `lead_id`, MAX(`created_at`) as `created_at` FROM `status_logs` ".$condition_update1." GROUP BY `lead_id` ) AS `latest_status_log` ON `leads`.`id` = `latest_status_log`.`lead_id` WHERE `leads`.`created_at` < `latest_status_log`.`created_at` ".$condition_update." and lead_statuses.is_stage IN ('notknows', 'qualified', 'disqualified') GROUP BY`leads`.`id`, `leads`.`name`, `leads`.`phone`, `leads`.`is_potential`, `leads`.`created_at`,`lead_types`.`name`, `lead_sources`.`name`, `lead_statuses`.`name`;");
            // dd($lms_leads_update_stmt);
            $lms_leads_update_stmt->execute();
            $lms_leads_update = $lms_leads_update_stmt->fetchAll();
            $lms_data['lms_update_leads'] = $lms_leads_update;

            $lms_leads_update_state_stmt = $lms_pdo->prepare("SELECT `lead_statuses`.`name` as `stage` ,COUNT(DISTINCT leads.id) as total FROM `leads` LEFT JOIN `lead_types` ON `leads`.`lead_type_id` = `lead_types`.`id` LEFT JOIN `lead_sources` ON `leads`.`lead_source_id` = `lead_sources`.`id` LEFT JOIN `lead_statuses` ON `leads`.`lead_status_id` = `lead_statuses`.`id` LEFT JOIN (SELECT `lead_id`, MAX(`created_at`) as `created_at` FROM `status_logs` ".$condition_update1." GROUP BY `lead_id` ) AS `latest_status_log` ON `leads`.`id` = `latest_status_log`.`lead_id` WHERE `leads`.`created_at` < `latest_status_log`.`created_at` ".$condition_update." and lead_statuses.is_stage IN ('notknows', 'qualified', 'disqualified') GROUP BY `lead_statuses`.`name` ORDER BY total DESC;");
            $lms_leads_update_state_stmt->execute();
            $lms_leads_update_state = $lms_leads_update_state_stmt->fetchAll();
            // dd($lms_leads_update_state);
            $lms_data['lms_update_leads_state'] = $lms_leads_update_state;

            $Updated_is_potential = 0;

            foreach($lms_leads_update as $lead){
                if($lead['is_potential'] == 'yes')
                {
                    $Updated_is_potential++;
                }
            }

            $lms_data['Updated_is_potential'] = $Updated_is_potential;

            // dd($lms_data);

        } catch(\PDOException $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
            
        return view("analysis.lms.lms-daily-report",compact("lms_data"));
    }

    public function get_lms_data_callreport(Request $request)
    {
        $lms_data = array();
        $error = false;
        $lms_client_id = $this->client_id;
        $lms_url = $this->client_url;
        $lms_data['lms_url'] = $lms_url;
        try {
            $_SESSION['lms_client_check'] = $lms_client_id;
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
            // $servername = "127.0.0.1";
            // $username = "root";
            // $password = "";
            // $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $condition = "";
            $Callback_condition = "";
            $Callback_condition_new_calls = "";
            $Callback_condition_old_calls = "";
            $CallbackCreated_condition = "";
            $appointment_condition = "";
            if(isset($_POST['number_calls']) && !empty($_POST['number_calls'])){

                $lms_data['number_calls'] = $request->number_calls;
            }else{
                $lms_data['number_calls'] = null;
            }
            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){
                if(strpos($_POST['lms_daterange'],"to") != false){

                    $date = explode(' to ', $_POST['lms_daterange']);
                    $date1 = Carbon::createFromFormat('d M, Y', $date[0]);
                    $date2 = Carbon::createFromFormat('d M, Y', $date[1]);

                    $diffInDays = $date1->diffInDays($date2) + 1;
                    $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                    $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                }else{
                    $date =  $_POST['lms_daterange'];
                    $date1 = Carbon::createFromFormat('d M, Y', $date);
                    $date2 = Carbon::createFromFormat('d M, Y', $date);
                    
                    $diffInDays = $date1->diffInDays($date2) + 1;
                    $start_date = date("Y-m-d H:i:s", strtotime($date1->format('Y-m-d')));
                    $end_date = date('Y-m-d H:i:s', strtotime($date2->format('Y-m-d') .' +1 day'));
                }

                $condition .= " where `leads`.`created_at` >= '".$start_date."' and `leads`.`created_at` <= '".$end_date."'";
                $Callback_condition .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."'";

                $Callback_condition_new_calls .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."' and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $Callback_condition_old_calls .= " and `calls`.`date` >= '".$start_date."' and `calls`.`date` <= '".$end_date."' and leads.created_at <= '".$start_date."'";

                $CallbackCreated_condition .= " and `calls`.`created_at` >= '".$start_date."' and `calls`.`created_at` <= '".$end_date."'";
                $appointment_condition .= " and `status_logs`.`created_at` >= '".$start_date."' and `status_logs`.`created_at` <= '".$end_date."'";

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];
                $lms_data['lms_lead_from'] = $start_date;
                $lms_data['lms_lead_to'] = $end_date;
                $lms_data['diffInDays'] = $diffInDays;
            }else{
                $lms_data['lms_daterange'] = null;
                $lms_data['diffInDays'] = 1;

                date_default_timezone_set('Asia/Kolkata');
                $lead_from = date('Y-m-d 00:00:00');
                $lead_to = date('Y-m-d H:i:s');

                $condition .= " WHERE `leads`.`created_at` >= '".$lead_from."' and `leads`.`created_at` <= '".$lead_to."'";
                $CallbackCreated_condition .= " and `calls`.`created_at` >= '".$lead_from."' and `calls`.`created_at` <= '".$lead_to."'";
                $Callback_condition .= " and `calls`.`created_at` >= '".$lead_from."' and `calls`.`created_at` <= '".$lead_to."'";

                $Callback_condition_new_calls .= " and `calls`.`date` >= '".$lead_from."' and `calls`.`date` <= '".$lead_to."' and leads.created_at >= '".$lead_from."' and leads.created_at <= '".$lead_to."'";
                $Callback_condition_old_calls .= " and `calls`.`date` >= '".$lead_from."' and `calls`.`date` <= '".$lead_to."' and leads.created_at <= '".$lead_from."'";

                $appointment_condition .= " and `status_logs`.`created_at` >= '".$lead_from."' and `status_logs`.`created_at` <= '".$lead_to."'";
                // $condition_update .= " where `leads`.`updated_at` >= '".$lead_from."' and `leads`.`updated_at` <= '".$lead_to."'";

                $lms_data['lms_lead_from'] = $lead_from;
                $lms_data['lms_lead_to'] = $lead_to;
            }
            
            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
                $lms_data['filters'] = $filter_arr;

                $condition .= " and `leads`.`lead_source_id` IN (".$implode_selected_sources.") ";
                $Callback_condition .= " and `leads`.`lead_source_id` IN (".$implode_selected_sources.") ";
                $CallbackCreated_condition .= " and `leads`.`lead_source_id` IN (".$implode_selected_sources.") ";
            }else{
                $lms_data['filters'] = null;
            }

            if(isset($_POST['lms_updateddaterange']) && !empty($_POST['lms_updateddaterange'])){
                $dates = explode(' to ', $_POST['lms_updateddaterange']);
                $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($dates[0] .' +1 day'));

                $condition .= " and leads.updated_at >= '".$start_date."' and leads.updated_at <= '".$end_date."'";
                $lms_data['lms_updateddaterange'] = $_POST['lms_updateddaterange'];
            }else{
                $lms_data['lms_updateddaterange'] = null;
            }
        
            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;
            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_client_name'] = $this->client->name;

            //Source Wise Lead Count
            $lms_source_lead_count_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id, count('leads.id') as total FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id ".$condition." group by lead_source_id;");
            $lms_source_lead_count_stmt->execute();
            $lms_source_lead_count = $lms_source_lead_count_stmt->fetchAll();
            $lms_data['lms_source_lead_count'] = $lms_source_lead_count;

            //Scheduled Callback
            $lms_callback_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' ".$Callback_condition." group by calls.lead_id;");
            $lms_callback_stmt->execute();
            $lms_callback = $lms_callback_stmt->fetchAll();
            $lms_data['lms_callback'] = $lms_callback;
            
            //Complete Call Count
            $lms_complete_call_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' and `calls`.`status` = '0' ".$Callback_condition." group by calls.lead_id;");
            $lms_complete_call_stmt->execute();
            $lms_complete_call = $lms_complete_call_stmt->fetchAll();
            $lms_data['lms_complete_call'] = $lms_complete_call;

            //Source Wise Scheduled Callback
            $lms_source_callback_stmt = $lms_pdo->prepare("SELECT lead_sources.name as source_name,COALESCE(COUNT(DISTINCT leads.id), 0) as total FROM `calls` inner join leads on leads.id = calls.lead_id inner join lead_sources on lead_sources.id = leads.lead_source_id WHERE `calls`.`today_call` = '0' and `lead_sources`.`status` = 'active'".$Callback_condition." group by leads.lead_source_id,source_name ;");
            $lms_source_callback_stmt->execute();
            $lms_source_callback = $lms_source_callback_stmt->fetchAll();
            $lms_data['lms_source_callback'] = $lms_source_callback;


            //telecaller
            $telecaller_users = $lms_pdo->prepare("SELECT id, name FROM `users` WHERE `department_id` = 1");
            $telecaller_users->execute();
            $telecaller_users = $telecaller_users->fetchAll();
            
            $telecaller_count_arr = [];
            $telecaller_unique_count_arr = [];
            if(isset($telecaller_users) && !empty($telecaller_users)) {
                foreach ($telecaller_users as $key => $value) {
                    
                    //Telecaller Wise Scheduled Callback Count
                    $lms_telecaller_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' and `calls`.`telecallerid` =".$value['id']."".$Callback_condition." group by calls.lead_id;");
                    // dd($lms_telecaller_stmt);
                    $lms_telecaller_stmt->execute();
                    $lms_telecaller = $lms_telecaller_stmt->fetchAll();

                    //Telecaller Wise Scheduled Callback Count
                    $lms_telecaller_new_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' and `calls`.`telecallerid` =".$value['id']."".$Callback_condition_new_calls." group by calls.lead_id;");
                    // dd($lms_telecaller_new_stmt);
                    $lms_telecaller_new_stmt->execute();
                    $lms_telecaller_new = $lms_telecaller_new_stmt->fetchAll();

                    //Telecaller Wise Scheduled Callback Count
                    $lms_telecaller_old_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' and `calls`.`telecallerid` =".$value['id']."".$Callback_condition_old_calls." group by calls.lead_id;");
                    // dd($lms_telecaller_old_stmt);
                    $lms_telecaller_old_stmt->execute();
                    $lms_telecaller_old = $lms_telecaller_old_stmt->fetchAll();

                    //Telecaller Wise Completed Callback Count
                    $lms_telecaller_completed_call_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = '0' and `calls`.`status` = '0' and `calls`.`telecallerid` =".$value['id']."".$Callback_condition." group by calls.lead_id;");
                    $lms_telecaller_completed_call_stmt->execute();
                    $lms_telecaller_completed_call = $lms_telecaller_completed_call_stmt->fetchAll();
                    
                    //Telecaller Wise Unique Callback Count
                    $lms_telecaller_unique_stmt = $lms_pdo->prepare("SELECT leads.id FROM `calls` inner join leads on leads.id = calls.lead_id WHERE `calls`.`today_call` = 1 and `calls`.`telecallerid` =".$value['id']."".$CallbackCreated_condition." group by calls.lead_id;");
                    $lms_telecaller_unique_stmt->execute();
                    $lms_telecaller_unique = $lms_telecaller_unique_stmt->fetchAll();
                    
                    $telecaller_count_arr[] = array('telecaller_name' => $value['name'], 'calls_counts' => count($lms_telecaller), 'calls_counts_new' => count($lms_telecaller_new), 'calls_counts_old' => count($lms_telecaller_old),'completed_calls_counts' => count($lms_telecaller_completed_call),'unique_calls_counts' => count($lms_telecaller_unique));
                }
            }

            // dd($telecaller_count_arr);
            $lms_data['telecaller_count_arr'] = $telecaller_count_arr;


        } catch(\PDOException $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            unset($_SESSION['lms_client_check']);
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
            
        return view("analysis.lms.lms-call-report",compact("lms_data"));
    }
}
