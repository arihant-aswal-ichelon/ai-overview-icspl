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

class SlcController extends Controller
{

    public function show(string $id)
    {
        $client_data = DomainManagementModel::with('Client_properties')->where("id", $id)->get();
        $data_request = slc_client_requestModel::where("client_id", $id)->get();
        // dd($client_data,$data_request);

        return view("SLC.show", compact('data_request','client_data','id'));

    }
    public function add(string $id, Request $request)
    {
        // dd($request);
        $client_data = DomainManagementModel::with('Client_properties')->where("id", $id)->get();
        // $data_request = Slc_requestModel::where("client_id", $id)->get();
        $data = explode("|", $request->domain);
        // dd($client_data);

        $lms_data = $filter_arr = [];

        if(isset($data[0]) && !empty($data[0])){    

            $lms_data['domain'] = $data[0]; 

        
        }else{
            $lms_data['domain'] = null;
            
        }

        try {
            // var_dump($_POST);die;            
            // $lms_url = base64_decode($lms_url);
            // $db_encoded = file_get_contents($lms_url.'statuslog.txt');
            // if(isset($db_encoded) && !empty($db_encoded)){
            //     $db_decode = json_decode($db_encoded);
            //     if(isset($db_decode) && !empty($db_decode)){
            //         if($lms_url == $db_decode->site_url){
                        
            //             /**LMS Server*/
            //             $servername = $db_decode->access->ip;
            //             $username = $db_decode->access->user;
            //             $password = $db_decode->access->pass;
            //             $dbName = $db_decode->access->name;

            //             $error = true;
            //         }
            //     }
            // }
            // if(!$error){
            //     $request->session()->flash("message", "Invalid Request!");
            //     return redirect('/view-client/'.$lms_client_id);
            // }

             /** Local Server */
             $servername = "127.0.0.1";
             $username = "root";
             $password = "";
             $dbName = "primeivfcrm_new";
             
             
             $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
             $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             
            if(isset($_POST['lms_daterange']) && !empty($_POST['lms_daterange'])){    

                $lms_data['lms_daterange'] = $_POST['lms_daterange']; 

                //Get Lead Sources
             $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
             $lms_source_stmt->execute();
             $lms_sources = $lms_source_stmt->fetchAll();
             $lms_data['lead_sources'] = $lms_sources;
 
             //Get Lead Stages
             $lms_stages_stmt = $lms_pdo->prepare("SELECT `id`, `name` FROM `lead_statuses` where `status`='active';");
             $lms_stages_stmt->execute();
             $lms_stages = $lms_stages_stmt->fetchAll();
             $lms_data['lms_stages'] = $lms_stages;
            
            }else{
             $lms_data['lms_daterange'] = null;
             $lms_data['lms_stages'] = null;
             $lms_data['lead_sources'] = null;
                
            }

            //  $client_data[0]->Client_properties

        } catch(\PDOException $ex) {
            // $request->session()->flash("message", $ex->getMessage());
            // return redirect('/view-client/'.$lms_client_id);
        } catch (\Throwable $ex) {
            // $request->session()->flash("message", $ex->getMessage());
            // return redirect('/view-client/'.$lms_client_id);
        }
        return view("SLC.add", compact('client_data','lms_data'));
    }

    public function fetchsourcestate(Request $request)
    {
        $data = explode("|", $request->domain);
        $domain = Client_propertiesModel::Where('id', $data['0'])->first();
        $lms_data = [];

        $db_encoded = file_get_contents($domain->domain.'statuslog.txt');
        
        if(isset($db_encoded) && !empty($db_encoded)){
            $db_decode = json_decode($db_encoded);
            
            if(isset($db_decode) && !empty($db_decode)){
                
                if($data['1'] == $db_decode->site_url){
                    
                    /**LMS Server*/
                    $servername = $db_decode->access->ip;
                    $username = $db_decode->access->user;
                    $password = $db_decode->access->pass;
                    $dbName = $db_decode->access->name;
                    
                }
            }
        }
        
        
            $servername = "127.0.0.1";
            $username = "root";
            $password = "";
            $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();
            $lms_data['lead_sources'] = $lms_sources;

            //Get Lead Stages
            $lms_stages_stmt = $lms_pdo->prepare("SELECT lead_statuses.name as lead_statuses_name,lead_statuses.id as lead_statuses_id FROM `leads` inner join `lead_statuses` on leads.lead_status_id=lead_statuses.id group by lead_status_id;");
            $lms_stages_stmt->execute();
            $lms_stages = $lms_stages_stmt->fetchAll();
            $lms_data['lms_stages'] = $lms_stages;



        return response()->json($lms_data);
    }

