<?php

namespace App\Http\Controllers;
use App\Models\DomainManagementModel;
use App\Models\BudgetModel;
use App\Models\Investment_clientModel;
use App\Models\Client_propertiesModel;
use App\Models\SalesFunnelModel;
use App\Models\SalesFunnelDayModel;
use PDO;
use Carbon\Carbon;
use Illuminate\Support\Collection;

use Illuminate\Http\Request;

class ConversionController extends Controller
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

    public function get_lms_conversion_data(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        try {
            $lms_url = base64_decode($lms_url);
            // var_dump($lms_url);die;            
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            

             // filter condition for leads
             $condition = "";
             $leadscondition = "";
             $confirmationcondition = "";
             $statecondition = "";
             $appointment_visit_Ucount = 0;
             $conversions_Ucount = 0;

            

            if(isset($_POST['targetedLeads']) && !empty($_POST['targetedLeads'])){

                $lms_data['targetedLeads'] = $_POST['targetedLeads'];
                
            }else{

                $lms_data['targetedLeads'] = null;
                
            }


            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){

                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;

                $condition = " and leads.lead_source_id IN (".$implode_selected_sources.") ";
                $leadscondition = " Where leads.lead_source_id IN (".$implode_selected_sources.") ";
                $statecondition .= " and leads.lead_source_id IN (".$implode_selected_sources.") ";

                $confirmationcondition .= " and leads.lead_source_id IN (".$implode_selected_sources.")";


            }

           
            // Creating sql condition for filters according to dates

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){
                
                $amount = 0;

                $Client_propertieID = Client_propertiesModel::where('domain', $lms_url)->first();
                if(isset($Client_propertieID) && !empty($Client_propertieID)){
                    $Investment_clientID = Investment_clientModel::where('client_properties_id', $Client_propertieID->id)->where('date_range',$_POST['lms_daterange'])->first();
                }
                if(isset($Investment_clientID) && !empty($Investment_clientID)){
                    $Budgets = BudgetModel::where('investment_client_id', $Investment_clientID->id)->get();
                    foreach($Budgets as $Budget){
                        $amount = $amount + $Budget->amount;
                    }
                }

                // dd($amount);

                $lms_data['amountInput'] = $amount;
                
                // dd($_POST['lms_daterange']);

                $date = self::getStartAndEndDate($_POST['lms_daterange']);

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

                $start_date = date("Y-m-d 00:00:00", strtotime($date[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));
                // dd($date);    

                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

                $statecondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
                
                $confirmationcondition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";

                if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                    $leadscondition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                }else{
                    $leadscondition .= " Where leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                }
            
            }else{
             $lms_data['lms_daterange'] = null;
             $lms_data['amountInput'] = null;
                
            }


         
            //Get Leads
            $lms_leads_stmt = $lms_pdo->prepare("SELECT count(*) as count FROM `leads` ".$leadscondition.";");
            $lms_leads_stmt->execute();
            $lms_leads = $lms_leads_stmt->fetchAll();
            $lms_data['lms_leads'] = $lms_leads['0']['count'];


            //Get ARPU
            $lms_ARPU_stmt = $lms_pdo->prepare("SELECT sum(products.price) as price FROM `leads` LEFT JOIN `products` ON leads.product_id = products.id ".$leadscondition.";");
            $lms_ARPU_stmt->execute();
            $lms_ARPU = $lms_ARPU_stmt->fetchAll();
            if($lms_leads['0']['count'] > 0){

                $lms_data['lms_ARPU'] = $lms_ARPU['0']['price']/$lms_leads['0']['count'];
            }else{
                $lms_data['lms_ARPU'] = 0;
            }


            //Get Leads where status is not known
            $lms_leads_status_notknown_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'notknows' ".$condition." ;");
            $lms_leads_status_notknown_stmt->execute();
            $lms_leads_status_notknown = $lms_leads_status_notknown_stmt->fetchAll();
            $lms_data['lms_leads_status_notknown'] = $lms_leads_status_notknown['0']['count'];

            
            //Get Leads where status is Qualified
            $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'qualified' ".$condition.";");
            $lms_leads_status_qualified_stmt->execute();
            $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
            $lms_data['lms_leads_status_qualified'] = $lms_leads_status_qualified['0']['count'];
            
            //Get Leads where status is Conversions

          
            $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
            
            $lms_leads_status_conversions_stmt->execute();
            $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
            $lms_data['lms_leads_status_conversions'] = $lms_leads_status_conversions['0']['count'];
            
            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){


                //Get Leads where status is Appointment Missed
                $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_missed_stmt->execute();
                $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_missed'] = $lms_leads_status_appointment_missed['0']['count'];

                //Get Leads where status is Appointment Visit
            

                $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_visit_stmt->execute();
                $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_visit'] = $lms_leads_status_appointment_visit['0']['count'];
                
                //Get Leads where status is Appointment Schedule

                $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_schedule_stmt->execute();
                $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_schedule'] = $lms_leads_status_appointment_schedule['0']['count'] ;

                //Get Leads where status is Appointment Visit not in Schedule

                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);

                // not in Schedule

                if($implode_appointment_schedule != ''){
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_Ucount = 0;
                }

                //Get Leads where status Conversions not in Appointment Visit 

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_array = [];
                foreach($appointment_visit as $key => $lead){
                    $appointment_visit_array[] = $lead['id'];
                }
                $implode_appointment_visit = implode(',', $appointment_visit_array);
                if($implode_appointment_schedule != ''){
                //AV IDS Not in AS
                $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
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

                $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition."  and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
            
                $conversions_stmt->execute();
                $conversions = $conversions_stmt->fetchAll();

                $conversions_Ucount =  $conversions['0']['count'] ;

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


                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$confirmationcondition." Group By calls.lead_id;");
                $appointment_confirmation_stmt->execute();
                $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
    
                $appointment_confirmation_array = [];
                foreach($appointment_confirmation_ids as $key => $lead){
                    $appointment_confirmation_array[] = $lead['id'];
                }
                $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);
                // dd($implode_appointment_confirmation);

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
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                
                // dd( $appointment_visit_stmt );
                
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();
                
                $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = '';
                }


                $lms_data['lms_leads_status_appointment_schedule'] = $lms_data['lms_leads_status_appointment_schedule'] + $appointment_visit_Ucount + $conversions_Ucount;
                $lms_data['lms_leads_status_appointment_visit'] = $lms_data['lms_leads_status_appointment_visit'] + $conversions_Ucount;

            }else{

                 //Get Leads where status is Appointment Missed
                $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_missed_stmt->execute();
                $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_missed'] = $lms_leads_status_appointment_missed['0']['count'];

                //Get Leads where status is Appointment Visit
            
                // dd($lms_data['lms_leads_status_conversions']);

                $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_visit_stmt->execute();
                $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_visit'] = $lms_leads_status_appointment_visit['0']['count'];
                
                //Get Leads where status is Appointment Schedule

                $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_schedule_stmt->execute();
                $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                $lms_data['lms_leads_status_appointment_schedule'] = $lms_leads_status_appointment_schedule['0']['count'] ;
                // dd($lms_data['lms_leads_status_appointment_schedule']);

                //Get Leads where status is Appointment Visit not in Schedule

                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);
                //appointment_visit not in Schedule
                if($implode_appointment_schedule != ''){

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                // dd($appointment_visit_stmt);
                
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();
                $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                
                //AV IDS Not in AS
                $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
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
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_array = [];
                foreach($appointment_visit as $key => $lead){
                    $appointment_visit_array[] = $lead['id'];
                }
                $implode_appointment_visit = implode(',', $appointment_visit_array);

                //conversions not in visit
                // dd('Test');

                if($implode_appointment_visit != ''){
                $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                
                $conversions_stmt->execute();
                $conversions = $conversions_stmt->fetchAll();

                $conversions_Ucount =  $conversions['0']['count'] ;

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


                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$confirmationcondition." Group By calls.lead_id;");
                $appointment_confirmation_stmt->execute();
                $appointment_confirmation_ids = $appointment_confirmation_stmt->fetchAll();
    
                $appointment_confirmation_array = [];
                foreach($appointment_confirmation_ids as $key => $lead){
                    $appointment_confirmation_array[] = $lead['id'];
                }
                $implode_appointment_confirmation = implode(',', $appointment_confirmation_array);
                // dd($implode_appointment_confirmation);

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
                
                // dd($implode_conversions_IDSNINAV);
                //appointment_visit not in Schedule

                if($implode_appointment_confirmation != ''){
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = 0;
                }

                $lms_data['lms_leads_status_appointment_schedule'] = $lms_data['lms_leads_status_appointment_schedule'] + $appointment_visit_Ucount + $conversions_Ucount;
                $lms_data['lms_leads_status_appointment_visit'] = $lms_data['lms_leads_status_appointment_visit'] + $conversions_Ucount;


            }


            //Get Leads where status is Interested

            $intrested_ID_stmt = $lms_pdo->prepare("SELECT id FROM lead_statuses where slug = 'interested-1' ;");
            $intrested_ID_stmt->execute();
            $intrested_ID = $intrested_ID_stmt->fetchAll();
            
            
            $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
            // dd($lms_leads_status_interested_stmt);

            $lms_leads_status_interested_stmt->execute();
            // dd($lms_leads_status_interested_stmt);

            $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
            $lms_data['lms_leads_status_interested'] = $lms_leads_status_interested['0']['count'] + $lms_data['lms_leads_status_appointment_schedule'];

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;



            $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$confirmationcondition." Group By calls.lead_id) AS subquery;");

            // dd($lms_leads_status_appointment_confirmation_stmt);

            $lms_leads_status_appointment_confirmation_stmt->execute();
            $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
            $lms_data['lms_leads_status_appointment_confirmation'] = $lms_leads_status_appointment_confirmation['0']['count'] + $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;
            
            // dd($lms_leads_status_interested_stmt);


            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['filters'] = $filter_arr;



        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.conversion.lms-conversion", compact("lms_data"));
    }

    public function PipelineUnderstanding(Request $request)
    {

        try {

            $PipelineValue = [];

            $PipelineValue['CAGR'] = Round((pow(($request->targetedLeads / $request->CurrentConversion),0.2) - 1) * 100, 2);

            $PipelineValue['ConversionM1'] = (float)($request->CurrentConversion);
            $PipelineValue['ConversionM2'] = Round($PipelineValue['ConversionM1'] * (1 + $PipelineValue['CAGR'] / 100) );
            $PipelineValue['ConversionM3'] = Round($PipelineValue['ConversionM2'] * (1 + $PipelineValue['CAGR'] / 100) );
            $PipelineValue['ConversionM4'] = Round($PipelineValue['ConversionM3'] * (1 + $PipelineValue['CAGR'] / 100) );
            $PipelineValue['ConversionM5'] = Round($PipelineValue['ConversionM4'] * (1 + $PipelineValue['CAGR'] / 100) );
            $PipelineValue['ConversionM6'] = Round($PipelineValue['ConversionM5'] * (1 + $PipelineValue['CAGR'] / 100) );

            $PipelineValue['AppointmentVisitM1'] = Round($PipelineValue['ConversionM1'] / ($request->ConversionsUpperStageP / 100));
            $PipelineValue['AppointmentVisitM2'] = Round($PipelineValue['ConversionM2'] / ($request->ConversionsUpperStageP / 100));
            $PipelineValue['AppointmentVisitM3'] = Round($PipelineValue['ConversionM3'] / ($request->ConversionsUpperStageP / 100));
            $PipelineValue['AppointmentVisitM4'] = Round($PipelineValue['ConversionM4'] / ($request->ConversionsUpperStageP / 100));
            $PipelineValue['AppointmentVisitM5'] = Round($PipelineValue['ConversionM5'] / ($request->ConversionsUpperStageP / 100));
            $PipelineValue['AppointmentVisitM6'] = Round($PipelineValue['ConversionM6'] / ($request->ConversionsUpperStageP / 100));

            $PipelineValue['AppointmentConfirmationM1'] = Round($PipelineValue['AppointmentVisitM1'] / ($request->AppointmentVisitUpperStageP / 100));
            $PipelineValue['AppointmentConfirmationM2'] = Round($PipelineValue['AppointmentVisitM2'] / ($request->AppointmentVisitUpperStageP / 100));
            $PipelineValue['AppointmentConfirmationM3'] = Round($PipelineValue['AppointmentVisitM3'] / ($request->AppointmentVisitUpperStageP / 100));
            $PipelineValue['AppointmentConfirmationM4'] = Round($PipelineValue['AppointmentVisitM4'] / ($request->AppointmentVisitUpperStageP / 100));
            $PipelineValue['AppointmentConfirmationM5'] = Round($PipelineValue['AppointmentVisitM5'] / ($request->AppointmentVisitUpperStageP / 100));
            $PipelineValue['AppointmentConfirmationM6'] = Round($PipelineValue['AppointmentVisitM6'] / ($request->AppointmentVisitUpperStageP / 100));

            $PipelineValue['AppointmentScheduleM1'] = Round($PipelineValue['AppointmentConfirmationM1'] / ($request->AppointmentConfirmationUpperStageP / 100));
            $PipelineValue['AppointmentScheduleM2'] = Round($PipelineValue['AppointmentConfirmationM2'] / ($request->AppointmentConfirmationUpperStageP / 100));
            $PipelineValue['AppointmentScheduleM3'] = Round($PipelineValue['AppointmentConfirmationM3'] / ($request->AppointmentConfirmationUpperStageP / 100));
            $PipelineValue['AppointmentScheduleM4'] = Round($PipelineValue['AppointmentConfirmationM4'] / ($request->AppointmentConfirmationUpperStageP / 100));
            $PipelineValue['AppointmentScheduleM5'] = Round($PipelineValue['AppointmentConfirmationM5'] / ($request->AppointmentConfirmationUpperStageP / 100));
            $PipelineValue['AppointmentScheduleM6'] = Round($PipelineValue['AppointmentConfirmationM6'] / ($request->AppointmentConfirmationUpperStageP / 100));

            $PipelineValue['InterestedM1'] = Round($PipelineValue['AppointmentScheduleM1'] / ($request->AppointmentScheduleUpperStageP / 100));
            $PipelineValue['InterestedM2'] = Round($PipelineValue['AppointmentScheduleM2'] / ($request->AppointmentScheduleUpperStageP / 100));
            $PipelineValue['InterestedM3'] = Round($PipelineValue['AppointmentScheduleM3'] / ($request->AppointmentScheduleUpperStageP / 100));
            $PipelineValue['InterestedM4'] = Round($PipelineValue['AppointmentScheduleM4'] / ($request->AppointmentScheduleUpperStageP / 100));
            $PipelineValue['InterestedM5'] = Round($PipelineValue['AppointmentScheduleM5'] / ($request->AppointmentScheduleUpperStageP / 100));
            $PipelineValue['InterestedM6'] = Round($PipelineValue['AppointmentScheduleM6'] / ($request->AppointmentScheduleUpperStageP / 100));

            $PipelineValue['QualifiedM1'] = Round($PipelineValue['InterestedM1'] / ($request->InterestedUpperStageP / 100));
            $PipelineValue['QualifiedM2'] = Round($PipelineValue['InterestedM2'] / ($request->InterestedUpperStageP / 100));
            $PipelineValue['QualifiedM3'] = Round($PipelineValue['InterestedM3'] / ($request->InterestedUpperStageP / 100));
            $PipelineValue['QualifiedM4'] = Round($PipelineValue['InterestedM4'] / ($request->InterestedUpperStageP / 100));
            $PipelineValue['QualifiedM5'] = Round($PipelineValue['InterestedM5'] / ($request->InterestedUpperStageP / 100));
            $PipelineValue['QualifiedM6'] = Round($PipelineValue['InterestedM6'] / ($request->InterestedUpperStageP / 100));

            $PipelineValue['LeadsGeneratedM1'] = Round($PipelineValue['QualifiedM1'] / ($request->QualifiedUpperStageP / 100));
            $PipelineValue['LeadsGeneratedM2'] = Round($PipelineValue['QualifiedM2'] / ($request->QualifiedUpperStageP / 100));
            $PipelineValue['LeadsGeneratedM3'] = Round($PipelineValue['QualifiedM3'] / ($request->QualifiedUpperStageP / 100));
            $PipelineValue['LeadsGeneratedM4'] = Round($PipelineValue['QualifiedM4'] / ($request->QualifiedUpperStageP / 100));
            $PipelineValue['LeadsGeneratedM5'] = Round($PipelineValue['QualifiedM5'] / ($request->QualifiedUpperStageP / 100));
            $PipelineValue['LeadsGeneratedM6'] = Round($PipelineValue['QualifiedM6'] / ($request->QualifiedUpperStageP / 100));
            
            $PipelineValue['InvestmentM1'] = Round($PipelineValue['LeadsGeneratedM1'] * ($request->LeadsGeneratedKPI));
            $PipelineValue['InvestmentM2'] = Round($PipelineValue['LeadsGeneratedM2'] * ($request->LeadsGeneratedKPI));
            $PipelineValue['InvestmentM3'] = Round($PipelineValue['LeadsGeneratedM3'] * ($request->LeadsGeneratedKPI));
            $PipelineValue['InvestmentM4'] = Round($PipelineValue['LeadsGeneratedM4'] * ($request->LeadsGeneratedKPI));
            $PipelineValue['InvestmentM5'] = Round($PipelineValue['LeadsGeneratedM5'] * ($request->LeadsGeneratedKPI));
            $PipelineValue['InvestmentM6'] = Round($PipelineValue['LeadsGeneratedM6'] * ($request->LeadsGeneratedKPI));
            
        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($PipelineValue);
    }

    public function Investmentneeded(Request $request)
    {

        try {

            $InvestmentValue = [];

            $InvestmentValue['LeadUpdateAD'] = Round(($request->ActiveLead * $request->TLUDU) / 100);
            $InvestmentValue['LeadUpdateWM'] = Round(($request->ActiveLead * $request->TLAWU) / 100);

            $InvestmentValue['ProspectAD'] = Round(($InvestmentValue['LeadUpdateAD'] * $request->IVF_PFUDU) / 100);
            $InvestmentValue['ProspectWM'] = Round(($InvestmentValue['LeadUpdateWM'] * $request->IVF_PFWU) / 100);

            $InvestmentValue['NumberOfWM'] = Round(($request->ActiveLead * 5), 2);

            $InvestmentValue['InvestmentNeededWM'] = Round(($InvestmentValue['NumberOfWM'] * 0.9), 2);

       
        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($InvestmentValue);
    }


    public function getStartAndEndDate($monthYear) {


        $dateTime = Carbon::createFromFormat('M, Y', $monthYear);
    
        
        $startDate = $dateTime->startOfMonth()->toDateString();
        
        $endDate = $dateTime->endOfMonth()->toDateString();
        // dd( $endDate);

        
        return [$startDate, $endDate];
    }

    
    public function get_lms_graph_conversion_data(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;
        $file = $request->file('file');

        // dd($request);
    


        
        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            // dd($lms_url);
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // $filter_arr['filter_stage'] = '';

            if(isset($_POST['lms_graph']) && !empty($_POST['lms_graph'])){

                $lms_data['graph'] = $_POST['lms_graph'];
        
            }else{
                $lms_data['graph'] = 'source';
            }

            $lms_data['last12Months'] = self::getLast12Months();
            $Last12MonthsDates = self::getLast12MonthsDates();

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;

            if(isset($_POST['lms_stages']) && !empty($_POST['lms_stages'])){
                // dd(empty($_POST['lms_stages']));
                $selected_stages = $_POST['lms_stages'];
                $implode_selected_stages = implode(',', $_POST['lms_stages']);
                $filter_arr['filter_stage'] = $_POST['lms_stages'];

                $lms_stages_name = $lms_pdo->prepare("SELECT `name` FROM `lead_statuses` where `id`='".$filter_arr['filter_stage'][0]."' ;");
                $lms_stages_name->execute();
                $lms_stages_na = $lms_stages_name->fetchAll();
                $lms_data['title'] = $lms_stages_na[0][0];
                
            }else{
                $filter_arr['filter_stage'] = "";
            }
            // dd($filter_arr['filter_stage']);


            if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){

                $selected_sources = $_POST['lms_sources'];
                $implode_selected_sources = implode(',', $selected_sources);
                $filter_arr['filter_source'] = $selected_sources;
        
            }

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;

            $lms_data['filters'] = $filter_arr;

            $filter_product_arr = [];

            if(isset($_POST['lms_products']) && !empty($_POST['lms_products'])){

                $selected_products = $_POST['lms_products'];
                $implode_selected_products = implode(',', $selected_products);
                $filter_product_arr['filter_product'] = $selected_products;
        
            }

            //Get Lead product
            $lms_source_stmt = $lms_pdo->prepare("SELECT products.name as lead_product_name,products.id as lead_product_id FROM `leads` inner join products on leads.product_id=products.id group by product_id;");
            $lms_source_stmt->execute();
            $lms_product = $lms_source_stmt->fetchAll();
            $lms_data['lead_products'] = $lms_product;

            $lms_data['filters_product'] = $filter_product_arr;


            $filter_telecaller_arr = [];

            if(isset($_POST['lms_telecallers']) && !empty($_POST['lms_telecallers'])){

                $selected_telecallers = $_POST['lms_telecallers'];
                $implode_selected_telecallers = implode(',', $selected_telecallers);
                $filter_telecaller_arr['filter_telecaller'] = $selected_telecallers;
        
            }

            //Get Lead telecaller
            $lms_source_stmt = $lms_pdo->prepare("SELECT name as lead_telecaller_name, id as lead_telecaller_id FROM `users`;");
            $lms_source_stmt->execute();
            $lms_telecaller = $lms_source_stmt->fetchAll();
            $lms_data['lead_telecallers'] = $lms_telecaller;


            $lms_data['filters_telecaller'] = $filter_telecaller_arr;

            if($lms_data['graph'] == 'source'){

                //Get Source where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_sources = array();
                
                if(isset($_POST['lms_sources']) && !empty($_POST['lms_sources'])){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_sources.id IN (".$implode_selected_sources.") GROUP BY lead_sources.name ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_sources = $lms_leads_status_conversions;
                

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') GROUP BY lead_sources.name ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_sources = $lms_leads_status_conversions;
                }
                
                foreach($conversions_sources as $key => $source){
                    
                    $conversions_arr[$source['source']] = array();
                }

                //Get Leads counts month wise where status is Conversions

                $conversions_lead_arr = array();

                
                foreach($Last12MonthsDates as $key => $dates){
                    
                    $condition = "";
                    $start_date = date("Y-m-d 00:00:00", strtotime($dates['start_date']));
                    $end_date = date('Y-m-d 00:00:00', strtotime($dates['end_date'] .' +1 day'));
                    
                    $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                    
                    if(isset($filter_arr['filter_stage']) && !empty($filter_arr['filter_stage'])){
                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source, COALESCE(COUNT(leads.id), 0) AS lead_count FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.id = ('".$filter_arr['filter_stage'][0]."') ".$condition." GROUP BY lead_sources.name ;");
                        // dd($lms_leads_status_conversions_stmt);
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;

                    }else{

                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source, COALESCE(COUNT(leads.id), 0) AS lead_count FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' ".$condition." GROUP BY lead_sources.name ;");
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;
                    }
                // dd($condition);
                    
                }

                //Make a formated array of data to show in graph

                foreach($conversions_lead_arr as $key => $month){

                    foreach($conversions_arr as $key1 => $source){
                        
                        $sources = array_column($month, "source");
                        // dd($sources);
                        if (in_array($key1, $sources)) {
                            $count = '';
                            foreach ($month as $source) {
                                if ($source['source'] === $key1) {
                                    $count = $source['lead_count'];
                                }
                            }
                            $conversions_arr[$key1][] = $count;
                        }  else {
                            // $key1 does not exist in $month
                            // Add 0 to $conversions_arr[$key1]
                            $conversions_arr[$key1][] = null;
                        }
                    }
                    
                }



                $series = [];
                foreach ($conversions_arr as $source => $data) {
                    $series[] = [
                        'name' => $source,
                        'data' => $data
                    ];
                }
                $lms_data['series'] = $series;

            }

            if($lms_data['graph'] == 'product'){

                //Get Product where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_products = array();
                
                if(isset($_POST['lms_products']) && !empty($_POST['lms_products'])){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE products.id IN (".$implode_selected_products.") GROUP BY products.name;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_products = $lms_leads_status_conversions;
                
                    // dd($lms_leads_status_conversions_stmt);

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') GROUP BY products.name ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_products = $lms_leads_status_conversions;

                }
                
                foreach($conversions_products as $key => $product){
                    
                    $conversions_arr[$product['product']] = array();
                }
                

                //Get Leads counts month wise where status is Conversions

                $conversions_lead_arr = array();

                
                foreach($Last12MonthsDates as $key => $dates){
                    
                    $condition = "";
                    $start_date = date("Y-m-d 00:00:00", strtotime($dates['start_date']));
                    $end_date = date('Y-m-d 00:00:00', strtotime($dates['end_date'] .' +1 day'));
                    
                    $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                    
                    if(isset($filter_arr['filter_stage']) && !empty($filter_arr['filter_stage'])){

                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product, COALESCE(COUNT(leads.id), 0) AS lead_count FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.id = ('".$filter_arr['filter_stage'][0]."') ".$condition." GROUP BY products.name;");
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;

                    }else{

                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product, COALESCE(COUNT(leads.id), 0) AS lead_count FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' ".$condition." GROUP BY products.name;");
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;
                    }
                    
                    
                }


                //Make a formated array of data to show in graph

                foreach($conversions_lead_arr as $key => $month){

                    foreach($conversions_arr as $key1 => $product){

                        $products = array_column($month, "product");
                        if (in_array($key1, $products)) {
                            $count = '';
                            foreach ($month as $product) {
                                if ($product['product'] === $key1) {
                                    $count = $product['lead_count'];
                                }
                            }
                            $conversions_arr[$key1][] = $count;
                        }  else {
                            // $key1 does not exist in $month
                            // Add 0 to $conversions_arr[$key1]
                            $conversions_arr[$key1][] = null;
                        }
                    }
                    
                }

                $series = [];
                foreach ($conversions_arr as $product => $data) {
                    $series[] = [
                        'name' => $product,
                        'data' => $data
                    ];
                }
                $lms_data['series'] = $series;

            }

            if($lms_data['graph'] == 'telecaller'){

                //Get Source where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_telecaller = array();
                
                if(isset($_POST['lms_telecallers']) && !empty($_POST['lms_telecallers'])){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT name FROM `users` WHERE id IN (".$implode_selected_telecallers.");");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_telecaller = $lms_leads_status_conversions;
                

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT users.name AS name FROM users LEFT JOIN status_logs ON users.id = status_logs.user_id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' GROUP BY users.name;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_telecaller = $lms_leads_status_conversions;

                }
                    // dd($lms_leads_status_conversions_stmt);
                
                foreach($conversions_telecaller as $key => $telecaller){
                    
                    $conversions_arr[$telecaller['name']] = array();
                }
                

                //Get Leads counts month wise where status is Conversions

                $conversions_lead_arr = array();

                
                foreach($Last12MonthsDates as $key => $dates){
                    
                    $condition = "";
                    $start_date = date("Y-m-d 00:00:00", strtotime($dates['start_date']));
                    $end_date = date('Y-m-d 00:00:00', strtotime($dates['end_date'] .' +1 day'));
                    
                    $condition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
                    
                    if(isset($filter_arr['filter_stage']) && !empty($filter_arr['filter_stage'])){
                     
                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT users.name AS name, count(users.name) as count FROM users LEFT JOIN status_logs ON users.id = status_logs.user_id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE lead_statuses.id = ('".$filter_arr['filter_stage'][0]."') ".$condition." GROUP BY users.name;");
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;

                    }else{
                     
                        $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT users.name AS name, count(users.name) as count FROM users LEFT JOIN status_logs ON users.id = status_logs.user_id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' ".$condition." GROUP BY users.name;");
                        $lms_leads_status_conversions_stmt->execute();
                        $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                        $conversions_lead_arr[] = $lms_leads_status_conversions;
                    }
                    
                }


                //Make a formated array of data to show in graph

                foreach($conversions_lead_arr as $key => $month){

                    foreach($conversions_arr as $key1 => $telecaller){
                        
                        $telecallers = array_column($month, "name");
                        if (in_array($key1, $telecallers)) {
                            $count = '';
                            foreach ($month as $telecaller) {
                                if ($telecaller['name'] === $key1) {
                                    $count = $telecaller['count'];
                                }
                            }
                            $conversions_arr[$key1][] = $count;
                        }  else {
                            // $key1 does not exist in $month
                            // Add 0 to $conversions_arr[$key1]
                            $conversions_arr[$key1][] = null;
                        }
                    }
                    
                }
                // dd($conversions_arr);

                $series = [];
                foreach ($conversions_arr as $telecaller => $data) {
                    $series[] = [
                        'name' => $telecaller,
                        'data' => $data
                    ];
                }
                $lms_data['series'] = $series;
            }
            // dd($lms_data['last12Months']); 
            if (!empty($file)) {

                // File Details
                $filename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $tempPath = $file->getRealPath();
                $fileSize = $file->getSize();
                $mimeType = $file->getMimeType();
    
                // Valid File Extensions
                $valid_extension = array("csv");
    
                // 2MB in Bytes
                $maxFileSize = 2097152;
    
                // Check file extension
                if (in_array(strtolower($extension), $valid_extension)) {
    
                    // Check file size
                    if ($fileSize <= $maxFileSize) {
    
                        // File upload location
                        //$location = 'uploads';
                        $location = '../public_html/spikeExcel/uploads';
    
                        // Upload file
                        $file->move($location, $filename);
    
                        // Import CSV to Database
                        //$filepath = public_path($location."/".$filename);
                        $filepath = $location . "/" . $filename;
    
                        // Reading file
                        $file = fopen($filepath, "r");
                        
                        $import_arr = array();
                        $importData_arr = array();
                        $last12Months = array();
                        // $series = [];
    
                        $i = 0;
    
                        while (($filedata = fgetcsv($file, 1000, ",")) !== false) {
                            $num = count($filedata);
    
                            // Skip first row (Remove below comment if you want to skip the first row)
                            if($i == 0){
                                for ($c = 0; $c < $num; $c++) {
                                    $import_arr[$i][] = $filedata[$c];
                                }
                            $i++;
                            continue;
                            }
                            for ($c = 0; $c < $num; $c++) {
                                $importData_arr[$i][] = $filedata[$c];
                            }
                            $i++;
                        }
                        fclose($file);
    
                        // dd($importData_arr);
                        // Insert to MySQL database
                        // foreach ($importData_arr as $importData) {
                        //     $last12Months[] = $importData[0];
                        // }
    
                        foreach ($import_arr as $import) {
                            for($j = 1; $j < count($import); $j++ ){
                                if($j == 0){
                                // $i++;
                                continue;
                                }
                                $data = [];
                                for ($i= 1; $i<= 12; $i++) {
                                    if(!empty($importData_arr[$i][$j])){
                                    $data[] = $importData_arr[$i][$j];
                                    }else{
                                    $data[] = null;
                                    }
                                }
                                $series[] = [
                                    'name' => $import[$j],
                                    'data' => $data
                                ];
    
                            }
                        }
                        // $lms_data['file'] = 1;
                        $lms_data['series'] = $series;
                        // $lms_data['last12Months'] = $last12Months;
    
                        $request->session()->flash("message", "Data have been imported successfully");
                    } else {
                        $request->session()->flash("error", "File too large. File must be less than 2MB.");
    
                    }
    
                } else {
                    $request->session()->flash("error", "Invalid File Extension.");
                }
            }
                        // dd($series);

            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.conversion.lms-graph-conversion", compact("lms_data"));
    }

    public function getLast12Months() 
    {

        $months = [];
        $currentMonth = (int)date('m'); // Get the current month as an integer (e.g., 2 for February)
        $currentYear = (int)date('Y'); // Get the current year
    
        // Start from the current month and go back 12 months
        for ($i = 1; $i <= 12; $i++) {
            $month = ($currentMonth - $i) % 12; // Calculate the month index (range: 0-11)
            $year = $currentYear - (int)(($currentMonth - $i) / 12); // Adjust the year accordingly
            if ($month <= 0) { // If the calculated month is December, adjust the year
                $year--;
                $month += 12;
            }
            $months[] = date('M Y', mktime(0, 0, 0, $month, 1, $year)); // Format the month and year and add it to the array
        }
        return array_reverse($months); // Reverse the array to get the last 12 months in chronological order
    }

   
    public function getLast12MonthsDates()
    {
        $months = [];
        $currentMonth = (int)date('m'); // Get the current month as an integer (e.g., 2 for February)
        $currentYear = (int)date('Y'); // Get the current year
        
        // Start from the current month and go back 12 months
        for ($i = 1; $i <= 12; $i++) {
            $month = ($currentMonth - $i) % 12; // Calculate the month index (range: 0-11)
            $year = $currentYear - (int)(($currentMonth - $i) / 12); // Adjust the year accordingly
            if ($month <= 0) { // If the calculated month is December, adjust the year
                $year--;
                $month += 12;
            }
            // Get the starting and ending dates of the month
            $startOfMonth = date('Y-m-01', mktime(0, 0, 0, $month, 1, $year));
            $endOfMonth = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
    
            // Format the month and year and add it to the array
            $months[] = array(
                'start_date' => $startOfMonth,
                'end_date' => $endOfMonth
            );
        }
        return array_reverse($months); // Reverse the array to get the last 12 months in chronological order
    }


    public function graph(Request $request)
    {

        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->client_id;
        $lms_url = $request->url;
        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            // dd($lms_url);
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
                echo json_encode($ex->getMessage());
                die;
            }

             /** Local Server */
            //  $servername = "127.0.0.1";
            //  $username = "root";
            //  $password = "";
            //  $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


            $lms_data['graph'] = $request->graph;
        
            $lms_data['last12Months'] = self::getLast12Months();
            $Last12MonthsDates = self::getLast12MonthsDates();

            if($request->filters != NULL){
                
                $selected_sources = $request->filters;
                $implode_selected_sources = implode(',', $selected_sources['filter_source']);
                $filter_arr['filter_source'] = $selected_sources;
        
            }

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;

            $lms_data['filters'] = $filter_arr;

            $filter_product_arr = [];

            if($request->filters_product != NULL){

                $selected_products = $request->filters_product;
                // var_dump($selected_products ); die;

                $implode_selected_products = implode(',', $selected_products['filter_product']);
                $filter_product_arr['filter_product'] = $selected_products;
        
            }

            //Get Lead product
            $lms_source_stmt = $lms_pdo->prepare("SELECT products.name as lead_product_name,products.id as lead_product_id FROM `leads` inner join products on leads.product_id=products.id group by product_id;");
            $lms_source_stmt->execute();
            $lms_product = $lms_source_stmt->fetchAll();
            $lms_data['lead_products'] = $lms_product;

            $lms_data['filters_product'] = $filter_product_arr;


            $filter_telecaller_arr = [];

            if($request->filters_telecaller != NULL){

                $selected_telecallers = $request->filters_telecaller;
                $implode_selected_telecallers = implode(',', $selected_telecallers['filter_telecaller']);
                $filter_telecaller_arr['filter_telecaller'] = $selected_telecallers;
        
            }

            //Get Lead telecaller
            $lms_source_stmt = $lms_pdo->prepare("SELECT name as lead_telecaller_name, id as lead_telecaller_id FROM `users`;");
            $lms_source_stmt->execute();
            $lms_telecaller = $lms_source_stmt->fetchAll();
            $lms_data['lead_telecallers'] = $lms_telecaller;


            $lms_data['filters_telecaller'] = $filter_telecaller_arr;

            
            
            $dates = $Last12MonthsDates[$request->month];
            $hoverSource = [];
            
            $conditionHover = "";
            $conditionHoverTel = "";
            $start_date = date("Y-m-d 00:00:00", strtotime($dates['start_date']));
            $end_date = date('Y-m-d 00:00:00', strtotime($dates['end_date'] .' +1 day'));
            
            $conditionHover .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

            $conditionHoverTel .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
            
            $conversions_graph = array();


            if($lms_data['graph'] == 'source'){

                //Get Source where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_sources = array();
                
                if($request->filters != NULL){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source, lead_sources.id as source_id FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_sources.id IN (".$implode_selected_sources.") ".$conditionHover." GROUP BY lead_sources.name, lead_sources.id;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_sources = $lms_leads_status_conversions;
                

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT lead_sources.name AS source,lead_sources.id as source_id FROM lead_sources LEFT JOIN leads ON lead_sources.id = leads.lead_source_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') ".$conditionHover." GROUP BY lead_sources.name,lead_sources.id ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_sources = $lms_leads_status_conversions;

                }


                foreach ($conversions_sources as $source => $data) {
                // var_dump($source);die;            

                    $lms_stmt = $lms_pdo->prepare("SELECT lead_statuses.name AS state, count(leads.id) as count FROM leads LEFT JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') ".$conditionHover." and leads.lead_source_id = '".$data['source_id']."' GROUP BY lead_statuses.name;");
                    $lms_stmt->execute();
                    $lms_conversions = $lms_stmt->fetchAll();
                    $conversions = $lms_conversions;

                    foreach ($conversions as $key => $data1) {
                        $conversions_graph[$data['source']][$key] = [
                            'name' => $data1['state'],
                            'count' => $data1['count']
                        ];
                    }
                    
                }

            }

            if($lms_data['graph'] == 'product'){

                //Get Product where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_products = array();
                
                if($request->filters_product != NULL){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product,products.id as products_id FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE products.id IN (".$implode_selected_products.") ".$conditionHover." GROUP BY products.name,products.id;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_products = $lms_leads_status_conversions;
                
                // dd($lms_leads_status_conversions_stmt);

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT products.name AS product,products.id as products_id FROM products LEFT JOIN leads ON products.id = leads.product_id INNER JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') ".$conditionHover." GROUP BY products.name,products.id ;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_products = $lms_leads_status_conversions;

                }
                
                foreach ($conversions_products as $product => $data) {
                    // var_dump($source);die;            
    
                        $lms_stmt = $lms_pdo->prepare("SELECT lead_statuses.name AS state, count(leads.id) as count FROM leads LEFT JOIN lead_statuses ON leads.lead_status_id = lead_statuses.id WHERE lead_statuses.slug IN ('converted', 'patient-registration', 'next-review', 'existing-patient') ".$conditionHover." and leads.product_id = '".$data['products_id']."' GROUP BY lead_statuses.name;");
                        $lms_stmt->execute();
                        $lms_conversions = $lms_stmt->fetchAll();
                        $conversions = $lms_conversions;
    
                        foreach ($conversions as $key => $data1) {
                            $conversions_graph[$data['product']][$key] = [
                                'name' => $data1['state'],
                                'count' => $data1['count']
                            ];
                        }
                        
                    }
            }

            if($lms_data['graph'] == 'telecaller'){

                //Get Source where lead is Converted
                
                
                $conversions_arr = array();
                $conversions_telecaller = array();
                
                if($request->filters_telecaller != NULL){
                
                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT name,users.id as id FROM `users` WHERE id IN (".$implode_selected_telecallers.");");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_telecaller = $lms_leads_status_conversions;
                
                    // dd($lms_leads_status_conversions_stmt);

                }else{

                    $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT users.name AS name,users.id as id FROM users LEFT JOIN status_logs ON users.id = status_logs.user_id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' GROUP BY users.name,users.id;");
                    $lms_leads_status_conversions_stmt->execute();
                    $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                    $conversions_telecaller = $lms_leads_status_conversions;

                }
                

                foreach ($conversions_telecaller as $telecaller => $data) {
                    // var_dump($source);die;            
    
                        $lms_stmt = $lms_pdo->prepare("SELECT count(users.name) as count,lead_statuses.name AS state FROM users LEFT JOIN status_logs ON users.id = status_logs.user_id LEFT JOIN lead_statuses ON status_logs.lead_status_id = lead_statuses.id WHERE lead_statuses.converted = 'yes' ".$conditionHoverTel." and users.id = '".$data['id']."' GROUP BY lead_statuses.name;");
                        $lms_stmt->execute();
                        $lms_conversions = $lms_stmt->fetchAll();
                        $conversions = $lms_conversions;
    
                        foreach ($conversions as $key => $data1) {
                            $conversions_graph[$data['name']][$key] = [
                                'name' => $data1['state'],
                                'count' => $data1['count']
                            ];
                        }
                        
                    }

            }


        } catch(\PDOException $ex) {
            echo json_encode($ex->getMessage());
            die;
        } catch (\Throwable $ex) {
            echo json_encode($ex->getMessage());
            die;
        }

        return response()->json($conversions_graph);
    }


    public function conversion_data(Request $request){
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        try {
            $lms_url = base64_decode($lms_url);
            // var_dump($lms_url);die;            
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            


             // filter condition for leads
             $condition = "";
             $condition1 = "";
             $leadscondition = "";
             $confirmationcondition = "";
             $appointment_visit_Ucount = 0;
             $conversions_Ucount = 0;
           
            // Creating sql condition for filters according to dates

            if(TRUE){

                $start_date = '2024-06-01 00:00:00';//date("Y-m-d 00:00:00", strtotime($date[0]));
                $end_date = '2024-07-01 00:00:00';//date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));

                $condition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
               
                $condition1 .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                
                $confirmationcondition .=" and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";

                
                $leadscondition .= " Where leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";
                $lms_data['amountInput'] = 300000;
            
            }else{
             $lms_data['lms_daterange'] = null;
             $lms_data['amountInput'] = null;
                
            }



            //Get Leads
            $lms_leads_stmt = $lms_pdo->prepare("SELECT count(*) as count FROM `leads` ".$leadscondition.";");
            $lms_leads_stmt->execute();
            $lms_leads = $lms_leads_stmt->fetchAll();
            $lms_data['lms_leads'] = $lms_leads['0']['count'];

            // dd($lms_data['lms_leads']);

            //Get ARPU
            $lms_ARPU_stmt = $lms_pdo->prepare("SELECT sum(products.price) as price FROM `leads` LEFT JOIN `products` ON leads.product_id = products.id ".$leadscondition.";");
            $lms_ARPU_stmt->execute();
            $lms_ARPU = $lms_ARPU_stmt->fetchAll();
            if($lms_data['lms_leads'] > 0){

                $lms_data['lms_ARPU'] = $lms_ARPU['0']['price']/$lms_leads['0']['count'];
            }else{
                $lms_data['lms_ARPU'] = 0;
            }

            //Get Leads where status is Appointment Missed
            $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment-missed' and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $lms_leads_status_appointment_missed_stmt->execute();
            $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
            $lms_data['lms_leads_status_appointment_missed'] = $lms_leads_status_appointment_missed['0']['count'];


            //Get Leads where status is Qualified
            $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses.is_stage = 'qualified' ".$condition1.";");
            $lms_leads_status_qualified_stmt->execute();
            $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
            $lms_data['lms_leads_status_qualified'] = $lms_leads_status_qualified['0']['count'];
        
           
            //Get Leads where status is Conversions

            $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $lms_leads_status_conversions_stmt->execute();
            $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
            $lms_data['lms_leads_status_conversions'] = $lms_leads_status_conversions['0']['count'];
       

            //Get Leads where status is Appointment Visit
          

            $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug In ('appointment') and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $lms_leads_status_appointment_visit_stmt->execute();
            $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
            $lms_data['lms_leads_status_appointment_visit'] = $lms_leads_status_appointment_visit['0']['count'];
            

            //Get Leads where status is Appointment Schedule
          
            $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug In ('appointment-scheduled') and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $lms_leads_status_appointment_schedule_stmt->execute();
            $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
            $lms_data['lms_leads_status_appointment_schedule'] = $lms_leads_status_appointment_schedule['0']['count'] ;


            //Get Leads where status is Interested

            $intrested_ID_stmt = $lms_pdo->prepare("SELECT id FROM lead_statuses where slug = 'interested-1' ;");
            $intrested_ID_stmt->execute();
            $intrested_ID = $intrested_ID_stmt->fetchAll();


            $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $lms_leads_status_interested_stmt->execute();
            $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
            $lms_data['lms_leads_status_interested'] = $lms_leads_status_interested['0']['count'];

            //Get Leads where status is Appointment Visit not in Schedule

            $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $appointment_schedule_ids_stmt->execute();
            $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

            $appointment_schedule_array = [];
            foreach($appointment_schedule_ids as $key => $lead){
                $appointment_schedule_array[] = $lead['id'];
            }
            $implode_appointment_schedule = implode(',', $appointment_schedule_array);

            if($implode_appointment_schedule != ''){
            //AV IDS Not in AS
            $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $appointment_visit_IDSNINAS_stmt->execute();
            $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();

            $appointment_visit_IDSNINAS_array = [];
            foreach($appointment_visit_IDSNINAS as $key => $lead){
                $appointment_visit_IDSNINAS_array[] = $lead['id'];
            }
            $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);

            // not in Schedule

            $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $appointment_visit_stmt->execute();
            $appointment_visit = $appointment_visit_stmt->fetchAll();

            $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
            }else{
                $implode_appointment_visit_IDSNINAS = '';
                $appointment_visit_Ucount = 0;
            }

            //Get Leads where status Conversions not in Appointment Visit 

            $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");
            $appointment_visit_stmt->execute();
            $appointment_visit = $appointment_visit_stmt->fetchAll();

            $appointment_visit_array = [];
            foreach($appointment_visit as $key => $lead){
                $appointment_visit_array[] = $lead['id'];
            }
            $implode_appointment_visit = implode(',', $appointment_visit_array);

            // not in visit

            if($implode_appointment_visit != ''){
            $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
        
            $conversions_stmt->execute();
            $conversions = $conversions_stmt->fetchAll();

            $conversions_Ucount =  $conversions['0']['count'] ;

            // conversions IDS Not in AV
            $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$condition." and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                
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

            $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$confirmationcondition." Group By calls.lead_id;");
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

            // dd($implode_appointment_confirmation);

            //appointment_visit not in Schedule
            if($implode_appointment_confirmation != "")
            {
            $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$condition." ORDER BY status_logs.created_at DESC;");

            $appointment_visit_stmt->execute();
            $appointment_visit = $appointment_visit_stmt->fetchAll();

            $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;

            }else{
                $appointment_visit_count_NotIN_AC = 0;
            }

            $lms_data['lms_leads_status_appointment_schedule'] = $lms_data['lms_leads_status_appointment_schedule'] + $appointment_visit_Ucount + $conversions_Ucount;
            $lms_data['lms_leads_status_appointment_visit'] = $lms_data['lms_leads_status_appointment_visit'] + $conversions_Ucount;
            $lms_data['lms_leads_status_interested'] = $lms_data['lms_leads_status_interested'] + $lms_data['lms_leads_status_appointment_schedule'];

            //Get Leads where status is Appointment Confirmation

            $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$confirmationcondition."Group By calls.lead_id) AS subquery;");
            $lms_leads_status_appointment_confirmation_stmt->execute();
            $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
            $lms_data['lms_leads_status_appointment_confirmation'] = $lms_leads_status_appointment_confirmation['0']['count'] + $appointment_visit_Ucount + $conversions_Ucount +$appointment_visit_count_NotIN_AC;
            
            // dd($lms_data);
            
            // dd($client_properties_id);

            $SalesFunnel = new SalesFunnelModel;

            $SalesFunnel->domainmanagement_id = $request->id;
            $SalesFunnel->client_properties_id = Client_propertiesModel::where('domain', $lms_url)->first()->id;
            $SalesFunnel->client_name = DomainManagementModel::where('id', $request->id)->first()->name;
            $SalesFunnel->month_year = "Jun, 2024";
            $SalesFunnel->ARPU = Round($lms_data['lms_ARPU'] , 2);

            // Store Number of leads 

            $SalesFunnel->LGNOL = $lms_data['lms_leads'];
            $SalesFunnel->QNOL = $lms_data['lms_leads_status_qualified'];
            $SalesFunnel->INOL = $lms_data['lms_leads_status_interested'];
            $SalesFunnel->ASNOL = $lms_data['lms_leads_status_appointment_schedule'];
            $SalesFunnel->ACNOL = $lms_data['lms_leads_status_appointment_confirmation'];
            $SalesFunnel->AVNOL = $lms_data['lms_leads_status_appointment_visit'];
            $SalesFunnel->CNOL = $lms_data['lms_leads_status_conversions'];

            // Store Stage to lead %
            
            $SalesFunnel->LGSTL = 100;
            if($lms_data['lms_leads'] > 0 ){
                $SalesFunnel->QSTL = Round($lms_data['lms_leads_status_qualified'] / $lms_data['lms_leads'] * 100 , 2);
                $SalesFunnel->ISTL = Round($lms_data['lms_leads_status_interested'] / $lms_data['lms_leads'] * 100 , 2);
                $SalesFunnel->ASSTL = Round($lms_data['lms_leads_status_appointment_schedule'] / $lms_data['lms_leads'] * 100 , 2);
                $SalesFunnel->ACSTL = Round($lms_data['lms_leads_status_appointment_confirmation'] / $lms_data['lms_leads'] * 100 , 2);
                $SalesFunnel->AVSTL = Round($lms_data['lms_leads_status_appointment_visit'] / $lms_data['lms_leads'] * 100 , 2);
                $SalesFunnel->CSTL = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads'] * 100 , 2);
            }else{
                $SalesFunnel->QSTL = 0;
                $SalesFunnel->ISTL = 0;
                $SalesFunnel->ASSTL = 0;
                $SalesFunnel->ACSTL = 0;
                $SalesFunnel->AVSTL = 0;
                $SalesFunnel->CSTL = 0;
            }
          

            // Store Stage to Upper Stage %
        
            if($lms_data['lms_leads'] > 0 ){
                $SalesFunnel->QSTUS = Round($lms_data['lms_leads_status_qualified'] / $lms_data['lms_leads'] * 100 , 2);
            }else{
                $SalesFunnel->QSTUS = 0;
            }
            if($lms_data['lms_leads_status_qualified'] > 0){
                $SalesFunnel->ISTUS = Round($lms_data['lms_leads_status_interested'] / $lms_data['lms_leads_status_qualified'] * 100 , 2);
            }else{
                $SalesFunnel->ISTUS = 0;
            }
            if($lms_data['lms_leads_status_interested'] > 0){
                $SalesFunnel->ASSTUS = Round($lms_data['lms_leads_status_appointment_schedule'] / $lms_data['lms_leads_status_interested'] * 100 , 2);
            }else{
                $SalesFunnel->ASSTUS = 0;
            }
            if($lms_data['lms_leads_status_appointment_schedule'] > 0){
                $SalesFunnel->ACSTUS = Round($lms_data['lms_leads_status_appointment_confirmation'] / $lms_data['lms_leads_status_appointment_schedule'] * 100 , 2);
            }else{
                $SalesFunnel->ACSTUS = 0;
            }
            if($lms_data['lms_leads_status_appointment_confirmation'] > 0){
                $SalesFunnel->AVSTUS = Round($lms_data['lms_leads_status_appointment_visit'] / $lms_data['lms_leads_status_appointment_confirmation'] * 100 , 2);
            }else{
                $SalesFunnel->AVSTUS = 0;
            }
            if($lms_data['lms_leads_status_appointment_visit'] > 0){
                $SalesFunnel->CSTUS = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_visit'] * 100 , 2);
            }else{
                $SalesFunnel->CSTUS = 0;
            }


            // Store Current Number to Stage %

            if($lms_data['lms_leads'] > 0){
                $SalesFunnel->LGCNTS = Round(($lms_data['lms_leads'] - $lms_data['lms_leads_status_qualified']) / $lms_data['lms_leads'] * 100 , 2);
            }else{
                $SalesFunnel->LGCNTS = 0 ;
            }

            if($lms_data['lms_leads_status_qualified'] > 0){
                $SalesFunnel->QCNTS = Round(( $lms_data['lms_leads_status_qualified'] - $lms_data['lms_leads_status_interested']) / $lms_data['lms_leads_status_qualified'] * 100 , 2);
            }else{
                $SalesFunnel->QCNTS = 0 ;
            }

            if($lms_data['lms_leads_status_interested'] > 0){
                $SalesFunnel->ICNTS = Round(( $lms_data['lms_leads_status_interested'] - $lms_data['lms_leads_status_appointment_schedule']) / $lms_data['lms_leads_status_interested'] * 100 , 2);
            }else{
                $SalesFunnel->ICNTS = 0 ;
            }

            if($lms_data['lms_leads_status_appointment_schedule'] > 0){
                $SalesFunnel->ASCNTS = Round(( $lms_data['lms_leads_status_appointment_schedule'] - $lms_data['lms_leads_status_appointment_confirmation']) / $lms_data['lms_leads_status_appointment_schedule'] * 100 , 2);
            }else{
                $SalesFunnel->ASCNTS = 0 ;
            }

            if($lms_data['lms_leads_status_appointment_confirmation'] > 0){
                $SalesFunnel->ACCNTS = Round(( $lms_data['lms_leads_status_appointment_confirmation'] - $lms_data['lms_leads_status_appointment_visit']) / $lms_data['lms_leads_status_appointment_confirmation'] * 100 , 2);
            }else{
                $SalesFunnel->ACCNTS = 0 ;
            }

            if($lms_data['lms_leads_status_appointment_visit'] > 0){
                $SalesFunnel->AVCNTS = Round(( $lms_data['lms_leads_status_appointment_visit'] - $lms_data['lms_leads_status_conversions']) / $lms_data['lms_leads_status_appointment_visit'] * 100 , 2);
            }else{
                $SalesFunnel->AVCNTS = 0 ;
            }


            // Store Cov to stage value
        
            if($lms_data['lms_leads'] > 0){
                $SalesFunnel->LGCTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads'] * 100 , 2);
            }else{
                $SalesFunnel->LGCTSV = 0;
            }
            if($lms_data['lms_leads_status_qualified'] > 0){
                $SalesFunnel->QCTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_qualified'] * 100 , 2);
            }else{
                $SalesFunnel->QCTSV = 0;
            }
            if($lms_data['lms_leads_status_interested'] > 0){
                $SalesFunnel->ICTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_interested'] * 100 , 2);
            }else{
                $SalesFunnel->ICTSV = 0;
            }
            if($lms_data['lms_leads_status_appointment_schedule'] > 0){
                $SalesFunnel->ASCTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_schedule'] * 100 , 2);
            }else{
                $SalesFunnel->ASCTSV = 0;
            }
            if($lms_data['lms_leads_status_appointment_confirmation'] > 0){
                $SalesFunnel->ACCTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_confirmation'] * 100 , 2);
            }else{
                $SalesFunnel->ACCTSV = 0;
            }
            if($lms_data['lms_leads_status_appointment_visit'] > 0){
                $SalesFunnel->AVCTSV = Round($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_visit'] * 100 , 2);
            }else{
                $SalesFunnel->AVCTSV = 0;
            }

            // Store Cov to stage value

            if($lms_data['lms_leads'] > 0){
                $SalesFunnel->LGSV = Round($lms_data['lms_leads'] * (($lms_data['lms_leads'] - $lms_data['lms_leads_status_qualified']) / $lms_data['lms_leads']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->LGSV = 0;
            }

            if($lms_data['lms_leads_status_qualified'] > 0){
                $SalesFunnel->QSV = Round($lms_data['lms_leads_status_qualified'] * (( $lms_data['lms_leads_status_qualified'] - $lms_data['lms_leads_status_interested']) / $lms_data['lms_leads_status_qualified']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_qualified']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->QSV = 0;
            }

            if($lms_data['lms_leads_status_interested'] > 0){
                $SalesFunnel->ISV = Round($lms_data['lms_leads_status_interested'] * (( $lms_data['lms_leads_status_interested'] - $lms_data['lms_leads_status_appointment_schedule']) / $lms_data['lms_leads_status_interested']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_interested']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->ISV = 0;
            }

            if($lms_data['lms_leads_status_appointment_schedule'] > 0){
                $SalesFunnel->ASSV = Round($lms_data['lms_leads_status_appointment_schedule'] * (( $lms_data['lms_leads_status_appointment_schedule'] - $lms_data['lms_leads_status_appointment_confirmation']) / $lms_data['lms_leads_status_appointment_schedule']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_schedule']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->ASSV = 0;
            }

            if($lms_data['lms_leads_status_appointment_confirmation'] > 0){
                $SalesFunnel->ACSV = Round($lms_data['lms_leads_status_appointment_confirmation'] * (( $lms_data['lms_leads_status_appointment_confirmation'] - $lms_data['lms_leads_status_appointment_visit']) / $lms_data['lms_leads_status_appointment_confirmation']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_confirmation']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->ACSV = 0;
            }

            if($lms_data['lms_leads_status_appointment_visit'] > 0){
                $SalesFunnel->AVSV = Round($lms_data['lms_leads_status_appointment_visit'] * (( $lms_data['lms_leads_status_appointment_visit'] - $lms_data['lms_leads_status_conversions']) / $lms_data['lms_leads_status_appointment_visit']) * ($lms_data['lms_leads_status_conversions'] / $lms_data['lms_leads_status_appointment_visit']) * $lms_data['lms_ARPU'] , 2);
            }else{
                $SalesFunnel->AVSV = 0;
            }

            $SalesFunnel->CSV = Round($lms_data['lms_leads_status_conversions'] * $lms_data['lms_ARPU'] , 2);


            // Funnel Value

            $SalesFunnel->TFV = $SalesFunnel->LGSV + $SalesFunnel->QSV + $SalesFunnel->ISV + $SalesFunnel->ASSV + $SalesFunnel->ACSV + $SalesFunnel->AVSV + $SalesFunnel->CSV;

            // STORE Cost KPI

            if($lms_data['lms_leads'] > 0){
                $SalesFunnel->LGCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads'] , 2);
            }else{
                $SalesFunnel->LGCKPI = 0;
            }
            if($lms_data['lms_leads_status_qualified'] > 0){
                $SalesFunnel->QCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_qualified'] , 2);
            }else{
                $SalesFunnel->QCKPI = 0;
            }
            if($lms_data['lms_leads_status_interested'] > 0){
                $SalesFunnel->ICKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_interested'] , 2);
            }else{
                $SalesFunnel->ICKPI = 0;
            }
            if($lms_data['lms_leads_status_appointment_schedule'] > 0){
                $SalesFunnel->ASCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_appointment_schedule'] , 2);
            }else{
                $SalesFunnel->ASCKPI = 0;
            }
            if($lms_data['lms_leads_status_appointment_confirmation'] > 0){
                $SalesFunnel->ACCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_appointment_confirmation'] , 2);
            }else{
                $SalesFunnel->ACCKPI = 0;
            }
            if($lms_data['lms_leads_status_appointment_visit'] > 0){
                $SalesFunnel->AVCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_appointment_visit'] , 2);
            }else{
                $SalesFunnel->AVCKPI = 0;
            }
            if($lms_data['lms_leads_status_conversions'] > 0){
                $SalesFunnel->CCKPI = Round($lms_data['amountInput'] / $lms_data['lms_leads_status_conversions'] , 2);
            }else{
                $SalesFunnel->CCKPI = 0;
            }


            // $SalesFunnel->save(); 

            dd($SalesFunnel);


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.conversion.lms-conversion", compact("lms_data"));
    }

    public function Comparison(Request $request){
        
        try {
            $lms_data = [];
            $lms_client_id = $request->id;
            $lms_url = $this->client_url;
            $lms_data['lms_url'] = $lms_url;
            $lms_url = base64_decode($lms_url);
            $lms_data['lms_client_id'] = $lms_client_id;
            $domain = Client_propertiesModel::where('domain' , $lms_url)->first();

            $lms_data['domainID'] = $domain->id ;

            $date = Carbon::now();
            $lastMonth = $date->subMonth()->format('M, Y');
            
            // dd($lastMonth);
            $data = SalesFunnelModel::Where('domainmanagement_id', $lms_client_id)->where('client_properties_id' , $domain->id)->where('month_year' , $lastMonth)->first();

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
      
        return view("analysis.conversion.comparison", compact("data","lms_data"));


    }

    public function comparisonMethod(Request $request){
        
        

            $comprasionData = [];
            $funnelData = [];

            $lastmonth = date('M, Y', strtotime('last month'));

            $datas = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->get();
            
            $lastmonthdata = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->where('month_year' , $lastmonth)->get();
        // dd($lastmonthdata);
       

        if($request->method == "Avgofsystem"){
           

            $data = [];

            foreach($datas as $value){
                    $valuesForQSTUS[] = $value["QSTUS"];
                    $valuesForISTUS[] = $value["ISTUS"];
                    $valuesForASSTUS[] = $value["ASSTUS"];
                    $valuesForACSTUS[] = $value["ACSTUS"];
                    $valuesForAVSTUS[] = $value["AVSTUS"];
                    $valuesForCSTUS[] = $value["CSTUS"];
            }

            $data['QSTUS'] = Round(collect($valuesForQSTUS)->avg() ,2);
            $data['ISTUS'] = Round(collect($valuesForISTUS)->avg() ,2);
            $data['ASSTUS'] = Round(collect($valuesForASSTUS)->avg() ,2);
            $data['ACSTUS'] = Round(collect($valuesForACSTUS)->avg() ,2);
            $data['AVSTUS'] = Round(collect($valuesForAVSTUS)->avg() ,2);
            $data['CSTUS'] = Round(collect($valuesForCSTUS)->avg() ,2);
        }
        else if($request->method == "Bestofsystem"){

            // find the month with max value
            $valuesForKey = [];

            foreach($datas as $value){
                    $valuesForKey[] = $value["TFV"];
            }

            $maxValue = max($valuesForKey);

            $data = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->where('TFV' , $maxValue)->first();


        }
        else if($request->method == "MostOptimisedFunnel"){

            $data = [];

            foreach($datas as $value){
                    $valuesForQSTUS[] = $value["QSTUS"];
                    $valuesForISTUS[] = $value["ISTUS"];
                    $valuesForASSTUS[] = $value["ASSTUS"];
                    $valuesForACSTUS[] = $value["ACSTUS"];
                    $valuesForAVSTUS[] = $value["AVSTUS"];
                    $valuesForCSTUS[] = $value["CSTUS"];
            }

            $data['QSTUS'] = max($valuesForQSTUS);
            $data['ISTUS'] = max($valuesForISTUS);
            $data['ASSTUS'] = max($valuesForASSTUS);
            $data['ACSTUS'] = max($valuesForACSTUS);
            $data['AVSTUS'] = max($valuesForAVSTUS);
            $data['CSTUS'] = max($valuesForCSTUS);

        }

        //creating the funnal with max value

        $funnelData['LGNOL'] = Round($lastmonthdata['0']->LGNOL);
        $funnelData['QNOL'] = Round($lastmonthdata['0']->LGNOL * $data['QSTUS'] / 100);
        $funnelData['INOL'] = Round($funnelData['QNOL'] * $data['ISTUS'] / 100);
        $funnelData['ASNOL'] = Round($funnelData['INOL'] * $data['ASSTUS'] / 100);
        $funnelData['ACNOL'] = Round($funnelData['ASNOL'] * $data['ACSTUS'] / 100);
        $funnelData['AVNOL'] = Round($funnelData['ACNOL'] * $data['AVSTUS'] / 100);
        $funnelData['CNOL'] = Round($funnelData['AVNOL'] * $data['CSTUS'] / 100);

        $funnelData['LGSTL'] = Round(100);
        if($funnelData['LGNOL'] > 0){
            $funnelData['QSTL'] = Round($funnelData['QNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ISTL'] = Round($funnelData['INOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ASSTL'] = Round($funnelData['ASNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ACSTL'] = Round($funnelData['ACNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['AVSTL'] = Round($funnelData['AVNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['CSTL'] = Round($funnelData['CNOL'] / $funnelData['LGNOL'] * 100, 2);
        }else{
            $funnelData['QSTL'] = 0;
            $funnelData['ISTL'] = 0;
            $funnelData['ASSTL'] = 0;
            $funnelData['ACSTL'] = 0;
            $funnelData['AVSTL'] = 0;
            $funnelData['CSTL'] = 0;
        }

        if($funnelData['LGNOL'] > 0){
            $funnelData['LGCTSV'] = Round($funnelData['CNOL'] / $funnelData['LGNOL'] * 100 ,2);
        }
        if($funnelData['QNOL'] > 0){
            $funnelData['QCTSV'] = Round($funnelData['CNOL'] / $funnelData['QNOL'] * 100 ,2);
        }
        if($funnelData['INOL'] > 0){
            $funnelData['ICTSV'] = Round($funnelData['CNOL'] / $funnelData['INOL'] * 100 ,2);
        }
        if($funnelData['AVNOL'] > 0){
            $funnelData['AVCTSV'] = Round($funnelData['CNOL'] / $funnelData['AVNOL'] * 100 ,2);
        }
        if($funnelData['ACNOL'] > 0){
            $funnelData['ACCTSV'] = Round($funnelData['CNOL'] / $funnelData['ACNOL'] * 100 ,2);
        }
        if($funnelData['ASNOL'] > 0){
            $funnelData['ASCTSV'] = Round($funnelData['CNOL'] / $funnelData['ASNOL'] * 100 ,2);
        }

        $funnelData['LGSV'] = Round(($funnelData['LGNOL'] - $funnelData['QNOL']) * $funnelData['LGCTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['QSV'] = Round(($funnelData['QNOL'] - $funnelData['INOL']) * $funnelData['QCTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['ISV'] = Round(($funnelData['INOL'] - $funnelData['ASNOL']) * $funnelData['ICTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['ASSV'] = Round(($funnelData['ASNOL'] - $funnelData['ACNOL']) * $funnelData['ASCTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['ACSV'] = Round(($funnelData['ACNOL'] - $funnelData['AVNOL']) * $funnelData['ACCTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['AVSV'] = Round(($funnelData['AVNOL'] - $funnelData['CNOL']) * $funnelData['AVCTSV'] * $lastmonthdata['0']->ARPU / 100 ,2);
        $funnelData['CSV'] = Round($funnelData['CNOL'] * $lastmonthdata['0']->ARPU ,2);

        $funnelData['TFV'] = Round($funnelData['LGSV'] + $funnelData['QSV'] + $funnelData['ISV'] + $funnelData['ASSV'] + $funnelData['ACSV'] + $funnelData['AVSV'] + $funnelData['CSV'] ,2);

        $comprasionData['method'] = $request->method;
        $comprasionData['data'] = $data;
        $comprasionData['funnelData'] = $funnelData;
        


        return response()->json($comprasionData);


    }

    public function ComparisonDay(Request $request){
        
        try {
            $lms_data = [];

            $lms_url = $this->client_url;
            $lms_data['lms_url'] = $lms_url;

            $lms_url = base64_decode($lms_url);
            // var_dump($lms_url);die;            
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            $data = [];
            $lms_client_id = $request->id;
            $lms_data['lms_client_id'] = $lms_client_id;
            $domain = Client_propertiesModel::where('domain' , $lms_url)->first();

            $lms_data['domainID'] = $domain->id ;

            $statecondition = "";
            $leadscondition ="";
            $condition ="";
            $confirmationcondition ="";
            $appointment_visit_Ucount = 0;
            $conversions_Ucount = 0;

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){
                if (strpos($_POST['lms_daterange'], "to") !== false) {
                    $dates = explode(' to ', $_POST['lms_daterange']);
                    $start_date = date("Y-m-d 00:00:00", strtotime($dates[0]));
                    $end_date = date('Y-m-d 00:00:00', strtotime($dates[1] .' +1 day'));
                }else{
                    $start_date = date("Y-m-d 00:00:00", strtotime($_POST['lms_daterange']));
                    $end_date = date('Y-m-d 00:00:00', strtotime($_POST['lms_daterange'] .' +1 day'));
                }


                $statecondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";
               
                $leadscondition .= " Where leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

                $condition .= " and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."'";

                $confirmationcondition .= " and calls.date >= '".$start_date."' and calls.date <= '".$end_date."'";

                //Get Leads
                $lms_leads_stmt = $lms_pdo->prepare("SELECT count(*) as count FROM `leads` ".$leadscondition.";");
                $lms_leads_stmt->execute();
                $lms_leads = $lms_leads_stmt->fetchAll();
                $data['LGNOL'] = $lms_leads['0']['count'];

                //Get Leads where status is Conversions
                $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_conversions_stmt->execute();
                $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                $data['CNOL'] = $lms_leads_status_conversions['0']['count'];

                //Get Leads where status is Qualified
                $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses. is_stage = 'qualified' ".$condition." ;");
                $lms_leads_status_qualified_stmt->execute();
                $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
                $data['QNOL'] = $lms_leads_status_qualified['0']['count'];

                //Get Leads where status is Appointment Missed
                $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs  inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_missed_stmt->execute();
                $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
                $data['AMNOL'] = $lms_leads_status_appointment_missed['0']['count'];

                //Get Leads where status is Appointment Visit
          

                $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_visit_stmt->execute();
                $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                $data['AVNOL'] = $lms_leads_status_appointment_visit['0']['count'];
            
                //Get Leads where status is Appointment Schedule

                $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_schedule_stmt->execute();
                $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                $data['ASNOL'] = $lms_leads_status_appointment_schedule['0']['count'] ;



                //Get Leads where status is Interested

                $intrested_ID_stmt = $lms_pdo->prepare("SELECT id FROM lead_statuses where slug = 'interested-1' ;");
                $intrested_ID_stmt->execute();
                $intrested_ID = $intrested_ID_stmt->fetchAll();

                $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_interested_stmt->execute();
                $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
                $data['INOL'] = $lms_leads_status_interested['0']['count'] ;

                //Get Leads where status is Appointment Visit not in Schedule

                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);

                if($implode_appointment_schedule != ''){
                //AV IDS Not in AS
                $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_IDSNINAS_stmt->execute();
                $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();

                $appointment_visit_IDSNINAS_array = [];
                foreach($appointment_visit_IDSNINAS as $key => $lead){
                    $appointment_visit_IDSNINAS_array[] = $lead['id'];
                }
                $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);

                // not in Schedule

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;

                }else{
                    $appointment_visit_Ucount = 0;
                    $implode_appointment_visit_IDSNINAS = '';
                }
                //Get Leads where status Conversions not in Appointment Visit 

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_array = [];
                foreach($appointment_visit as $key => $lead){
                    $appointment_visit_array[] = $lead['id'];
                }
                $implode_appointment_visit = implode(',', $appointment_visit_array);

                // not in visit

                if($implode_appointment_visit != ''){

                $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." and status_logs.lead_id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                // dd($conversions_stmt);
            
                $conversions_stmt->execute();
                $conversions = $conversions_stmt->fetchAll();

                $conversions_Ucount =  $conversions['0']['count'] ;

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
                    $implode_conversions_IDSNINAV = '';
                    $conversions_Ucount = 0;
                }
                //appointment_confirmation not in AV

                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$condition."".$confirmationcondition." Group By calls.lead_id;");
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

                // dd($implode_appointment_confirmation);

                //appointment_visit not in Schedule
                if($implode_appointment_confirmation != "")
                { 
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");

                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = 0;
                }
                // dd($conversions_Ucount);
                

                $data['ASNOL'] = $data['ASNOL'] + $appointment_visit_Ucount + $conversions_Ucount;
                $data['AVNOL'] = $data['AVNOL'] + $conversions_Ucount;
                $data['INOL'] = $data['INOL'] + $data['ASNOL'];
            

                $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) ".$condition." ".$confirmationcondition."Group By calls.lead_id) AS subquery;");
                $lms_leads_status_appointment_confirmation_stmt->execute();
                $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
                $data['ACNOL'] = $lms_leads_status_appointment_confirmation['0']['count'] + $appointment_visit_Ucount + $conversions_Ucount +$appointment_visit_count_NotIN_AC;
                // dd($lms_leads_status_appointment_confirmation['0']['count']);
                //Get ARPU
                $lms_ARPU_stmt = $lms_pdo->prepare("SELECT sum(products.price) as price FROM `leads` LEFT JOIN `products` ON leads.product_id = products.id ".$leadscondition.";");
                $lms_ARPU_stmt->execute();
                $lms_ARPU = $lms_ARPU_stmt->fetchAll();
                if($data['LGNOL'] > 0){
                    $data['ARPU'] = $lms_ARPU['0']['price']/$data['LGNOL'];
                }else{
                    $data['ARPU'] = 0;
                }

                


                // dd($data['ARPU']);

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];
            }else {

                //Get Leads
                $lms_leads_stmt = $lms_pdo->prepare("SELECT count(*) as count FROM `leads`;");
                $lms_leads_stmt->execute();
                $lms_leads = $lms_leads_stmt->fetchAll();
                $data['LGNOL'] = $lms_leads['0']['count'];

                //Get Leads where status is Conversions
                $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_conversions_stmt->execute();
                $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
                $data['CNOL'] = $lms_leads_status_conversions['0']['count'];


                //Get Leads where status is Qualified
                $lms_leads_status_qualified_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT leads.id) as count FROM leads INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on leads.lead_status_id = lead_statuses.id where lead_statuses. is_stage = 'qualified' ;");
                $lms_leads_status_qualified_stmt->execute();
                $lms_leads_status_qualified = $lms_leads_status_qualified_stmt->fetchAll();
                $data['QNOL'] = $lms_leads_status_qualified['0']['count'];

                //Get Leads where status is Appointment Missed
                $lms_leads_status_appointment_missed_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_missed_stmt->execute();
                $lms_leads_status_appointment_missed = $lms_leads_status_appointment_missed_stmt->fetchAll();
                $data['AMNOL'] = $lms_leads_status_appointment_missed['0']['count'];

                //Get Leads where status is Appointment Visit
                $lms_leads_status_appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_visit_stmt->execute();
                $lms_leads_status_appointment_visit = $lms_leads_status_appointment_visit_stmt->fetchAll();
                $data['AVNOL'] = $lms_leads_status_appointment_visit['0']['count'];
                
                //Get Leads where status is Appointment Schedule
                $lms_leads_status_appointment_schedule_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_appointment_schedule_stmt->execute();
                $lms_leads_status_appointment_schedule = $lms_leads_status_appointment_schedule_stmt->fetchAll();
                $data['ASNOL'] = $lms_leads_status_appointment_schedule['0']['count'];


                //Get Leads where status is Interested
                $lms_leads_status_interested_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'interested-1' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;");
                $lms_leads_status_interested_stmt->execute();
                $lms_leads_status_interested = $lms_leads_status_interested_stmt->fetchAll();
                $data['INOL'] = $lms_leads_status_interested['0']['count'] ;
                // dd($lms_leads_status_appointment_schedule_stmt);

                //Get Leads where status is Appointment Visit not in Schedule

                $appointment_schedule_ids_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment-scheduled' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $appointment_schedule_ids_stmt->execute();
                $appointment_schedule_ids = $appointment_schedule_ids_stmt->fetchAll();

                $appointment_schedule_array = [];
                foreach($appointment_schedule_ids as $key => $lead){
                    $appointment_schedule_array[] = $lead['id'];
                }
                $implode_appointment_schedule = implode(',', $appointment_schedule_array);

                          
                if($implode_appointment_schedule != ''){
                //AV IDS Not in AS
                $appointment_visit_IDSNINAS_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $appointment_visit_IDSNINAS_stmt->execute();
                $appointment_visit_IDSNINAS = $appointment_visit_IDSNINAS_stmt->fetchAll();

                $appointment_visit_IDSNINAS_array = [];
                foreach($appointment_visit_IDSNINAS as $key => $lead){
                    $appointment_visit_IDSNINAS_array[] = $lead['id'];
                }
                $implode_appointment_visit_IDSNINAS = implode(',', $appointment_visit_IDSNINAS_array);


                // not in Schedule

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_schedule.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_Ucount =  $appointment_visit['0']['count'] ;
                }else{
                    $implode_appointment_visit_IDSNINAS = '';
                    $appointment_visit_Ucount = 0;
                }

                //Get Leads where status Conversions not in Appointment Visit 

                $appointment_visit_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses. slug = 'appointment' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;");
                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_array = [];
                foreach($appointment_visit as $key => $lead){
                    $appointment_visit_array[] = $lead['id'];
                }
                $implode_appointment_visit = implode(',', $appointment_visit_array);

                // not in visit

                if($implode_appointment_visit != ''){
                $conversions_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and status_logs.lead_id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                
              
            
                $conversions_stmt->execute();
                $conversions = $conversions_stmt->fetchAll();

                $conversions_Ucount =  $conversions['0']['count'] ;

                
                // conversions IDS Not in AV
                $conversions_IDSNINAV_stmt = $lms_pdo->prepare("SELECT status_logs.lead_id as id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' and leads.id Not IN (".$implode_appointment_visit.") ORDER BY status_logs.created_at DESC;");
                
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

                $appointment_confirmation_stmt = $lms_pdo->prepare("SELECT calls.lead_id as id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) Group By calls.lead_id;");
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

                // dd($implode_appointment_confirmation);

                //appointment_visit not in Schedule
                if($implode_appointment_confirmation != "")
                {  
                $appointment_visit_stmt = $lms_pdo->prepare("SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.slug = 'appointment' and status_logs.lead_id Not IN (".$implode_appointment_confirmation.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;");

                $appointment_visit_stmt->execute();
                $appointment_visit = $appointment_visit_stmt->fetchAll();

                $appointment_visit_count_NotIN_AC =  $appointment_visit['0']['count'] ;
                }else{
                    $appointment_visit_count_NotIN_AC = 0;
                }



                $data['ASNOL'] = $data['ASNOL'] + $appointment_visit_Ucount + $conversions_Ucount;
                $data['AVNOL'] = $data['AVNOL'] + $conversions_Ucount;
                $data['INOL'] = $data['INOL'] + $data['ASNOL'];
                

                $lms_leads_status_appointment_confirmation_stmt = $lms_pdo->prepare("SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') ) Group By calls.lead_id ) AS subquery;");
                $lms_leads_status_appointment_confirmation_stmt->execute();
                $lms_leads_status_appointment_confirmation = $lms_leads_status_appointment_confirmation_stmt->fetchAll();
                $data['ACNOL'] = $lms_leads_status_appointment_confirmation['0']['count']+ $appointment_visit_Ucount + $conversions_Ucount + $appointment_visit_count_NotIN_AC;

                //Get ARPU
                $lms_ARPU_stmt = $lms_pdo->prepare("SELECT sum(products.price) as price FROM `leads` LEFT JOIN `products` ON leads.product_id = products.id ;");
                $lms_ARPU_stmt->execute();
                $lms_ARPU = $lms_ARPU_stmt->fetchAll();
                $data['ARPU'] = $lms_ARPU['0']['price']/$data['LGNOL'];



                $lms_data['lms_daterange'] = '';
            }

            // dd($data);

                $data['LGSTL'] = Round(100);
                if($data['LGNOL'] > 0){
                    $data['QSTL'] = Round($data['QNOL'] / $data['LGNOL'] * 100, 2);
                    $data['ISTL'] = Round($data['INOL'] / $data['LGNOL'] * 100, 2);
                    $data['ASSTL'] = Round($data['ASNOL'] / $data['LGNOL'] * 100, 2);
                    $data['ACSTL'] = Round($data['ACNOL'] / $data['LGNOL'] * 100, 2);
                    $data['AVSTL'] = Round($data['AVNOL'] / $data['LGNOL'] * 100, 2);
                    $data['CSTL'] = Round($data['CNOL'] / $data['LGNOL'] * 100, 2);

                    $data['LGCTSV'] = Round($data['CNOL'] / $data['LGNOL'] * 100 ,2);

                }else{
                    $data['QSTL'] = 0;
                    $data['ISTL'] = 0;
                    $data['ASSTL'] = 0;
                    $data['ACSTL'] = 0;
                    $data['AVSTL'] = 0;
                    $data['CSTL'] = 0;

                    $data['LGCTSV'] = 0;
                }

                if($data['QNOL'] > 0){
                    $data['QCTSV'] = Round($data['CNOL'] / $data['QNOL'] * 100 ,2);
                }else {
                    $data['QCTSV'] = 0;
                }
                if($data['INOL'] > 0){
                    $data['ICTSV'] = Round($data['CNOL'] / $data['INOL'] * 100 ,2);
                }else {
                    $data['ICTSV'] = 0;
                }
                if($data['AVNOL'] > 0){
                    $data['AVCTSV'] = Round($data['CNOL'] / $data['AVNOL'] * 100 ,2);
                }else {
                    $data['AVCTSV'] = 0;
                }
                if($data['ACNOL'] > 0){
                    $data['ACCTSV'] = Round($data['CNOL'] / $data['ACNOL'] * 100 ,2);
                }else {
                    $data['ACCTSV'] = 0;
                }
                if($data['ASNOL'] > 0){
                    $data['ASCTSV'] = Round($data['CNOL'] / $data['ASNOL'] * 100 ,2);
                }else {
                    $data['ASCTSV'] = 0;
                }
                if($data['AVNOL'] > 0)
                {
                    $data['CSTUS'] = Round($data['CNOL'] / $data['AVNOL'] * 100, 2);
                }else {
                    $data['CSTUS'] = 0;
                }

                if($data['LGNOL'] > 0){
                    $data['QSTUS'] = Round($data['QNOL'] / $data['LGNOL'] * 100, 2);
                }else{
                    $data['QSTUS'] = 0;
                }
                if($data['QNOL'] > 0){
                    $data['ISTUS'] = Round($data['INOL'] / $data['QNOL'] * 100, 2);
                }else{
                    $data['ISTUS'] = 0;
                }
                if($data['INOL'] > 0){
                    $data['ASSTUS'] = Round($data['ASNOL'] / $data['INOL'] * 100, 2);
                }else{
                    $data['ASSTUS'] = 0;
                }
                if($data['ASNOL'] > 0){
                    $data['ACSTUS'] = Round($data['ACNOL'] / $data['ASNOL'] * 100, 2);
                }else{
                    $data['ACSTUS'] = 0;
                }
                if($data['ACNOL'] > 0){
                    $data['AVSTUS'] = Round($data['AVNOL'] / $data['ACNOL'] * 100, 2);
                }else{
                    $data['AVSTUS'] = 0;
                }

                $data['LGSV'] = Round(($data['LGNOL'] - $data['QNOL']) * $data['LGCTSV'] * $data['ARPU'] / 100 ,2);
                $data['QSV'] = Round(($data['QNOL'] - $data['INOL']) * $data['QCTSV'] * $data['ARPU'] / 100 ,2);
                $data['ISV'] = Round(($data['INOL'] - $data['ASNOL']) * $data['ICTSV'] * $data['ARPU'] / 100 ,2);
                $data['ASSV'] = Round(($data['ASNOL'] - $data['ACNOL']) * $data['ASCTSV'] * $data['ARPU'] / 100 ,2);
                $data['ACSV'] = Round(($data['ACNOL'] - $data['AVNOL']) * $data['ACCTSV'] * $data['ARPU'] / 100 ,2);
                $data['AVSV'] = Round(($data['AVNOL'] - $data['CNOL']) * $data['AVCTSV'] * $data['ARPU'] / 100 ,2);
                $data['CSV'] = Round($data['CNOL'] * $data['ARPU'] ,2);

            // $data = SalesFunnelDayModel::Where('domainmanagement_id', $lms_client_id)->where('client_properties_id' , $domain->id)->latest()->first();
            // dd($lms_data['lms_url']);

        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
      
        return view("analysis.conversion.comparisonDay", compact("data","lms_data"));


    }

    public function comparisonMethodDay(Request $request){
        
        

            $comprasionData = [];
            $funnelData = [];

            $lastmonth = date('M, Y', strtotime('last month'));

            $datas = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->get();
            
            // $lastmonthdata = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->where('month_year' , $lastmonth)->get();
        // dd($lastmonthdata);
       

        if($request->method == "Avgofsystem"){
           

            $data = [];

            foreach($datas as $value){
                    $valuesForQSTUS[] = $value["QSTUS"];
                    $valuesForISTUS[] = $value["ISTUS"];
                    $valuesForASSTUS[] = $value["ASSTUS"];
                    $valuesForACSTUS[] = $value["ACSTUS"];
                    $valuesForAVSTUS[] = $value["AVSTUS"];
                    $valuesForCSTUS[] = $value["CSTUS"];
            }

            $data['QSTUS'] = Round(collect($valuesForQSTUS)->avg() ,2);
            $data['ISTUS'] = Round(collect($valuesForISTUS)->avg() ,2);
            $data['ASSTUS'] = Round(collect($valuesForASSTUS)->avg() ,2);
            $data['ACSTUS'] = Round(collect($valuesForACSTUS)->avg() ,2);
            $data['AVSTUS'] = Round(collect($valuesForAVSTUS)->avg() ,2);
            $data['CSTUS'] = Round(collect($valuesForCSTUS)->avg() ,2);
        }
        else if($request->method == "Bestofsystem"){

            // find the month with max value
            $valuesForKey = [];

            foreach($datas as $value){
                    $valuesForKey[] = $value["TFV"];
            }

            $maxValue = max($valuesForKey);

            $data = SalesFunnelModel::Where('domainmanagement_id', $request->client_id)->where('client_properties_id' , $request->domainID)->where('TFV' , $maxValue)->first();


        }
        else if($request->method == "MostOptimisedFunnel"){

            $data = [];

            foreach($datas as $value){
                    $valuesForQSTUS[] = $value["QSTUS"];
                    $valuesForISTUS[] = $value["ISTUS"];
                    $valuesForASSTUS[] = $value["ASSTUS"];
                    $valuesForACSTUS[] = $value["ACSTUS"];
                    $valuesForAVSTUS[] = $value["AVSTUS"];
                    $valuesForCSTUS[] = $value["CSTUS"];
            }

            $data['QSTUS'] = max($valuesForQSTUS);
            $data['ISTUS'] = max($valuesForISTUS);
            $data['ASSTUS'] = max($valuesForASSTUS);
            $data['ACSTUS'] = max($valuesForACSTUS);
            $data['AVSTUS'] = max($valuesForAVSTUS);
            $data['CSTUS'] = max($valuesForCSTUS);

        }

        //creating the funnal with max value

        $funnelData['LGNOL'] = Round($request->lgnol);
        $funnelData['QNOL'] = Round($request->lgnol * $data['QSTUS'] / 100);
        $funnelData['INOL'] = Round($funnelData['QNOL'] * $data['ISTUS'] / 100);
        $funnelData['ASNOL'] = Round($funnelData['INOL'] * $data['ASSTUS'] / 100);
        $funnelData['ACNOL'] = Round($funnelData['ASNOL'] * $data['ACSTUS'] / 100);
        $funnelData['AVNOL'] = Round($funnelData['ACNOL'] * $data['AVSTUS'] / 100);
        $funnelData['CNOL'] = Round($funnelData['AVNOL'] * $data['CSTUS'] / 100);

        $funnelData['LGSTL'] = Round(100);
        if($funnelData['LGNOL'] > 0){
            $funnelData['QSTL'] = Round($funnelData['QNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ISTL'] = Round($funnelData['INOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ASSTL'] = Round($funnelData['ASNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['ACSTL'] = Round($funnelData['ACNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['AVSTL'] = Round($funnelData['AVNOL'] / $funnelData['LGNOL'] * 100, 2);
            $funnelData['CSTL'] = Round($funnelData['CNOL'] / $funnelData['LGNOL'] * 100, 2);
        }else{
            $funnelData['QSTL'] = 0;
            $funnelData['ISTL'] = 0;
            $funnelData['ASSTL'] = 0;
            $funnelData['ACSTL'] = 0;
            $funnelData['AVSTL'] = 0;
            $funnelData['CSTL'] = 0;
        }

        if($funnelData['LGNOL'] > 0){
            $funnelData['LGCTSV'] = Round($funnelData['CNOL'] / $funnelData['LGNOL'] * 100 ,2);
        }else{
            $funnelData['LGCTSV'] = 0;
        }
        if($funnelData['QNOL'] > 0){
            $funnelData['QCTSV'] = Round($funnelData['CNOL'] / $funnelData['QNOL'] * 100 ,2);
        }else{
            $funnelData['QCTSV'] = 0;
        }
        if($funnelData['INOL'] > 0){
            $funnelData['ICTSV'] = Round($funnelData['CNOL'] / $funnelData['INOL'] * 100 ,2);
        }else{
            $funnelData['ICTSV'] = 0;
        }
        if($funnelData['AVNOL'] > 0){
            $funnelData['AVCTSV'] = Round($funnelData['CNOL'] / $funnelData['AVNOL'] * 100 ,2);
        }else{
            $funnelData['AVCTSV'] = 0;
        }
        if($funnelData['ACNOL'] > 0){
            $funnelData['ACCTSV'] = Round($funnelData['CNOL'] / $funnelData['ACNOL'] * 100 ,2);
        }else{
            $funnelData['ACCTSV'] = 0;
        }
        if($funnelData['ASNOL'] > 0){
            $funnelData['ASCTSV'] = Round($funnelData['CNOL'] / $funnelData['ASNOL'] * 100 ,2);
        }else{
            $funnelData['ASCTSV'] = 0;
        }

        $funnelData['LGSV'] = Round(($funnelData['LGNOL'] - $funnelData['QNOL']) * $funnelData['LGCTSV'] * $request->arpu / 100 ,2);
        $funnelData['QSV'] = Round(($funnelData['QNOL'] - $funnelData['INOL']) * $funnelData['QCTSV'] * $request->arpu / 100 ,2);
        $funnelData['ISV'] = Round(($funnelData['INOL'] - $funnelData['ASNOL']) * $funnelData['ICTSV'] * $request->arpu / 100 ,2);
        $funnelData['ASSV'] = Round(($funnelData['ASNOL'] - $funnelData['ACNOL']) * $funnelData['ASCTSV'] * $request->arpu / 100 ,2);
        $funnelData['ACSV'] = Round(($funnelData['ACNOL'] - $funnelData['AVNOL']) * $funnelData['ACCTSV'] * $request->arpu / 100 ,2);
        $funnelData['AVSV'] = Round(($funnelData['AVNOL'] - $funnelData['CNOL']) * $funnelData['AVCTSV'] * $request->arpu / 100 ,2);
        $funnelData['CSV'] = Round($funnelData['CNOL'] * $request->arpu ,2);

        $funnelData['TFV'] = Round($funnelData['LGSV'] + $funnelData['QSV'] + $funnelData['ISV'] + $funnelData['ASSV'] + $funnelData['ACSV'] + $funnelData['AVSV'] + $funnelData['CSV'] ,2);

        $comprasionData['method'] = $request->method;
        $comprasionData['data'] = $data;
        $comprasionData['funnelData'] = $funnelData;
        


        return response()->json($comprasionData);


    }

    public function getstatusforQualifed(Request $request){
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        try {
            $lms_url = base64_decode($lms_url);
            // var_dump($lms_url);die;            
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
            //  $dbName = "primeivfcrm_new";
            
             $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
             $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

             $lms_stages_stmt = $lms_pdo->prepare("SELECT `name` FROM `lead_statuses` where is_stage = 'qualified';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;


            // dd($lms_data['lms_stages']);


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($lms_data['lms_stages']);
    }

    public function getstatusforConversions(Request $request){
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;

        try {
            $lms_url = base64_decode($lms_url);
            // var_dump($lms_url);die;            
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
            //  $dbName = "primeivfcrm_new";
            
             $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
             $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

             $lms_stages_stmt = $lms_pdo->prepare("SELECT `name` FROM `lead_statuses` where converted = 'yes';");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;


            // dd($lms_data['lms_stages']);


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($lms_data['lms_stages']);

    }

    public function spikegraph(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;


        
        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            // dd($lms_url);
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // $filter_arr['filter_stage'] = '';

            $lms_data['last12Months'] = self::getLast12Months();
            $Last12MonthsDates = self::getLast12MonthsDates();




            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['file'] = 0;


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.conversion.lms-spikegraph" , compact("lms_data"));
    }

    public function financial(Request $request)
    {
        $lms_data = $filter_arr = array();
        $error = false;
        $lms_client_id = $request->id;
        $lms_url = $this->client_url;


        
        try {
            // var_dump($_POST);die;            
            $lms_url = base64_decode($lms_url);
            // dd($lms_url);
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
            //  $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // $filter_arr['filter_stage'] = '';

            $statecondition = "";

            $revenue = 0;

            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){
                
                $amount = 0;

                $Client_propertieID = Client_propertiesModel::where('domain', $lms_url)->first();
                if(isset($Client_propertieID) && !empty($Client_propertieID)){
                    $Investment_clientID = Investment_clientModel::where('client_properties_id', $Client_propertieID->id)->where('date_range',$_POST['lms_daterange'])->first();
                }
                if(isset($Investment_clientID) && !empty($Investment_clientID)){
                    $Budgets = BudgetModel::where('investment_client_id', $Investment_clientID->id)->get();
                    foreach($Budgets as $Budget){
                        $amount = $amount + $Budget->amount;
                    }
                }

                // dd($amount);

                $lms_data['amountInput'] = $amount;
                
                // dd($_POST['lms_daterange']);

                $date = self::getStartAndEndDate($_POST['lms_daterange']);

                $lms_data['lms_daterange'] = $_POST['lms_daterange'];

                $start_date = date("Y-m-d 00:00:00", strtotime($date[0]));
                $end_date = date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));
                // dd($date);    

                $statecondition .= " and status_logs.created_at >= '".$start_date."' and status_logs.created_at <= '".$end_date."'";

                $lms_leads_status_conversions_stmt = $lms_pdo->prepare("SELECT DISTINCT status_logs.lead_id FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where lead_statuses.converted='yes' and status_logs.field_change = '0' ".$statecondition." ORDER BY status_logs.created_at DESC;");

                $lms_leads_status_conversions_stmt->execute();
                $lms_leads_status_conversions = $lms_leads_status_conversions_stmt->fetchAll();
    
                foreach($lms_leads_status_conversions as $leadid){
                    
                    $lms_price_stmt = $lms_pdo->prepare("SELECT DISTINCT products.price FROM leads INNER JOIN products ON leads.product_id = products.id where leads.id ='".$leadid[0]."';");
                    $lms_price_stmt->execute();
                    $lms_price = $lms_price_stmt->fetchAll();
    
                    $revenue = $revenue + $lms_price[0]['price'];
                }

                $lms_data['revenue'] = $revenue;

            
            }else{
             $lms_data['lms_daterange'] = null;
             $lms_data['revenue'] = $revenue;
             $lms_data['amountInput'] = null;
                
            }

            //Get Leads where status is Conversions


            // dd($revenue);
            // dd($lms_leads_status_conversions);

            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.conversion.lms-Financial" , compact("lms_data"));
    }

    public function importLeads(Request $request) {

        $lms_data = $filter_arr = array();
        $file = $request->file('file');

        dd($request);

        if (!empty($file)) {

            // File Details
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize();
            $mimeType = $file->getMimeType();

            // Valid File Extensions
            $valid_extension = array("csv");

            // 2MB in Bytes
            $maxFileSize = 2097152;

            // Check file extension
            if (in_array(strtolower($extension), $valid_extension)) {

                // Check file size
                if ($fileSize <= $maxFileSize) {

                    // File upload location
                    //$location = 'uploads';
                    $location = '../public_html/spikeExcel/uploads';

                    // Upload file
                    $file->move($location, $filename);

                    // Import CSV to Database
                    //$filepath = public_path($location."/".$filename);
                    $filepath = $location . "/" . $filename;

                    // Reading file
                    $file = fopen($filepath, "r");
                    
                    $import_arr = array();
                    $importData_arr = array();
                    $last12Months = array();
                    $series = [];

                    $i = 0;

                    while (($filedata = fgetcsv($file, 1000, ",")) !== false) {
                        $num = count($filedata);

                        // Skip first row (Remove below comment if you want to skip the first row)
                        if($i == 0){
                            for ($c = 0; $c < $num; $c++) {
                                $import_arr[$i][] = $filedata[$c];
                            }
                        $i++;
                        continue;
                        }
                        for ($c = 0; $c < $num; $c++) {
                            $importData_arr[$i][] = $filedata[$c];
                        }
                        $i++;
                    }
                    fclose($file);

                    // Insert to MySQL database
                    foreach ($importData_arr as $importData) {
                        $last12Months[] = $importData[0];
                    }

                    foreach ($import_arr as $import) {
                        for($j = 0; $j < count($import); $j++ ){
                            if($j == 0){
                            $i++;
                            continue;
                            }
                            $data = [];
                            foreach ($importData_arr as $importData) {
                                $data[] = $importData[$j];
                            }
                            $series[] = [
                                'name' => $import[$j],
                                'data' => $data
                            ];

                        }
                    }
                    // dd($series);
                    $lms_data['file'] = 1;
                    $lms_data['series'] = $series;
                    $lms_data['last12Months'] = $last12Months;

                    $request->session()->flash("message", "Data have been imported successfully");
                } else {
                    $request->session()->flash("error", "File too large. File must be less than 2MB.");

                }

            } else {
                $request->session()->flash("error", "Invalid File Extension.");
            }
            $lms_data['lms_client_id'] = $this->client_id;
            $lms_data['lms_url'] = $this->client_url;
            // Redirect to index
           return view("analysis.conversion.lms-spikegraph" , compact("lms_data"));
        } else {
            $lms_data['lms_client_id'] = $this->client_id;
            $lms_data['lms_url'] = $this->client_url;
            $request->session()->flash("error", "Please select file to import Data");
           return view("analysis.conversion.lms-spikegraph" , compact("lms_data"));
        }
    }
}
