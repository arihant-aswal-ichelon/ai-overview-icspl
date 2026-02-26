<?php

namespace App\Http\Controllers;
use App\Models\DomainManagementModel;
use App\Models\Slc_requestModel;
use App\Models\slc_client_requestModel;
use App\Models\Slc_dataModel;
use App\Models\Client_propertiesModel;
use Carbon\Carbon;
use PDO;

use Illuminate\Http\Request;

class SlcInvestementController extends Controller
{
    public function viewInvestement(string $id)
    {
        $data_request = slc_client_requestModel::where("client_id", $id)->get();

        $date_ranges = $data_request->pluck('date_range');

        // dd($date_ranges);

        $tr_html = "<thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Product</th><th>Source</th><th>Status</th><th>Amount Collected</th><th>Lead Entry CountCRM</th><th>CRM first entry Date</th><th>Entry Month</th><th>Lead Type</th><th>Consultation Type</th></tr></thead><tbody>";
        $client_data = Slc_dataModel::where("request_id", $id)->get();
        $domain = Slc_requestModel::where("request_id", $id)->get();
        $lms_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'lms')->get();

        $upload_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'upload')->get();
        
        // dd($domain);

        $lms_count = count($lms_data);
        $upload_count = count($upload_data);

        if($lms_count > $upload_count){

            foreach($lms_data as $data){
                $check_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'upload')->where("phone", $data->phone)->first();

                if(!empty($check_data)){
                    $tr_html .="<tr>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->product_price."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->lms_created_at."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }else{
                    $tr_html .="<tr style=background-color:yellow;>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->product_price."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->lms_created_at."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }

            }
            foreach($upload_data as $data){
                $check_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'lms')->where("phone", $data->phone)->first();
                
                if(empty($check_data)){
                    $tr_html .="<tr style=background-color:red;>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->revenue."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->visited_date."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }
            }

            $tr_html .="</tbody>";

            // dd($tr_html);
        }else{

            foreach($upload_data as $data){
                $check_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'lms')->where("phone", $data->phone)->first();
            // dd($check_data);

                if(!empty($check_data)){
                    $tr_html .="<tr>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->product_price."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->lms_created_at."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }else{
                    $tr_html .="<tr style=background-color:red;>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->product_price."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->lms_created_at."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }

            }

            foreach($lms_data as $data){
                $check_data = Slc_dataModel::where("request_id", $id)->where("data_from", 'upload')->where("phone", $data->phone)->first();
                
                if(empty($check_data)){
                    $tr_html .="<tr style=background-color:yellow;>";
                    $tr_html .= "<td>".$data->name."</td>";
                    $tr_html .= "<td>".$data->phone."</td>";
                    $tr_html .= "<td>".$data->email."</td>";
                    $tr_html .= "<td>".$data->product_name."</td>";
                    $tr_html .= "<td>".$data->lead_source_name."</td>";
                    $tr_html .= "<td>".$data->lead_status_name."</td>";
                    $tr_html .= "<td>".$data->product_price."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td>".$data->lms_created_at."</td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .= "<td></td>";
                    $tr_html .="</tr>";
                }
            }
            $tr_html .="</tbody>";

        }



        return view("SLC.investementview", compact('tr_html','date_ranges','id'));

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

}
