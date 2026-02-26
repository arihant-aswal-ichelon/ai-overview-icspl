<?php

namespace App\Http\Controllers;

use App\Models\GroupTypeModel;
use App\Models\GroupModel;
use Illuminate\Http\Request;
use App\Models\DomainManagementModel;
use App\Models\Assigne_groupsModel;
use App\Models\Client_propertiesModel;
use Illuminate\Support\Facades\Auth;
use PDO;

class GroupingController extends Controller
{
    public function index()
    {
        $types = GroupTypeModel::with('table1')->orderBy('id', 'desc')->get();


        // dd($types);
        return view("group.index", compact("types"));
    }

    public function create()
    {
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        return view("group.create", compact("clients"));
    }

    public function store(Request $request)
    {
        $data = [];
        $data = $request->all();
        // dd($data);
        $type = new GroupTypeModel;
        $type->name = $data['groupType'];
        $type->domainmanagement_id = $data['clientID'];
        $type->save();
        return redirect('/group-type');
        
    }

    public function edit($id)
    {
        $group = GroupTypeModel::where('id', $id)->first();
        // dd($group);
        return view("group.edit", compact("group"));
    }

    public function update(Request $request)
    {
        $group = GroupTypeModel::where('id', $request->id)->first();
        $group->name = $request->groupType;
        if ($group->save()) {
            // foreach($request->request as $key => $data ){
            //     if(strpos($key, '_update')){
            //         $field[] = $key;
            //     }
            // }
            // $statusController = new StatusController();
            // $data = $field;
            // $statusController->update($data,$clientID);
            $request->session()->flash("message", "Group Type  has been updated successfully");
            return redirect('/group-type');
        } else {
            $request->session()->flash("error", "Unable to Update Group Type. Please try again later");
        }
    }

    public function groupindex()
    {
        $types = GroupModel::with('table1', 'table2', 'table3')->orderBy('id', 'desc')->get();
        // dd($types);
        return view("group.groups.index", compact("types"));
    }

    public function groupcreate()
    {
        $user = Auth::user();
        if($user->type == "SA"){
            $clients = DomainManagementModel::where('status','active')->orderBy('id', 'desc')->get();
            $types = GroupTypeModel::orderBy('id', 'desc')->get();
        }else{
            $clients = DomainManagementModel::where("id", $user->domainmanagement_id)->get();
            $types = GroupTypeModel::where("domainmanagement_id", $user->domainmanagement_id)->orderBy('id', 'desc')->get();
        }
        return view("group.groups.create", compact("clients","types"));
    }

    public function groupstore(Request $request)
    {
        $data = [];
        $data = $request->all();

        $group = new GroupModel;

        $group->name = $data['groupName'];
        $group->group_for = $data['groupFor'];
        $group->group_for = $data['groupFor'];
        $group->group_type_id = $data['groupType'];
        $group->client_id = $data['clientID'];

        $domain_id =  explode("|",$data['domain']);
        $group->client_properties_id = $domain_id[0];

        $group->save();
            // $statusController = new StatusController();
            // $data = "New Budget Added for " . $clients->name;
            // $statusController->store($data,$clientID);

            // $request->session()->flash("message", $data);
            return redirect('/group');
        
    }
    public function groupedit($id)
    {
        $group = GroupModel::with('table1', 'table2', 'table3')->where('id', $id)->first();
        $types = GroupTypeModel::orderBy('id', 'desc')->get();
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        // dd($group);
        return view("group.groups.edit", compact("group","types","clients"));
    }

    public function groupupdate(Request $request)
    {
        $group = GroupModel::where('id', $request->id)->first();
        // dd($group );
 
        $group->name = $request->groupName;
        $group->group_for = $request->groupFor;
        $group->group_type_id = $request->groupType;
    
        if ($group->save()) {
            // foreach($request->request as $key => $data ){
            //     if(strpos($key, '_update')){
            //         $field[] = $key;
            //     }
            // }

            // $statusController = new StatusController();
            // $data = $field;
            // $statusController->update($data,$clientID);S
            $request->session()->flash("message", "Group  has been updated successfully");
            return redirect('/group');
        } else {
            $request->session()->flash("error", "Unable to Update Group. Please try again later");
        }
    }

    public function assignegroup()
    {
        $types = GroupTypeModel::orderBy('id', 'desc')->get();
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        // dd($types);
        return view("group.assigneindex", compact("clients","types"));
    }

