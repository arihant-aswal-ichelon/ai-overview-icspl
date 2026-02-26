@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Client Properties</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{route('home')}}">Clients</a></li>
                            <li class="breadcrumb-item active">Client Properties</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Property</th>
                                    <th scope="col">Domain</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($client_data->isNotEmpty()){ ?>
                                    <?php foreach($client_data as $key => $client){  ?>
                                        <?php 
                                            foreach($client->Client_properties as $lms){ 
                                        ?>
                                            <tr>
                                                @if($lms->type == 'lms')
                                                <td>LMS</td>
                                                @elseif($lms->type == 'website')
                                                <td>website</td>
                                                @elseif($lms->type == 'landing')
                                                <td>website</td>
                                                @endif
                                                <td>{{$lms->domain}}</td>
                                                <td>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-soft-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ri-more-fill align-middle"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <form action="{{ url('present-client/'.$lms->type.'/'.$client->id.'/'.base64_encode($lms->domain)) }}" method="POST" class="dropdown-item">
                                                                    @csrf <!-- Include CSRF token for Laravel applications -->
                                                                    <input type="hidden" name="_method" value="POST"> <!-- Specify POST method -->
                                                                    <button type="submit" class="btn btn-link" style="text-align:left; padding:0; border:none; background:none;">
                                                                        <i class="mdi_icon mdi mdi-graphql text-muted"></i> Record
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php  } ?>
                                        
                                    <?php } ?>
                                <?php }else{ ?>
                                    <tr>
                                        <td colspan="4">No Record!!</td>
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