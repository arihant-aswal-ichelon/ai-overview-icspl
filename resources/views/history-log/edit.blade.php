@extends('layouts.page-app')
@section("content")
<?php 
// dd($data1);

?>
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit {{$data->name}}</h4>

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
                            <form class="row g-3" method="post" action="{{url('edit-client')}}">
                                @csrf
                                
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Name*</label>
                                    <input type="text" name="name" class="form-control" id="name" value="{{$data->name}}" required="">
                                </div>
                                <input type="text" name="id" class="form-control" id="id" value="{{$data->id}}" hidden>
                                <input type="text" name="userid" class="form-control" id="userid" value="{{$data1->id}}" hidden>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Phone*</label>
                                    <input type="text" name="phone" class="form-control" id="phone" value="{{$data->phone}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefaultUsername" class="form-label">Email*</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="inputGroupPrepend2">@</span>
                                        <input type="text" name="email" class="form-control" id="email" value="{{$data->email}}" aria-describedby="inputGroupPrepend2" required="">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="validationDefault02" class="form-label">Slug <strong>(*Provide slug same as used in LMS for)</strong></label>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="customer_id" class="form-label">Customer ID*</label>
                                    <input type="text" name="customer_id" class="form-control" id="customer_id" value="{{$data->customer_id}}" required="">
                                </div>
                                <div class="col-md-6">
                                    <label for="manager_id" class="form-label">Manager ID*</label>
                                    <input type="text" name="manager_id" class="form-control" id="manager_id" value="{{$data->manager_id}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Scheduled*</label>
                                    <input type="text" name="scheduled" class="form-control" id="scheduled" value="{{$scheduled_slug}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Visited*</label>
                                    <input type="text" name="visited" class="form-control" id="visited" value="{{$visited_slug}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Appointment Missed*</label>
                                    <input type="text" name="missed" class="form-control" id="missed" value="{{$missed_slug}}" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Interested*</label>
                                    <input type="text" name="interested" class="form-control" id="interested" value="{{$interested_slug}}" required="">
                                </div>
                  
                                <div class="col-md-4">
                                    <label for="validationDefault04" class="form-label">Industry*</label>
                                    <select class="form-select" name="industry" id="industry" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option {{$data->industry == 'ivf' ? 'selected' :'' }} value="ivf">IVF</option>
                                        <option {{$data->industry == 'hair' ? 'selected' :'' }} value="hair">Hair Transplant</option>
                                        <option {{$data->industry == 'dental' ? 'selected' :'' }} value="dental">Dental</option>
                                        <option {{$data->industry == 'dermatologist' ? 'selected' :'' }} value="dermatologist">Dermatologist</option>
                                        <option {{$data->industry == 'other' ? 'selected' :'' }} value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">City*</label>
                                    <select class="form-select" name="city" id="city" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option {{$data->city == 'delhi' ? 'selected' :'' }} value="delhi">Delhi</option>
                                        <option {{$data->city == 'gurugram' ? 'selected' :'' }} value="gurugram">Gurugram</option>
                                        <option {{$data->city == 'noida' ? 'selected' :'' }} value="noida">Noida</option>
                                        <option {{$data->city == 'mumbai' ? 'selected' :'' }} value="mumbai">Mumbai</option>
                                        <option {{$data->city == 'bangalore' ? 'selected' :'' }} value="bangalore">Bangalore</option>
                                        <option {{$data->city == 'other' ? 'selected' :'' }} value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Zip*</label>
                                    <input type="text" name="zip" class="form-control" id="zip" required="" value="{{$data->zip}}">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Password*</label>
                                    <input type="password" id="password" name="password" class="form-control" value="" autocomplete="new-password">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Status</label>
                                    <div class="form-check">
                                        <input type="radio" value="active" class="form-check-input" id="validationFormCheck2" {{$data->status == 'active' ? 'checked' :'' }} name="status">
                                        <label class="form-check-label" for="validationFormCheck2">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="inactive" class="form-check-input" id="validationFormCheck3" {{$data->status == 'inactive' ? 'checked' :'' }} name="status">
                                        <label class="form-check-label" for="validationFormCheck3">InActive</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Type</label>
                                    <div class="form-check">
                                        <input type="radio" value="Admin" class="form-check-input" checked="checked" id="validationFormCheck4" {{$data1->type == 'Admin' ? 'checked' :'' }} name="type">
                                        <label class="form-check-label" for="validationFormCheck4">Admin</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="SA" class="form-check-input" id="validationFormCheck5" {{$data1->type == 'SA' ? 'checked' :'' }} name="type">
                                        <label class="form-check-label" for="validationFormCheck5">Super Admin</label>
                                    </div>
                                </div>
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

@section("jscontent")
<script>
function addNameUpdateListener(id) {
    document.addEventListener("DOMContentLoaded", function() {
        var nameInput = document.getElementById(id);

        nameInput.addEventListener("keyup", function() {
            nameInput.setAttribute("name", id + "_update");
        });
    });
}

addNameUpdateListener("name");
addNameUpdateListener("phone");
addNameUpdateListener("email");
addNameUpdateListener("scheduled");
addNameUpdateListener("visited");
addNameUpdateListener("missed");
addNameUpdateListener("interested");
addNameUpdateListener("zip");
addNameUpdateListener("password");

function addNameUpdateListenerOnClick(id) {
    document.addEventListener("DOMContentLoaded", function() {
        var nameInput = document.getElementById(id);

        nameInput.addEventListener("click", function() {
            nameInput.setAttribute("name", id + "_update");
        });
    });
}

addNameUpdateListenerOnClick("industry");
addNameUpdateListenerOnClick("city");

document.addEventListener("DOMContentLoaded", function() {
    var nameInput = document.getElementById("validationFormCheck2");

    nameInput.addEventListener("click", function() {
        nameInput.setAttribute("name", "status_update");
    });
});
document.addEventListener("DOMContentLoaded", function() {
    var nameInput = document.getElementById("validationFormCheck3");

    nameInput.addEventListener("click", function() {
        nameInput.setAttribute("name", "status_update");
    });
});

document.addEventListener("DOMContentLoaded", function() {
    var nameInput = document.getElementById("validationFormCheck4");

    nameInput.addEventListener("click", function() {
        nameInput.setAttribute("name", "type_update");
    });
});
document.addEventListener("DOMContentLoaded", function() {
    var nameInput = document.getElementById("validationFormCheck5");

    nameInput.addEventListener("click", function() {
        nameInput.setAttribute("name", "type_update");
    });
});
</script>
@endsection