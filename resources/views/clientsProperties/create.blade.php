@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Add Property</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('clients')}}">Clients</a></li>
                            <li class="breadcrumb-item active">Add Property</li>
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
                            <form class="row g-3" method="post" action="{{url('add-client-properties')}}">
                                @csrf
                                <div class="col-md-4">
                                    <label for="validationDefault04" class="form-label">Type*</label>
                                    <select class="form-select" name="type" id="validationDefault04" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option value="lms">LMS</option>
                                        <option value="website">Website</option>
                                        <option value="landing">landing Page</option>
                                        
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Domain</label>
                                    <input type="text" name="domain" class="form-control" id="validationDefault02" value="">
                                </div>
                                <input type="text" name="domainmanagement_id" class="form-control" id="validationDefault02" value="{{$id}}" hidden>
                                
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