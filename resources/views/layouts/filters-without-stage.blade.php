<div class="card-header align-items-center d-flex">
    <h4 class="card-title mb-0 flex-grow-1">Filters</h4>
</div>
<form method="post" action="">
    @csrf
    <div class="row align-items-center g-3">
        <div class="col-lg-4">
            <label for="choices-multiple-remove-button" class="form-label text-muted">Select Sources</label>
            <p class="text-muted">Set <code>multiple</code> source to filter.</p>
            <select class="form-control" id="choices-multiple-remove-button" data-choices data-choices-removeItem name="lms_sources[]" multiple>
                <?php foreach($lead_sources as $source){// var_dump($source);die;?>
                    <option value="<?php echo $source['lead_source_id']; ?>" <?php if(isset($filters['filter_source']) && !empty($filters['filter_source']) && in_array($source['lead_source_id'],$filters['filter_source'])){echo "selected"; }?>><?php echo $source['lead_source_name']; ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="col-lg-4">
            <label for="choices-multiple-remove-button" class="form-label text-muted">Select Date Range</label>
            <p class="text-muted">Set <code>created date</code> filter.</p>
            <input type="text" class="form-control" value="<?php echo $lms_daterange; ?>" data-provider="flatpickr" data-date-format="d M, Y" id="lms_daterange" name="lms_daterange" value="">
        </div> 
        <div class="col-lg-4">
            <label for="choices-multiple-remove-button" class="form-label text-muted">Select Date Range</label>
            <p class="text-muted">Set <code>updated date</code> filter.</p>
            <input type="text" class="form-control" value="<?php echo $lms_updateddaterange; ?>" data-provider="flatpickr" data-date-format="d M, Y" id="lms_updateddaterange" name="lms_updateddaterange" value="">
        </div> 
        <div class="col-lg-4 mt-12">
            <input type="submit" class="btn btn-primary" value="Submit" />
        </div>
    </div>
</form>
<form action="{{ url()->current() }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-primary mt-1">Reset</button>
</form>
<hr class="mb-0">
