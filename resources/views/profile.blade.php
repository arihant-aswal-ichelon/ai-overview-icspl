@extends('layouts.page-app')
@section("content")
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Profile</h4>

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{url('/')}}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Profile</li>
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
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1"></h4>
                            <form action="{{ route('profileEdit') }}" method="get">
                                @csrf
                                <button type="submit" class="btn btn-primary mt-1">Edit</button>
                            </form>
                        </div>
                        <div class="live-preview row g-3">
                            <div class="col-md-4">
                                <label for="validationDefault01" class="form-label">Name*</label>
                                <input type="text" name="name" class="form-control" id="name" value="{{$user->name}}" readonly>
                            </div>
                            <div class="col-md-4">
                                <label for="validationDefaultUsername" class="form-label">Email*</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="inputGroupPrepend2">@</span>
                                    <input type="text" name="email" class="form-control" id="email" value="{{$user->email}}" aria-describedby="inputGroupPrepend2" readonly>
                                </div>
                            </div>
                            @if($client != null)
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Phone*</label>
                                    <input type="text" name="phone" class="form-control" id="phone" value="{{$client->phone}}" readonly>
                                </div>
                            @endif
                            <!-- <div class="col-md-4">
                                <label for="validationDefault02" class="form-label">Password*</label>
                                <input type="password" id="password" name="password" class="form-control" value="{{$user->password}}" readonly>
                            </div> -->
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