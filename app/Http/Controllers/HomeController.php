<?php

namespace App\Http\Controllers;

use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\DomainManagementModel;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $user = Auth::user();
        if($user->type == "SA"){
            $clients = DomainManagementModel::where('status', 'active')->orderBy('id', 'desc')->get();
            return view('home', compact('clients'));
        }else{
            $client_data = DomainManagementModel::with('Client_properties')->where("id", $user->domainmanagement_id)->get();
            return view("clients.show", compact("client_data"));
        }
    }

    public function gauth(Request $request, string $id=""){

        try{
            $client = new \Google_Client();
            $client->setApplicationName('ICGAnalytics');
            $client->setScopes([
                'https://www.googleapis.com/auth/youtube.readonly',
            ]);

            $client->setAuthConfig('../client_secrets.json');
            $client->setAccessType('offline');

            $get_gauth_client_id = Session::get('gauth_client_id');
            if(empty($id) && isset($get_gauth_client_id) && !empty($get_gauth_client_id)){
                $id = $get_gauth_client_id;
            }
            
            if(empty($id)){
                $request->session()->flash("error", "Invalid Request. Client not found!");
                return redirect("clients/".$id);
            }else{
                Session::put('gauth_client_id', $id);
                
                $client_data = DomainManagementModel::find($id);
                $authCode = $client_data->gauthcode;
                
                if (empty($authCode) && isset($_GET['code'])) {
                    $authCode = $_GET['code'];
                    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                    $client_data->grefreshtoken = $accessToken['refresh_token'];
                    $client_data->gaccesstoken = $accessToken['access_token'];
                    $client_data->gauthjson = $accessToken;
                    $client_data->gauthcode = $authCode;
                    $client_data->save();
                }

                if (empty($authCode)) {
                    $authUrl = $client->createAuthUrl();
                    echo "<script>window.open('".filter_var($authUrl, FILTER_SANITIZE_URL)."', '_self').focus();</script>";
                }else{
                    $request->session()->flash("message", "Auth token created successfully!");
                    return redirect("view-client/".$id);
                }
            }
        }catch(\PDOException $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/clients');
        } catch (\Throwable $ex) {
            $request->session()->flash("message", $ex->getMessage());
            return redirect('/clients');
        }
    }

    public function profile(){
        $user = Auth::user();
        $client = DomainManagementModel::where('id', $user->domainmanagement_id)->first();
        // dd($client);
        return view("profile", compact("user","client"));
    }

    public function profileEdit(){
        $user = Auth::user();
        $client = DomainManagementModel::where('id', $user->domainmanagement_id)->first();
        return view("edit", compact("user","client"));
    }

    public function profileUpdate(Request $request){

        $data = [];
        $data = $request->all();


        $userid = Auth::user();
        $client = DomainManagementModel::find($userid->domainmanagement_id);
        $user = User::find($userid->id);
        
        // dd($client);
        if(isset($request->name_update) && $client != null){
            $client->name = $data["name_update"];
        }

        if(isset($request->name_update)){
            $user->name = $data["name_update"];
        }else{
            $user->name = $data["name"];
        }

        if(isset($request->phone_update) && $client != null ){
            $client->phone = $data["phone_update"];
        }

        
        if(isset($request->password_update)){

            $user->password = Hash::make($data["password_update"]);
            // dd(Hash::make($data["password_update"]));
        }else{
            $user->password = Hash::make($data["password"]);
        }

        if($client != null ){
            $client->save();
        }
        
        $user->save();
        return view("profile", compact("user","client"));
    }
}
