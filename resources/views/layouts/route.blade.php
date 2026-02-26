<?php

use App\Helpers\RouteHelper;

$routes = RouteHelper::get_Route($lms_data['lms_client_id'],  $lms_data['lms_url']);
// dd($routes);
?>
<div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                    <div class="col-lg-12 mt-12 d-flex justify-content-end" >
                            <form class="ms-1" action="{{ route('present-client-lms-step-1', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="height : 56px;" id="LeadCount">Overview</button>
                            </form>
                            <form class="ms-1" action="{{ route('present-client-lms-conversion', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="SalesFunnel">Sales Funnel</button>
                            </form>
                            <form class="ms-1" action="{{ route('telecaller', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="Telecallerefficiency">Telecaller efficiency</button>
                            </form>
                            <form class="ms-1" action="{{ route('present-client-lms-callreport', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="CallReport">Callback Report</button>
                            </form>
                            <form class="ms-1" action="{{ route('present-client-lms-dailyreport', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="DailyReport">Daily Report</button>
                            </form>
                            <form class="ms-1" action="{{ route('present-client-lms-leadslog', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="GradeReport">Log Report</button>
                            </form>
                            <form class="ms-1" action="{{ route('ComparisonDay', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="font-size: 11px; height : 56px;" id="ComparisonReportDay">Comparison Report Day Wise</button>
                            </form>
                            <form class="ms-1" action="{{ route('Comparison', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="ComparisonReport">Comparison Report</button>
                            </form>
                            <form class="ms-1" action="{{ route('financial', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" id="financial" style="height : 56px;">Financial</button>
                                </form>
                            <form class="ms-1" action="{{ route('group-analysis', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="groupanalysis">Group Report</button>
                            </form>
                           
                            <!-- <form class="ms-1" action="{{ route('leadReport', ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary" id="LeadReport">Lead Report</button>
                            </form> -->
                           @foreach($routes as $route)
                            @if($route->type == "Active")
                                <form class="ms-1" action="{{ route($route->routeName, ['id' => $lms_data['lms_client_id'],'url' => $lms_data['lms_url']]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" style="height : 56px;" id="{{$route->routeName}}">{{ucfirst($route->routeName)}}</button>
                                </form>
                                @endif
                            @endforeach
                        </div>

                </div>
            </div>
        </div>