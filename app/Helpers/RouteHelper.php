<?php
namespace App\Helpers;
use App\Models\Client_routesModel;
use App\Models\Client_propertiesModel;

use Auth;

class RouteHelper{

    public static function get_Route($client_id, $url) {
        $lms_url = base64_decode($url);
        $client_data = Client_propertiesModel::where("domain", $lms_url)->first();

        $routes = Client_routesModel::where('client_properties_id', $client_data->id)->where('domainmanagement_id', $client_id)->orderBy('id', 'desc')->get();
        // dd($routes);

        return $routes;
    }

}