    public function store(Request $request)
    {

        $client_requests = slc_client_requestModel::with('Slc_request')->Where('client_id', $request->clientID)->Where('date_range', $request->lms_daterange)->get();

        // $message = "";
        $domain_id = $domain_name = "";
        $data = explode('|', $request->domain);
        $domain_id = $data[0];
        $domain_name = $data[1];
        // dd($request);
        
        foreach($request->lms_sources as $key){
            $data = explode('|', $key);
            $source_id[] = $data[0];
            $source_name[] = $data[1];
        }
        $source_id_data = implode(',', $source_id);
        $source_name_data = implode(',', $source_name);
        
        // dd($data);
        foreach($request->lms_stages as $key){
            $data = explode('|', $key);
            $statuse_id[] = $data[0];
            $statuse_name[] = $data[1];
        }
        $statuse_id_data = implode(',', $statuse_id);
        $statuse_name_data = implode(',', $statuse_name);

        if(count($client_requests) == 0){
            
            $new_client_request = new slc_client_requestModel;
            $new_client_request->client_id = $request->clientID;
            $new_client_request->date_range = $request->lms_daterange;
            $new_client_request->type = 'processing';
            $new_client_request->save();

            $new_request = new Slc_requestModel;
            $new_request->request_id = $new_client_request->id;
            $new_request->source = $source_id_data;
            $new_request->statuse = $statuse_id_data;
            $new_request->client_properties_domain = $domain_id;
            $new_request->domain_name = $domain_name;
            $new_request->source_name = $source_name_data;
            $new_request->statuse_name = $statuse_name_data;
            $new_request->type = 'processing';
            $new_request->save();

        }else{
            
            $client_requests_processing = Slc_requestModel::Where('request_id', $client_requests[0]->id)->Where('type', 'processing')->get();
            $flag = false;

            if(count($client_requests[0]->Slc_request) == count($client_requests_processing))
            {
                foreach($client_requests[0]->Slc_request as $data){
                    if($data->client_properties_domain != $domain_id){
                        $flag = true;
                    }
                }
            }
            // dd($flag);

            if($flag){
                $new_request = new Slc_requestModel;
                $new_request->request_id = $client_requests[0]->id;
                $new_request->source = $source_id_data;
                $new_request->statuse = $statuse_id_data;
                $new_request->client_properties_domain = $domain_id;
                $new_request->domain_name = $domain_name;
                $new_request->source_name = $source_name_data;
                $new_request->statuse_name = $statuse_name_data;
                $new_request->type = 'processing';
                $new_request->save();
            }


        }
       

        // $source = implode(',', $request->source);
        // $statuse = implode(',', $request->statuse);

       
        // $request->session()->flash("message", $message);
        return redirect()->route('view-client-slc-add', ['id' => $request->clientID]);

    }

