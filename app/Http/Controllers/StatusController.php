<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Models\StatusModel;

use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function store($data, $id)
    {
        $log = new StatusModel;
        $log->user_id = Auth::id();
        $log->client_id = $id;
        $log->data = $data;
        // dd(json_encode($data));
        $log->save();

        return response()->json(['message' => 'Status saved successfully'], 200);

    }

    public function update($request, $id)
    {
        $log = new StatusModel;
        $log->user_id = Auth::id();
        $log->client_id = $id;
        $log->data = json_encode($request);
        // dd(json_encode($data));
        $log->save();

        return response()->json(['message' => 'Status saved successfully'], 200);

    }
}
