<?php

namespace App\Http\Controllers;
use PDO;

use Illuminate\Http\Request;
use App\Models\DomainManagementModel;
use App\Models\BudgetModel;
use App\Models\Investment_clientModel;
use App\Models\Client_propertiesModel;
use App\Http\Controllers\StatusController;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        return view("budget.index", compact("clients"));
    }


    public function create()
    {
        $clients = DomainManagementModel::orderBy('id', 'desc')->get();
        return view("budget.create", compact("clients"));
    }

    public function fetch(Request $request)
    {
        $clients = DomainManagementModel::Where('id', $request->clientId)->get();
        $domain = Client_propertiesModel::Where('domainmanagement_id', $request->clientId)->get();

        return response()->json($domain);
    }

    public function fetchsource(Request $request)
    {
        $data = explode("|", $request->domain);
        $domain = Client_propertiesModel::Where('id', $data['0'])->first();

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
        
        
            // $servername = "127.0.0.1";
            // $username = "root";
            // $password = "";
            // $dbName = "primeivfcrm";
            
            $lms_pdo = new PDO("mysql:host=$servername;dbname=$dbName", $username, $password);
            $lms_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            //Get Lead Sources
            $lms_source_stmt = $lms_pdo->prepare("SELECT lead_sources.name as lead_source_name,lead_sources.id as lead_source_id FROM `leads` inner join lead_sources on leads.lead_source_id=lead_sources.id group by lead_source_id;");
            $lms_source_stmt->execute();
            $lms_sources = $lms_source_stmt->fetchAll();



        return response()->json($lms_sources);
    }

    public function store(Request $request)
    {
        $data = [];
        $data = $request->all();
        $clientID = $data['clientID'];
        $clients = DomainManagementModel::where('id', $clientID)->first();


        // dd($data);
        $investment = new Investment_clientModel;

        $investment->domainmanagement_id = $clientID;
        $parts = explode('|', $data['domain']);
        $investment->client_properties_id = $parts["0"];
        $investment->client_properties_domain = $parts["1"];
        $investment->date_range = $data["lms_daterange"];
        $investment->client_name = $clients["name"];
        $investment->save();
   
        foreach($data['source'] as $key => $source){
            $budugt = new BudgetModel;
            $budugt->investment_client_id = $investment->id;
            $budugt->amount = $data['amount'][$key];
            $parts = explode('|', $data['source'][$key]);
            $budugt->source_id = $parts['0'];
            $budugt->source_name = $parts['1'];
            $budugt->save();
        }

            $statusController = new StatusController();
            $data = "New Budget Added for " . $clients->name;
            $statusController->store($data,$clientID);

            $request->session()->flash("message", $data);
            return redirect('/budgets');
        
    }

    public function show($id)
    {
        $client = DomainManagementModel::find($id);

        $investmentsWithBudget = $client->investment_client()->with('budget')->get();

        // dd($investmentsWithBudget);

        return view("budget.show", compact("investmentsWithBudget"));
    }

    public function edit($id)
    {
        $budget = BudgetModel::where('id', $id)->first();
        return view("budget.edit", compact("budget"));
    }

    public function update(Request $request)
    {
        $budget = BudgetModel::where('id', $request->id)->first();
        $id = Investment_clientModel::where('id', $budget->investment_client_id)->first();
        $clientID = $id->domainmanagement_id;
        $field = [];
        
        if(isset($request->amount_update)){
            $budget->amount = $request->amount_update;
        }else{
            $budget->amount = $request->amount;
        }
        
        if ($budget->save()) {
            foreach($request->request as $key => $data ){
                if(strpos($key, '_update')){
                    $field[] = $key;
                }
            }

            $statusController = new StatusController();
            $data = $field;
            $statusController->update($data,$clientID);


            $request->session()->flash("message", "Client Property has been updated successfully");
            return redirect('/clients');
        } else {
            $request->session()->flash("error", "Unable to Update client property. Please try again later");
        }
    }
}
