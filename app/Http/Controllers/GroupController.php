<?php

namespace App\Http\Controllers;
use App\Models\DomainManagementModel;
use App\Models\Client_propertiesModel;
use App\Models\Assigne_groupsModel;
use App\Models\GroupModel;
use PDO;

use Illuminate\Http\Request;

class GroupController extends Controller
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

    public function index(Request $request)
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

            $domain = Client_propertiesModel::Where('domain', $lms_url)->first();

            $lms_data['lms_client_id'] = $lms_client_id;
            $lms_data['lms_url'] = $this->client_url;
            $lms_data['domainID'] = $domain->id;
            $lms_data['lms_client_name'] = $this->client->name;


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return view("analysis.group.index", compact("lms_data"));

    }

    public function fetch(Request $request)
    {
        // dd($request);
        $group = GroupModel::Where('client_id', $request->client_id)->Where('client_properties_id', $request->domain)->Where('group_for', $request->groupFor)->get();

        return response()->json($group);
    }
    public function fetchData(Request $request)
    {


        try {

            $domain = Client_propertiesModel::Where('id', $request->domain)->first();
            $lms_client_id = $request->client_id;

            $lms_url = $domain->domain;
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

            $tr_html = "<thead><tr><th>Ids</th>";

            if($request->ad_page == "0"){
                $tr_html .= "<th>Ads Name</th>";
            }
            if($request->ad_page == "1"){
                $tr_html .= "<th>Forms Name</th>";
            }

            $tr_html .= "<th>All Leads</th><th>Converted Leads</th><th>View Funnel</th></tr></thead><tbody>";

            $ids = Assigne_groupsModel::where('group_id', $request->group)->where('client_id', $request->client_id)->where('client_properties_id', $request->domain)->where('ad_forms', $request->ad_page)->get();

            foreach($ids as $key){
                $tr_html .="<tr>";
                $tr_html .= "<td>".$key->ids."</td>";
                $tr_html .= "<td>".$key->ids."</td>";
                if($request->groupFor == "Facebook"){
                    if($request->ad_page == "0"){
                        $query = "SELECT count(*) as count FROM `leads` WHERE ad_id = '".$key->ids."';"; 
                        $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where ad_id = '".$key->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }
                    if($request->ad_page == "1"){
                        $query = "SELECT count(*) as count FROM `leads` WHERE fbform_id = '".$key->ids."';";
                        $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where fbform_id = '".$key->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ; 
                    }
                }
                if($request->groupFor == "Google"){
                    if($request->ad_page == "0"){
                        $query = "SELECT count(*) as count FROM `leads` WHERE gadgroupid = '".$key->ids."';"; 
                        $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where gadgroupid = '".$key->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;  
                    }
                    if($request->ad_page == "1"){
                        $query = "SELECT count(*) as count FROM `leads` WHERE gcampaignid = '".$key->ids."';"; 
                        $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_sources ON leads.lead_source_id = lead_sources.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where gcampaignid = '".$key->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;  
                    }
                }
                    $leads_stmt = $lms_pdo->prepare($query);
                    $leads_stmt->execute();
                    $leads = $leads_stmt->fetchAll();
                    $leads_arr = $leads;

                    $converted_stmt = $lms_pdo->prepare($query1);
                    $converted_stmt->execute();
                    $converted = $converted_stmt->fetchAll();
                    $converted_arr = $converted;
                    $tr_html .= "<td>".$leads_arr['0']['count']."</td><td>".$converted_arr['0']['count']."</td> <td><button class='btn btn-primary view-btn'>View</button></td></tr></tbody>";
                    // dd($tr_html);

            }


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($tr_html);

    }

    public function funnel(Request $request)
    {

        try {

            $domain = Client_propertiesModel::Where('id', $request->domain)->first();
            $lms_client_id = $request->client_id;

            $lms_url = $domain->domain;
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

            
            
            $tr_html = "<thead><tr><th></th><th>Number of leads</th><th>Stage to lead %</th><th>Stage to <br>Upper Stage %</th><th>Current Number<br> to Stage %</th><th>Cov to stage value</th></tr></thead><tbody>";
            // dd($tr_html);

            $intrested_ID_stmt = $lms_pdo->prepare("SELECT id FROM lead_statuses where slug = 'interested-1' ;");
            $intrested_ID_stmt->execute();
            $intrested_ID = $intrested_ID_stmt->fetchAll();

            if($request->groupFor == "Facebook"){
                if($request->ad_page == "0"){
                    $query = "SELECT count(*) as count FROM `leads` WHERE ad_id = '".$request->ids."';";

                    $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.ad_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ; //conversion

                    $query2 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_statuses on status_logs. lead_status_id=lead_statuses.id WHERE leads.ad_id = '".$request->ids."' and lead_statuses.is_stage = 'qualified';" ; //qualified

                    $query3 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;" ; //interested

                    $query4 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-scheduled
                   
                    $query5 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-visit
                   
                    $query6 = "SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where leads.ad_id = '".$request->ids."' and lead_statuses.is_stage = 'notknows';" ; //status not known
                   
                    $query7 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where leads.ad_id = '".$request->ids."' and lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-missed

                    $query8 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.ad_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ; //appointment-confirmation

                    //conversion IDS not in AV

                    $query9 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AV lead ids

                    $AV_ids_stmt = $lms_pdo->prepare($query9);
                    $AV_ids_stmt->execute();
                    $AV_ids = $AV_ids_stmt->fetchAll();
                    $AV_ids_arr = $AV_ids;

                    $av_IDS_array = [];
                    foreach($AV_ids_arr as $key => $lead){
                        $av_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_av_IDS_array = implode(',', $av_IDS_array);

                    if(isset($implode_av_IDS_array) && !empty($implode_av_IDS_array)){
                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.ad_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.lead_id Not IN (".$implode_av_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }else{

                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.ad_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }
                    

                    //AV IDS not in AC

                    $query11 = "SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.ad_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id;" ; //AC lead ids

                    $AC_ids_stmt = $lms_pdo->prepare($query11);
                    $AC_ids_stmt->execute();
                    $AC_ids = $AC_ids_stmt->fetchAll();
                    $AC_ids_arr = $AC_ids;

                    $ac_IDS_array = [];
                    foreach($AC_ids_arr as $key => $lead){
                        $ac_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_ac_IDS_array = implode(',', $ac_IDS_array);

                    if(isset($implode_ac_IDS_array) && !empty($implode_ac_IDS_array)){

                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.ad_id = '".$request->ids."' and status_logs.lead_id Not IN (".$implode_ac_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }else{
                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }


                    //AC IDS not in AS

                    $query13 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.ad_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AC lead ids

                    $AS_ids_stmt = $lms_pdo->prepare($query13);
                    $AS_ids_stmt->execute();
                    $AS_ids = $AS_ids_stmt->fetchAll();
                    $AS_ids_arr = $AS_ids;

                    $as_IDS_array = [];
                    foreach($AS_ids_arr as $key => $lead){
                        $as_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_as_IDS_array = implode(',', $as_IDS_array);

                    if(isset($implode_as_IDS_array) && !empty($implode_as_IDS_array)){
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.ad_id = '".$request->ids."' and calls.lead_id Not IN (".$implode_as_IDS_array.") and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }else{
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.ad_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }

                    // dd($query1);

                }
                if($request->ad_page == "1"){

                    $query = "SELECT count(*) as count FROM `leads` WHERE fbform_id = '".$request->ids."';"; 
                    $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.fbform_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ; //conversion

                    $query2 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_statuses on status_logs. lead_status_id=lead_statuses.id WHERE leads.fbform_id = '".$request->ids."' and lead_statuses.is_stage = 'qualified';" ; //qualified

                    $query3 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;" ; //interested

                    $query4 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-scheduled
                   
                    $query5 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-visit
                   
                    $query6 = "SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where leads.fbform_id = '".$request->ids."' and lead_statuses.is_stage = 'notknows';" ; //status not known
                   
                    $query7 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where leads.fbform_id = '".$request->ids."' and lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-missed 

                    $query8 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.fbform_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ; //appointment-confirmation

                    //conversion IDS not in AV

                    $query9 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AV lead ids

                    $AV_ids_stmt = $lms_pdo->prepare($query9);
                    $AV_ids_stmt->execute();
                    $AV_ids = $AV_ids_stmt->fetchAll();
                    $AV_ids_arr = $AV_ids;
                    
                    $av_IDS_array = [];
                    foreach($AV_ids_arr as $key => $lead){
                        $av_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_av_IDS_array = implode(',', $av_IDS_array);
                    
                    if(isset($implode_av_IDS_array) && !empty($implode_av_IDS_array)){
                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.fbform_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.lead_id Not IN (".$implode_av_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }else{

                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.fbform_id = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }
                                        
                    
                    //AV IDS not in AC
                    
                    $query11 = "SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.fbform_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id;" ; //AC lead ids
                    
                    $AC_ids_stmt = $lms_pdo->prepare($query11);
                    $AC_ids_stmt->execute();
                    $AC_ids = $AC_ids_stmt->fetchAll();
                    $AC_ids_arr = $AC_ids;
                    
                    $ac_IDS_array = [];
                    foreach($AC_ids_arr as $key => $lead){
                        $ac_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_ac_IDS_array = implode(',', $ac_IDS_array);
                    
                    if(isset($implode_ac_IDS_array) && !empty($implode_ac_IDS_array)){

                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.fbform_id = '".$request->ids."' and status_logs.lead_id Not IN (".$implode_ac_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }else{
                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }
                    
                    //AC IDS not in AS
                    
                    $query13 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.fbform_id = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AC lead ids
                    
                    $AS_ids_stmt = $lms_pdo->prepare($query13);
                    $AS_ids_stmt->execute();
                    $AS_ids = $AS_ids_stmt->fetchAll();
                    $AS_ids_arr = $AS_ids;

                    $as_IDS_array = [];
                    foreach($AS_ids_arr as $key => $lead){
                        $as_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_as_IDS_array = implode(',', $as_IDS_array);

                    if(isset($implode_as_IDS_array) && !empty($implode_as_IDS_array)){
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.fbform_id = '".$request->ids."' and calls.lead_id Not IN (".$implode_as_IDS_array.") and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }else{
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.fbform_id = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }

                }
            }
            if($request->groupFor == "Google"){
                if($request->ad_page == "0"){

                    $query = "SELECT count(*) as count FROM `leads` WHERE gadgroupid = '".$request->ids."';"; 
                    $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gadgroupid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ; //conversion

                    $query2 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_statuses on status_logs. lead_status_id=lead_statuses.id WHERE leads.gadgroupid = '".$request->ids."' and lead_statuses.is_stage = 'qualified';" ; //qualified

                    $query3 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;" ; //interested

                    $query4 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-scheduled
                   
                    $query5 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-visit
                   
                    $query6 = "SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where leads.gadgroupid = '".$request->ids."' and lead_statuses.is_stage = 'notknows';" ; //status not known
                   
                    $query7 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where leads.gadgroupid = '".$request->ids."' and lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-missed 

                    $query8 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gadgroupid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ; //appointment-confirmation

                    //conversion IDS not in AV

                    $query9 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AV lead ids

                    $AV_ids_stmt = $lms_pdo->prepare($query9);
                    $AV_ids_stmt->execute();
                    $AV_ids = $AV_ids_stmt->fetchAll();
                    $AV_ids_arr = $AV_ids;

                    $av_IDS_array = [];
                    foreach($AV_ids_arr as $key => $lead){
                        $av_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_av_IDS_array = implode(',', $av_IDS_array);

                    if(isset($implode_av_IDS_array) && !empty($implode_av_IDS_array)){
                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gadgroupid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.lead_id Not IN (".$implode_av_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }else{

                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gadgroupid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }
                                        
                    
                    //AV IDS not in AC
                    
                    $query11 = "SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gadgroupid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id;" ; //AC lead ids
                    
                    $AC_ids_stmt = $lms_pdo->prepare($query11);
                    $AC_ids_stmt->execute();
                    $AC_ids = $AC_ids_stmt->fetchAll();
                    $AC_ids_arr = $AC_ids;

                    $ac_IDS_array = [];
                    foreach($AC_ids_arr as $key => $lead){
                        $ac_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_ac_IDS_array = implode(',', $ac_IDS_array);

                    if(isset($implode_ac_IDS_array) && !empty($implode_ac_IDS_array)){

                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gadgroupid = '".$request->ids."' and status_logs.lead_id Not IN (".$implode_ac_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }else{
                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }
                    
                    //AC IDS not in AS
                    
                    $query13 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.gadgroupid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AC lead ids
                    
                    $AS_ids_stmt = $lms_pdo->prepare($query13);
                    $AS_ids_stmt->execute();
                    $AS_ids = $AS_ids_stmt->fetchAll();
                    $AS_ids_arr = $AS_ids;

                    $as_IDS_array = [];
                    foreach($AS_ids_arr as $key => $lead){
                        $as_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_as_IDS_array = implode(',', $as_IDS_array);

                    if(isset($implode_as_IDS_array) && !empty($implode_as_IDS_array)){
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gadgroupid = '".$request->ids."' and calls.lead_id Not IN (".$implode_as_IDS_array.") and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }else{
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gadgroupid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }

                }
                if($request->ad_page == "1"){
                    $query = "SELECT count(*) as count FROM `leads` WHERE gcampaignid = '".$request->ids."';"; 
                    $query1 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gcampaignid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ; //conversion

                    $query2 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id INNER JOIN lead_statuses on status_logs. lead_status_id=lead_statuses.id WHERE leads.gcampaignid = '".$request->ids."' and lead_statuses.is_stage = 'qualified';" ; //qualified

                    $query3 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where (lead_statuses. slug = 'interested-1' OR lead_statuses.parent_id = ".$intrested_ID[0]['id'].") and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0'  ORDER BY status_logs.created_at DESC;" ; //interested

                    $query4 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-scheduled
                   
                    $query5 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-visit
                   
                    $query6 = "SELECT COUNT(DISTINCT leads.id) as count FROM leads inner join lead_statuses on leads.lead_status_id = lead_statuses.id where leads.gcampaignid = '".$request->ids."' and lead_statuses.is_stage = 'notknows';" ; //status not known
                   
                    $query7 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where leads.gcampaignid = '".$request->ids."' and lead_statuses. slug = 'appointment-missed' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //appointment-missed 

                    $query8 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gcampaignid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ; //appointment-confirmation

                    //conversion IDS not in AV

                    $query9 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AV lead ids

                    $AV_ids_stmt = $lms_pdo->prepare($query9);
                    $AV_ids_stmt->execute();
                    $AV_ids = $AV_ids_stmt->fetchAll();
                    $AV_ids_arr = $AV_ids;

                    $av_IDS_array = [];
                    foreach($AV_ids_arr as $key => $lead){
                        $av_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_av_IDS_array = implode(',', $av_IDS_array);

                    if(isset($implode_av_IDS_array) && !empty($implode_av_IDS_array)){
                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gcampaignid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.lead_id Not IN (".$implode_av_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }else{

                        $query10 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs INNER JOIN leads ON status_logs.lead_id = leads.id inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id where leads.gcampaignid = '".$request->ids."' and lead_statuses.converted='yes' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }
                                        
                    //AV IDS not in AC
                                        
                    $query11 = "SELECT calls.lead_id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gcampaignid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id;" ; //AC lead ids
                                        
                    $AC_ids_stmt = $lms_pdo->prepare($query11);
                    $AC_ids_stmt->execute();
                    $AC_ids = $AC_ids_stmt->fetchAll();
                    $AC_ids_arr = $AC_ids;

                    $ac_IDS_array = [];
                    foreach($AC_ids_arr as $key => $lead){
                        $ac_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_ac_IDS_array = implode(',', $ac_IDS_array);

                    if(isset($implode_ac_IDS_array) && !empty($implode_ac_IDS_array)){

                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gcampaignid = '".$request->ids."' and status_logs.lead_id Not IN (".$implode_ac_IDS_array.") and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;
                    }else{
                        $query12 = "SELECT COUNT(DISTINCT status_logs.lead_id) as count FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment' and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC; " ;

                    }
                                        
                    //AC IDS not in AS
                                        
                    $query13 = "SELECT DISTINCT status_logs.lead_id FROM status_logs inner join lead_statuses on status_logs. lead_status_id=lead_statuses.id INNER JOIN leads ON status_logs.lead_id = leads.id where lead_statuses. slug = 'appointment-scheduled' and leads.gcampaignid = '".$request->ids."' and status_logs.field_change = '0' ORDER BY status_logs.created_at DESC;" ; //AC lead ids
                                        
                    $AS_ids_stmt = $lms_pdo->prepare($query13);
                    $AS_ids_stmt->execute();
                    $AS_ids = $AS_ids_stmt->fetchAll();
                    $AS_ids_arr = $AS_ids;

                    $as_IDS_array = [];
                    foreach($AS_ids_arr as $key => $lead){
                        $as_IDS_array[] = $lead['lead_id'];
                    }
                    $implode_as_IDS_array = implode(',', $as_IDS_array);

                    if(isset($implode_as_IDS_array) && !empty($implode_as_IDS_array)){
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gcampaignid = '".$request->ids."' and calls.lead_id Not IN (".$implode_as_IDS_array.") and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }else{
                        $query14 = "SELECT COUNT(*) as count FROM (SELECT calls.id FROM calls RIGHT JOIN leads ON leads.id = lead_id WHERE leads.gcampaignid = '".$request->ids."' and calls.lead_status_id = ( SELECT id FROM lead_statuses WHERE lead_statuses.slug IN ('appointment-scheduled') )  Group By calls.lead_id) AS subquery;" ;
                    }

                }
            }
                $leads_stmt = $lms_pdo->prepare($query);
                $leads_stmt->execute();
                $leads = $leads_stmt->fetchAll();
                $leads_arr = $leads;

                $leadCount = $leads_arr['0']['count'] ;

                $converted_stmt = $lms_pdo->prepare($query1);
                $converted_stmt->execute();
                $converted = $converted_stmt->fetchAll();
                $converted_arr = $converted;

                $convertedCount = $converted_arr['0']['count'];

                $converted_NIAV_stmt = $lms_pdo->prepare($query10);
                $converted_NIAV_stmt->execute();
                $converted_NIAV = $converted_NIAV_stmt->fetchAll();
                $converted_NIAV_arr = $converted_NIAV;

                $AV_NIAC_stmt = $lms_pdo->prepare($query12);
                $AV_NIAC_stmt->execute();
                $AV_NIAC = $AV_NIAC_stmt->fetchAll();
                $AV_NIAC_arr = $AV_NIAC;

                $AC_NIAS_stmt = $lms_pdo->prepare($query14);
                $AC_NIAS_stmt->execute();
                $AC_NIAS = $AC_NIAS_stmt->fetchAll();
                $AC_NIAS_arr = $AC_NIAS;

                $qualified_stmt = $lms_pdo->prepare($query2);
                $qualified_stmt->execute();
                $qualified = $qualified_stmt->fetchAll();
                $qualified_arr = $qualified;

                $qualifiedCount = $qualified_arr['0']['count'];

                $interested_stmt = $lms_pdo->prepare($query3);
                $interested_stmt->execute();
                $interested = $interested_stmt->fetchAll();
                $interested_arr = $interested;

                $interestedCount = $interested_arr['0']['count'] + $converted_NIAV_arr['0']['count'] + $AV_NIAC_arr['0']['count'] + $AC_NIAS_arr['0']['count'];

                $AS_stmt = $lms_pdo->prepare($query4);
                $AS_stmt->execute();
                $AS = $AS_stmt->fetchAll();
                $AS_arr = $AS;

                $ASCount = $AS_arr['0']['count'] + $AC_NIAS_arr['0']['count'] + $AV_NIAC_arr['0']['count'] + $converted_NIAV_arr['0']['count'];

                $AV_stmt = $lms_pdo->prepare($query5);
                $AV_stmt->execute();
                $AV = $AV_stmt->fetchAll();
                $AV_arr = $AV;

                $AVCount = $AV_arr['0']['count'] + $converted_NIAV_arr['0']['count'];

                $SNK_stmt = $lms_pdo->prepare($query6);
                $SNK_stmt->execute();
                $SNK = $SNK_stmt->fetchAll();
                $SNK_arr = $SNK;

                $AM_stmt = $lms_pdo->prepare($query7);
                $AM_stmt->execute();
                $AM = $AM_stmt->fetchAll();
                $AM_arr = $AM;

                $AC_stmt = $lms_pdo->prepare($query8);
                $AC_stmt->execute();
                $AC = $AC_stmt->fetchAll();
                $AC_arr = $AC;

                $ACCount = $AC_arr['0']['count'] + $AV_NIAC_arr['0']['count'] + $converted_NIAV_arr['0']['count'];


                //Stage to lead % 

                if($leadCount > 0){
                    $QSTL = Round($qualifiedCount / $leadCount * 100, 2 ) ;
                    $ISTL = Round($interestedCount / $leadCount * 100, 2 ) ;
                    $ASSTL = Round($ASCount / $leadCount * 100, 2 ) ;
                    $ACSTL = Round($ACCount / $leadCount * 100, 2 ) ;
                    $AVSTL = Round($AVCount / $leadCount * 100, 2 ) ;
                    $CSTL = Round($convertedCount / $leadCount * 100, 2 ) ;
                }else{
                    $QSTL = 0 ;
                    $ISTL = 0 ;
                    $ASSTL = 0 ;
                    $ACSTL = 0 ;
                    $AVSTL = 0 ;
                    $CSTL = 0 ;
                }

                // Stage to Upper Stage %

                if($leadCount > 0){
                    $QSTUL = Round($qualifiedCount / $leadCount * 100, 2 ) ;
                }else{
                    $QSTUL = 0 ;
                }

                if($qualifiedCount > 0 ){
                    $ISTUL = Round($interestedCount / $qualifiedCount * 100, 2 ) ;
                }else{
                    $ISTUL = 0 ;
                }

                if($interestedCount > 0){
                    $ASSTUL = Round($ASCount / $interestedCount * 100, 2 ) ;
                }else{
                    $ASSTUL = 0 ;
                }

                if($ASCount > 0){
                    $ACSTUL = Round($ACCount / $ASCount * 100, 2 ) ;
                }else{
                    $ACSTUL = 0 ;
                }

                if($ACCount > 0 ){
                    $AVSTUL = Round($AVCount / $ACCount * 100, 2 ) ;
                }else{
                    $AVSTUL = 0 ;
                }

                if($AVCount > 0){
                    $CSTUL = Round($convertedCount / $AVCount * 100, 2 ) ;
                }else{
                    $CSTUL = 0 ;
                }

                //Current Number to Stage %

                if( $leadCount > 0){
                    $LGCNTS = Round(($leadCount - $qualifiedCount) / $leadCount * 100, 2 ) ;
                }else{
                    $LGCNTS = 0 ;
                }

                if( $qualifiedCount > 0){
                    $QCNTS = Round(($qualifiedCount - $interestedCount) / $qualifiedCount * 100, 2 ) ;
                }else{
                    $QCNTS = 0 ;
                }

                if( $interestedCount > 0){
                    $ICNTS = Round(($interestedCount - $ASCount) / $interestedCount * 100, 2 ) ;
                }else{
                    $ICNTS = 0 ;
                }

                if( $ASCount > 0){
                    $ASCNTS = Round(($ASCount - $ACCount) / $ASCount * 100, 2 ) ;
                }else{
                    $ASCNTS = 0 ;
                }

                if( $ACCount > 0){
                    $ACCNTS = Round(($ACCount - $AVCount) / $ACCount * 100, 2 ) ;
                }else{
                    $ACCNTS = 0 ;
                }

                if( $AVCount > 0){
                    $AVCNTS = Round(($AVCount - $convertedCount) / $AVCount * 100, 2 ) ;
                }else{
                    $AVCNTS = 0 ;
                }

                //Cov to stage value

                if( $leadCount > 0){
                    $LGCTSV = Round($convertedCount / $leadCount * 100, 2 ) ;
                }else{
                    $LGCTSV = 0 ;
                }

                if( $qualifiedCount > 0){
                    $QCTSV = Round($convertedCount / $qualifiedCount * 100, 2 ) ;
                }else{
                    $QCTSV = 0 ;
                }

                if( $interestedCount > 0){
                    $ICTSV = Round($convertedCount / $interestedCount * 100, 2 ) ;
                }else{
                    $ICTSV = 0 ;
                }

                if( $ASCount > 0){
                    $ASCTSV = Round($convertedCount / $ASCount * 100, 2 ) ;
                }else{
                    $ASCTSV = 0 ;
                }

                if( $ACCount > 0){
                    $ACCTSV = Round($convertedCount / $ACCount * 100, 2 ) ;
                }else{
                    $ACCTSV = 0 ;
                }

                if( $AVCount > 0){
                    $AVCTSV = Round($convertedCount / $AVCount * 100, 2 ) ;
                }else{
                    $AVCTSV = 0 ;
                }

                $tr_html .="<tr><td><b>Leads Generated</b></td><td>".$leadCount."</td><td>100%</td><td></td><td>".$LGCNTS."%</td><td>".$LGCTSV."%</td></tr>";

                $tr_html .="<tr><td><b>Qualified</b></td><td>".$qualifiedCount."</td><td>".$QSTL."%</td><td>".$QSTUL."%</td><td>".$QCNTS."%</td><td>".$QCTSV."%</td></tr>";

                $tr_html .="<tr><td><b>Interested</b></td><td>".$interestedCount."</td><td>".$ISTL."%</td><td>".$ISTUL."%</td><td>".$ICNTS."%</td><td>".$ICTSV."%</td></tr>";

                $tr_html .="<tr><td><b>Appointment Schedule</b></td><td>".$ASCount."</td><td>".$ASSTL."%</td><td>".$ASSTUL."%</td><td>".$ASCNTS."%</td><td>".$ASCTSV."%</td></tr>";

                $tr_html .="<tr><td><b>Appointment Confirmation</b></td><td>".$ACCount."</td><td>".$ACSTL."%</td><td>".$ACSTUL."%</td><td>".$ACCNTS."%</td><td>".$ACCTSV."%</td></tr>";
                
                $tr_html .="<tr><td><b>Appointment Visit</b></td><td>".$AVCount."</td><td>".$AVSTL."%</td><td>".$AVSTUL."%</td><td>".$AVCNTS."%</td><td>".$AVCTSV."%</td></tr>";

                $tr_html .="<tr><td><b>Conversions</b></td><td>".$convertedCount."</td><td>".$CSTL."%</td><td>".$CSTUL."%</td><td></td><td></td></tr></tbody>";

                $tr_html .="<tr><td><b>Status not known</b></td><td>".$SNK_arr['0']['count']."</td><td></td><td></td><td></td><td></td></tr></tbody>";

                $tr_html .="<tr><td><b>Appointment Missed</b></td><td>".$AM_arr['0']['count']."</td><td></td><td></td><td></td><td></td></tr></tbody>";
                // dd($tr_html);


        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/view-client/'.$lms_client_id);
        }
        return response()->json($tr_html);

    }
}
