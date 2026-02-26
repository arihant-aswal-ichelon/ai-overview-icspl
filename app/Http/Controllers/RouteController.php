<?php

namespace App\Http\Controllers;
use App\Models\Client_routesModel;
use App\Models\DomainManagementModel;
use App\Models\Client_propertiesModel;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(string $id, string $did)
    {
        $clients = Client_routesModel::where('client_properties_id', $id)->where('domainmanagement_id', $did)->orderBy('id', 'desc')->get();
        // dd($clients);
        return view("clientRouts.index", compact("clients","id","did"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $id, string $did)
    {
        $client_data = Client_propertiesModel::where("id", $id)->first();
        $client = DomainManagementModel::where("id", $did)->first();
        // dd($client_data);
        return view("clientRouts.create", compact("client_data","client"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request);
        $data = [];
        $route = new Client_routesModel;
        $data = $request->all();

        $route->domainmanagement_id = $data["clientID"];
        $route->client_properties_id = $data["CilentPropID"];
        $route->routeName = $data["name"];
        
        $route->type = $data["status"];
        
        if ($route->save()) {

            $statusController = new StatusController();
            $data1 = "New Route Added - " . $route->name;
            $statusController->store($data1,$route->id);

            $request->session()->flash("message", "Route has been added successfully");
            return redirect()->route('clientsroute', [
                'id' => $data['CilentPropID'],
                'did' => $data['clientID']
            ]);
        } else {
            $request->session()->flash("error", "Unable to add route. Please try again later");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        unset($_SESSION['lms_client_check']);
        $client_data = DomainManagementModel::with('Client_properties')->where("id", $id)->get();
        return view("clientRouts.show", compact("client_data"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = Client_routesModel::find($id);
        $client_data = Client_propertiesModel::where("id", $data->client_properties_id)->first();
        $client = DomainManagementModel::where("id", $data->domainmanagement_id)->first();
        // dd($client_data);
        return view("clientRouts.edit", compact("data","client_data","client"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // dd($request);
        $data = [];
        $data = $request->all();

        // dd($data);
        $route = Client_routesModel::find($data['id']);
        
        $route->routeName = $data["name"];
        $route->type = $data["status"];
      
    
        if ($route->save()) {


            $request->session()->flash("message", "Route has been Updated successfully");
            return redirect()->route('clientsroute', [
                'id' => $data['CilentPropID'],
                'did' => $data['clientID']
            ]);
        } else {
            $request->session()->flash("error", "Unable to update route. Please try again later");
        }
    }
}
