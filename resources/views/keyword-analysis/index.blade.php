@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Keywords List</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Keywords</li>
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
                        <a href="{{route('add-keyword-request', ['id' => $id])}}" class="btn btn-success">Add Keyword</a>
                    </div>
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Keyword</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if($keywordRequest->isNotEmpty()){ ?>
                                    <?php foreach($keywordRequest as $key => $keyword){ ?>
                                        <tr>
                                            <td class="fw-medium">{{$key+1}}</td>
                                            <td>{{$keyword->keyword}}</td>
                                            <td>{{$keyword->created_at}}</td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="{{url('keyword-analysis/'.$keyword->domainmanagement_id.'/'.$keyword->client_property_id.'/'.$keyword->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-delete-circle-outline text-muted"></i> Keywords Analysed</a></li>
                                                        <li><a href="{{url('generate-aio-prompt/'.$keyword->domainmanagement_id.'/'.$keyword->client_property_id.'/'.$keyword->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-delete-circle-outline text-muted"></i> Generate AIO Prompt</a></li>
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