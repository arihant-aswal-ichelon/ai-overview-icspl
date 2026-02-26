@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">{{$client[0]->name}} Properties</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('clients')}}">Clients</a></li>
                            <li class="breadcrumb-item active">Clients Properties</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <a href="{{route('add-client-properties',$client[0]->id)}}" class="btn btn-success">Add Property</a>
                    </div>
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Property</th>
                                    <th scope="col">Domain</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if($client_data->isNotEmpty()){ ?>
                                    <?php foreach($client_data as $key => $data){ ?>
                                        <tr>
                                            <td class="fw-medium">{{$key+1}}</td>
                                            <td>{{$data->type}}</td>
                                            <td>{{$data->domain}}</td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="{{url('edit-client-properties/'.$data->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-pencil-box-multiple text-muted"></i> Edit</a> </li>
                                                        <li><a href="{{url('clientsroute/'.$data->id.'/'.$data->domainmanagement_id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-pencil-box-multiple text-muted"></i> Routes</a> </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php }else{ ?>
                                    <tr>
                                        <td colspan="5">No Record!!</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!--end col-->
        </div><!--end row-->
    </div>
</div>
@endsection