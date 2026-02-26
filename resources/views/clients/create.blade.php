@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Add Client</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{url('clients')}}">Clients</a></li>
                            <li class="breadcrumb-item active">Add Client</li>
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
                            <form class="row g-3" method="post" action="{{url('add-client')}}">
                                @csrf
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Name*</label>
                                    <input type="text" name="name" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Phone*</label>
                                    <input type="text" name="phone" class="form-control" id="validationDefault02" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefaultUsername" class="form-label">Email*</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="inputGroupPrepend2">@</span>
                                        <input type="text" name="email" class="form-control" id="validationDefaultUsername" aria-describedby="inputGroupPrepend2" required="">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="validationDefault02" class="form-label">Slug <strong>(*Provide slug same as used in LMS for)</strong></label>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Scheduled*</label>
                                    <input type="text" name="scheduled" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Visited*</label>
                                    <input type="text" name="visited" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Missed*</label>
                                    <input type="text" name="missed" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Interested*</label>
                                    <input type="text" name="interested" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                  
                                <div class="col-md-4">
                                    <label for="validationDefault04" class="form-label">Industry*</label>
                                    <select class="form-select" name="industry" id="validationDefault04" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option value="ivf">IVF</option>
                                        <option value="hair">Hair Transplant</option>
                                        <option value="dental">Dental</option>
                                        <option value="dermatologist">Dermatologist</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">City*</label>
                                    <select class="form-select" name="city" id="validationDefault05" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option value="delhi">Delhi</option>
                                        <option value="gurugram">Gurugram</option>
                                        <option value="noida">Noida</option>
                                        <option value="mumbai">Mumbai</option>
                                        <option value="bangalore">Bangalore</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Zip*</label>
                                    <input type="text" name="zip" class="form-control" id="validationDefault05" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Password*</label>
                                    <input type="password" id="password" name="password" class="form-control" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Status</label>
                                    <div class="form-check">
                                        <input type="radio" value="active" class="form-check-input" checked="checked" id="validationFormCheck2" name="status">
                                        <label class="form-check-label" for="validationFormCheck2">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="inactive" class="form-check-input" id="validationFormCheck3" name="status">
                                        <label class="form-check-label" for="validationFormCheck3">InActive</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Type</label>
                                    <div class="form-check">
                                        <input type="radio" value="Admin" class="form-check-input" checked="checked" id="validationFormCheck2" name="type">
                                        <label class="form-check-label" for="validationFormCheck2">Admin</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="SA" class="form-check-input" id="validationFormCheck3" name="type">
                                        <label class="form-check-label" for="validationFormCheck3">Super Admin</label>
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