    public function fetchdata(){
        $client_requests = slc_client_requestModel::with('Slc_request')->Where('type', 'processing')->get();
        foreach($client_requests as $data){
            
            $date = self::getStartAndEndDate($data->date_range);
            
            $start_date = date("Y-m-d 00:00:00", strtotime($date[0]));
            $end_date = date('Y-m-d 00:00:00', strtotime($date[1] .' +1 day'));
            foreach($data->Slc_request as $domain){
                
                if($domain->type == 'processing'){
                    // dd($domain);
                    try {
                        // var_dump($_POST);die;            
                        // $lms_url = base64_decode($lms_url);
                        // $db_encoded = file_get_contents($lms_url.'statuslog.txt');
                        // if(isset($db_encoded) && !empty($db_encoded)){
                        //     $db_decode = json_decode($db_encoded);
                        //     if(isset($db_decode) && !empty($db_decode)){
                        //         if($lms_url == $db_decode->site_url){
                                    
                        //             /**LMS Server*/
                        //             $servername = $db_decode->access->ip;
                        //             $username = $db_decode->access->user;
                        //             $password = $db_decode->access->pass;
                        //             $dbName = $db_decode->access->name;
            
                        //             $error = true;
                        //         }
                        //     }
                        // }
                        // if(!$error){
                        //     $request->session()->flash("message", "Invalid Request!");
                        //     return redirect('/view-client/'.$lms_client_id);
                        // }
            
                         /** Local Server */
                         $servername = "127.0.0.1";
                         $username = "root";
                         $password = "";
                         $dbName = "primeivfcrm_new";
                         
                         
                         $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
                         $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                         
            
                        //Get Lead Sources
                        $lms_leads_stmt = $lms_pdo->prepare("SELECT leads.name as name, leads.phone as phone, leads.email as email, leads.product_id as product_id, leads.lead_source_id as lead_source_id, leads.lead_status_id as lead_status_id, leads.created_at as created_at, leads.updated_at as updated_at, products.name as product_name, products.price as product_price, lead_sources.name as source_name, lead_statuses.name as statuses_name FROM `leads` Left join lead_sources on leads.lead_source_id = lead_sources.id Left join lead_statuses on leads.lead_status_id = lead_statuses.id Left join products on leads.product_id = products.id where lead_source_id in (".$domain->source.") and lead_status_id in (".$domain->statuse.") and leads.created_at >= '".$start_date."' and leads.created_at <= '".$end_date."' ");
                        // dd($lms_leads_stmt);
                        $lms_leads_stmt->execute();
                        $lms_leads = $lms_leads_stmt->fetchAll();
                         

                        foreach($lms_leads as $leads){
                             
                            $new_data = new Slc_dataModel;
                             
                            $new_data->client_id = $client_requests[0]->client_id;
                            $new_data->request_id = $data->id; 
                            $new_data->request_client_id = $domain->id;
                            $new_data->data_from = 'lms';
                            $new_data->name = $leads['name'];
                            $new_data->phone = $leads['phone'];
                            $new_data->email = $leads['email'];
                            $new_data->product_id = $leads['product_id'];
                            $new_data->product_name = $leads['product_name'];
                            $new_data->product_price = $leads['product_price'];
                            $new_data->lead_source_id = $leads['lead_source_id'];
                            $new_data->lead_source_name = $leads['source_name'];
                            $new_data->lead_status_id = $leads['lead_status_id'];
                            $new_data->lead_status_name = $leads['statuses_name'];
                            $new_data->lms_created_at = $leads['created_at'];
                            $new_data->lms_updated_at = $leads['updated_at'];
                            // dd($new_data);

                            $new_data->save();
                             
                        }

            
                        //  $client_data[0]->Client_properties
            
                    } catch(\PDOException $ex) {
                        // $request->session()->flash("message", $ex->getMessage());
                        // return redirect('/view-client/'.$lms_client_id);
                    } catch (\Throwable $ex) {
                        // $request->session()->flash("message", $ex->getMessage());
                        // return redirect('/view-client/'.$lms_client_id);
                    }
                    $data_request = Slc_requestModel::where("id", $domain->id)->first();
                    $data_request->type = 'complete';
                    $data_request->save();                    
                }
                
            }
            $data->type = 'complete';
            $data->save(); 
            // dd($data);
        }
        dd('save');

    }

    public function getStartAndEndDate($monthYear) {


        $dateTime = Carbon::createFromFormat('M, Y', $monthYear);
    
        
        $startDate = $dateTime->startOfMonth()->toDateString();
        
        $endDate = $dateTime->endOfMonth()->toDateString();
        // dd( $endDate);

        
        return [$startDate, $endDate];
    }

    public function showdomain(string $id)
    {

        $data_client_request = slc_client_requestModel::where("id", $id)->first();
        $data_request = Slc_requestModel::where("request_id", $id)->get();
        
        $client_data = DomainManagementModel::with('Client_properties')->where("id", $data_client_request->client_id)->get();
        // dd($client_data);

        return view("SLC.showdomain", compact('data_request','client_data','id'));

    }

