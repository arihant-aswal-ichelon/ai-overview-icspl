@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit {{$client[0]->name}} Property</h4>

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
                            <form class="row g-3" method="post" action="{{url('edit-client-properties')}}">
                                @csrf
                                <div class="col-md-4">
                                    <label for="validationDefault04" class="form-label">Type*</label>
                                    <select class="form-select" name="type" id="validationDefault04" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option {{$client_data[0]->type == 'lms' ? 'selected' :'' }} value="lms">LMS</option>
                                        <option {{$client_data[0]->type == 'website' ? 'selected' :'' }} value="website">Website</option>
                                        <option {{$client_data[0]->type == 'landing' ? 'selected' :'' }} value="landing">landing Page</option>
                                        
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Domain</label>
                                    <input type="text" name="domain" class="form-control" id="validationDefault02" value="{{$client_data[0]->domain}}">
                                </div>
                                <div class="col-md-4">
                                    <label for="keyword_mentioned_array" class="form-label">
                                        Keyword Mentioned
                                        <i class="ri-question-fill align-middle" title="Enter keywords to check if the keyword appears in AI Overview results. Use commas to add multiple keywords." style="cursor: help;"></i>
                                    </label>
                                    <input type="text" name="keyword_mentioned_array" class="form-control" id="keyword_mentioned_array" value="{{$client_data[0]->keyword_mentioned_array}}">
                                </div>
                                <input type="text" name="id" class="form-control" id="validationDefault02" value="{{$client_data[0]->id}}" hidden>
                                
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">Update</button>
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