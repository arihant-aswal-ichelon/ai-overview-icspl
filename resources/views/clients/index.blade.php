@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Client List</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Clients</li>
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
                        <a href="{{route('add-client')}}" class="btn btn-success">Add Client</a>
                    </div>
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Client</th>
                                    <th scope="col">Industry</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if($clients->isNotEmpty()){ ?>
                                    <?php foreach($clients as $key => $client){ ?>
                                        <tr>
                                            <td class="fw-medium">{{$key+1}}</td>
                                            <td>{{$client->name}}</td>
                                            <td>{{$client->industry}}</td>
                                            <td>{{$client->status}}</td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="{{url('edit-client/'.$client->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-pencil-box-multiple text-muted"></i> Edit</a> </li>
                                                        <li><a href="{{url('clients-properties/'.$client->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-delete-circle-outline text-muted"></i> Properties</a></li>
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