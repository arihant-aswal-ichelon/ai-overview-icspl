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
                                <div class="col-md-4">
                                    <label for="frequency_value" class="form-label">Frequency*</label>
                                    <div class="input-group">
                                        <input type="number" id="frequency_value" class="form-control" min="1" placeholder="e.g. 2" required="">
                                        <select id="frequency_unit" class="form-select" style="max-width: 110px;">
                                            <option value="hours">Hours</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                    <small class="text-muted mt-1 d-block" id="frequency_preview"></small>
                                    {{-- Hidden field stores the DD:HH:MM formatted value --}}
                                    <input type="hidden" name="frequency" id="frequency" value="">
                                </div>
                                <div class="col-md-4">
                                    <label for="keyword_mentioned_array" class="form-label">
                                        Keyword Mentioned
                                        <i class="ri-question-fill align-middle" title="Enter keywords to check if the keyword appears in AI Overview results. Use commas to add multiple keywords." style="cursor: help;"></i>
                                    </label>
                                    <input type="text" name="keyword_mentioned_array" class="form-control" id="keyword_mentioned_array" >
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

@section('jscontent')
<script>

document.addEventListener("DOMContentLoaded", function () {
    var freqValueInput = document.getElementById("frequency_value");
    var freqUnitSelect = document.getElementById("frequency_unit");
    var freqHidden     = document.getElementById("frequency");
    var freqPreview    = document.getElementById("frequency_preview");

    // Parse existing stored value (DD:HH:MM) back into the UI
    function parseExistingFrequency(raw) {
        if (!raw) return;
        var parts = raw.split(":");
        if (parts.length !== 3) return;
        var dd = parseInt(parts[0], 10);
        var hh = parseInt(parts[1], 10);
        if (dd > 0) {
            freqValueInput.value  = dd;
            freqUnitSelect.value  = "days";
        } else if (hh > 0) {
            freqValueInput.value  = hh;
            freqUnitSelect.value  = "hours";
        }
        updatePreview();
    }

    // Build DD:HH:MM string and refresh the preview label
    function updateFrequency() {
        var val  = parseInt(freqValueInput.value, 10) || 0;
        var unit = freqUnitSelect.value;
        var formatted = unit === "days"
            ? pad(val) + ":00:00"
            : "00:" + pad(val) + ":00";
        freqHidden.value = formatted;
        updatePreview();
        // Mark as updated for the change-detection system
        freqHidden.setAttribute("name", "frequency_update");
    }

    function updatePreview() {
        var val  = parseInt(freqValueInput.value, 10) || 0;
        var unit = freqUnitSelect.value;
        if (val > 0) {
            freqPreview.textContent = "Stored as: " + freqHidden.value
                + "  (" + val + " " + (val === 1 ? unit.replace(/s$/, "") : unit) + ")";
        } else {
            freqPreview.textContent = "";
        }
    }

    function pad(n) { return String(n).padStart(2, "0"); }

    freqValueInput.addEventListener("input", updateFrequency);
    freqUnitSelect.addEventListener("change", updateFrequency);

    // Initialise from the existing Blade value
    parseExistingFrequency("");
});
</script>
@endsection