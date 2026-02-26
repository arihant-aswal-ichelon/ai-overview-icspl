@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('clients')}}">Clients</a></li>
                            <li class="breadcrumb-item active">Edit Route</li>
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
                        <div class="live-preview">
                            <form class="row g-3" method="post" action="{{url('edit-client-route')}}">
                                @csrf
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Client Name*</label>
                                    <input type="text" name="ClientName" class="form-control" id="validationDefault01" value="{{$client->name}}" readonly>
                                    <input type="text" name="clientID" class="form-control" id="validationDefault01" value="{{$client->id}}" hidden>
                                </div>
                                <input type="text" name="id" class="form-control" id="id" value="{{$data->id}}" hidden>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Client Property*</label>
                                    <input type="text" name="CilentProp" class="form-control" id="validationDefault01" value="{{$client_data->domain}}" readonly>
                                    <input type="text" name="CilentPropID" class="form-control" id="validationDefault01" value="{{$client_data->id}}" hidden>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Route*</label>
                                    <input type="text" name="name" class="form-control" id="validationDefault01" value="{{$data->routeName}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Status</label>
                                    <div class="form-check">
                                        <input type="radio" value="active" class="form-check-input" checked="checked" id="validationFormCheck2" {{$data->type == 'Active' ? 'checked' :'' }} name="status">
                                        <label class="form-check-label" for="validationFormCheck2">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="inactive" class="form-check-input" id="validationFormCheck3" {{$data->type == 'InActive' ? 'checked' :'' }} name="status">
                                        <label class="form-check-label" for="validationFormCheck3">InActive</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div>
        <!-- end row -->
    </div> <!-- container-fluid -->
</div>
@endsection

@section("jscontent")
<script>

</script>
@endsection