    public function storeuplodeddata(Request $request, string $id)
    {

        dd($request->revenew);
        
        $file = $request->file('file');
        $data_client_request = slc_client_requestModel::where("id", $id)->first();


        // if (!empty($file)) {

        //     // File Details
        //     $filename = $file->getClientOriginalName();
        //     $extension = $file->getClientOriginalExtension();
        //     $tempPath = $file->getRealPath();
        //     $fileSize = $file->getSize();
        //     $mimeType = $file->getMimeType();

        //     // Valid File Extensions
        //     $valid_extension = array("csv");

        //     // 2MB in Bytes
        //     $maxFileSize = 2097152;

        //     // Check file extension
        //     if (in_array(strtolower($extension), $valid_extension)) {

        //         // Check file size
        //         if ($fileSize <= $maxFileSize) {

        //             // File upload location
        //             //$location = 'uploads';
        //             $location = '../public_html/slcupload/uploads';

        //             // Upload file
        //             $file->move($location, $filename);

        //             // Import CSV to Database
        //             //$filepath = public_path($location."/".$filename);
        //             $filepath = $location . "/" . $filename;

        //             // Reading file
        //             $file = fopen($filepath, "r");
                    

        //             $import_arr = array();
        //             $importData_arr = array();
        //             $last12Months = array();
        //             // $series = [];

        //             $i = 0;

        //             while (($filedata = fgetcsv($file, 1000, ",")) !== false) {
        //                 $num = count($filedata);

        //                 // Skip first row (Remove below comment if you want to skip the first row)
        //                 if($i == 0){
        //                     for ($c = 0; $c < $num; $c++) {
        //                         $import_arr[$i][] = $filedata[$c];
        //                     }
        //                 $i++;
        //                 continue;
        //                 }
        //                 for ($c = 0; $c < $num; $c++) {
        //                     $importData_arr[$i][] = $filedata[$c];
        //                 }
        //                 $i++;
        //             }

                    
        //             fclose($file);
                    
        //             foreach($importData_arr as $data){
                        
                        
        //                 $dateString = $data[3];
                        
        //                 $date = Carbon::createFromFormat('m/d/Y', $dateString);
                        
        //                 // Format the Carbon instance to the desired format
        //                 $formattedDate = $date->format('Y-m-d H:i:s');

        //                 $new_data = new Slc_dataModel;
        //                 $new_data->client_id = $data_client_request->client_id;
        //                 $new_data->request_id = $data_client_request->id;
        //                 $new_data->data_from = 'upload';
        //                 $new_data->name = $data[0];
        //                 $new_data->phone = $data[1];
        //                 $new_data->revenue = $data[2];
        //                 $new_data->visited_date = $formattedDate;
        //                 // $new_data->save();
        //             }


        //             $request->session()->flash("message", "Data have been imported successfully");
        //         } else {
        //             $request->session()->flash("error", "File too large. File must be less than 2MB.");

        //         }

        //     } else {
        //         $request->session()->flash("error", "Invalid File Extension.");
        //     }
        // }


        $upload_data = Slc_dataModel::where("client_id", $data_client_request->client_id)->where("request_id", $data_client_request->id)->where('data_from', 'upload')->get();

        foreach($upload_data as $updata){
            $lms_data = Slc_dataModel::where("client_id", $data_client_request->client_id)->where("request_id", $data_client_request->id)->where('phone', $updata->phone)->where('data_from', 'lms')->get();
            if(!empty($lms_data)){
                foreach($lms_data as $lmsdata){
    
                    if($updata->visited_date < $lmsdata->lms_created_at){
                        $lmsdata->lead_type = 'existing';
                    }else{
                        $lmsdata->lead_type = 'new';
                    }

                    $lmsdata->save();
                    
                }
            }

            if(!empty($request->revenew)){

            }
            
        }
        dd($lmsdata);

        $client_data = DomainManagementModel::with('Client_properties')->where("id", $data_client_request->client_id)->get();
        // dd($client_data);

        return view("SLC.showdomain", compact('data_request','client_data','id'));

    }

    public function viewdata(string $id)
    {

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



        return view("SLC.tableview", compact('tr_html','id'));

    }
}