    public function fetch(Request $request)
    {
        try {
            $error = false;
            $groupData = $res_arr = [];
            $data = explode("|", $request->domain);
            
            $clients = Client_propertiesModel::where('id', $data['0'])->first();

            $group = GroupModel::where('client_id', $clients->domainmanagement_id)->where('client_properties_id', $data['0'])->where('group_for', $request->type)->get();
            
            $groupData['group'] = $group;
            $groupData['type'] = $request->type;
            
            $db_encoded = file_get_contents($clients->domain.'statuslog.txt');

            if(isset($db_encoded) && !empty($db_encoded)){
                $db_decode = json_decode($db_encoded);
                if(isset($db_decode) && !empty($db_decode)){
                    if($clients->domain == $db_decode->site_url){
                        
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
                // return redirect('/view-client/'.$lms_client_id);
            }

            /** Local Server */
            // $servername = "127.0.0.1";
            // $username = "root";
            // $password = "";
            // $dbName = "primeivfcrm_new";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $query = "";
            $tr_html = "<thead><tr><th>Ids</th>";

            if($request->type == "Facebook"){

                if($request->for_ad_page == "0"){
                    $query = "SELECT DISTINCT(ad_id) as dataid FROM `leads` WHERE ad_id IS NOT NULL;"; 
                    $tr_html .= "<th>Facebook Ads Name</th>";
                }
                if($request->for_ad_page == "1"){
                    $query = "SELECT DISTINCT(fbform_id) as dataid FROM `leads` WHERE fbform_id IS NOT NULL;";
                    $tr_html .= "<th>Facebook Forms Name</th>";
                }
            }
            if($request->type == "Google"){

                if($request->for_ad_page == "0"){
                    $query = "SELECT DISTINCT(gadgroupid) as dataid FROM `leads` WHERE gadgroupid IS NOT NULL;";
                    $tr_html .= "<th>Google Ads Name</th>";

                }
                if($request->for_ad_page == "1"){
                    $query = "SELECT DISTINCT(gcampaignid) as dataid FROM `leads` WHERE gcampaignid IS NOT NULL;";
                    $tr_html .= "<th>Google Forms Name</th>";

                }
            }
                $fbPages_stmt = $lms_pdo->prepare($query);
                $fbPages_stmt->execute();
                $fbPages = $fbPages_stmt->fetchAll();
                $res_arr = $fbPages;

            
            foreach($group as $grp){
                $tr_html .= "<th>".$grp->name."</th>";

            }
            $tr_html .= "</tr></thead><tbody>"; 
            foreach($res_arr as $key => $ssss){
                $tr_html .="<tr>";
                $tr_html .= "<td>".$ssss['dataid']."</td>";
                $tr_html .= "<td>".$ssss['dataid']."</td>";
                foreach($group as $grp){ 
                $check = self::group_data($ssss['dataid'], $grp->id);
                    $checked="";
                    if($check == 1){ $checked = "checked"; }
                    $tr_html .= "<td><input type='checkbox' ".$checked."  name='group[".$ssss['dataid']."][".$grp->id."]' /> ".$grp->name."</td>";
                    // var_dump($tr_html);die;
                }
                $tr_html .="</tr>";
            }
            $tr_html .="</tbody>";
            // var_dump($tr_html);die;
            $groupData['tr_html'] = $tr_html;
            // dd($fbPages_stmt);

            return response()->json($groupData);
        } catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            // return redirect('/view-client/'.$lms_client_id)
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            // return redirect('/view-client/'.$lms_client_id)
        }
    }

    public function group_data($data_id, $group_id){

        $data = Assigne_groupsModel::where('ids', $data_id)->where('group_id', $group_id)->get();
        $count = $data->count();
        return $count;
    }

    public function assigneStore(Request $request)
    {
        if($request->groupFor_post == '0' ){
            $data = Assigne_groupsModel::with(['table1' => function ($query) {$query->where('group_for', 'Facebook'); }])->where('client_properties_id', $request->domainID_post)->where('client_id', $request->client_id_post)->where('ad_forms', $request->ad_page_post)->whereHas('table1', function ($query) {$query->where('group_for', 'Facebook');})->get();
        }
        if($request->groupFor_post == '1' ){
            $data = Assigne_groupsModel::with(['table1' => function ($query) {$query->where('group_for', 'Facebook'); }])->where('client_properties_id', $request->domainID_post)->where('client_id', $request->client_id_post)->where('ad_forms', $request->ad_page_post)->whereHas('table1', function ($query) {$query->where('group_for', 'Google');})->get();
        }
        // dd($data);

        
        if(!empty($data)){
            foreach($data as $key)
            {
                $key->delete();
            }
            // dd($data);
        }
            
        foreach($request->group as $key => $ids){
            foreach($ids as $key1 => $group_id){
                $assigne = new Assigne_groupsModel;
                $assigne->client_properties_id = $request->domainID_post;
                $assigne->client_id = $request->client_id_post;
                $assigne->ad_forms = $request->ad_page_post;
                $assigne->ids = $key;
                $assigne->group_id = $key1;
                $assigne->save();
            }

                
        }
        return redirect('/group');

    }
}


