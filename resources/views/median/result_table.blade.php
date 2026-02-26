
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Added to Bucket</h5>
        </div>
        <div class="card-body">
            @if($addedBucket->isEmpty())
            <p class="text-warning">No keywords added to bucket.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Keyword</th>
                            <th>Monthly Search</th>
                            <th>Competition</th>
                            <th>Low Bid (₹)</th>
                            <th>High Bid (₹)</th>
                            <th>Clicks</th>
                            <th>CTR (%)</th>
                            <th>Impressions</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($addedBucket as $row)
                        <tr>
                            <td>{{ $row->keyword_term }}</td>
                            <td>{{ $row->monthlysearch_p }}</td>
                            <td>{{ $row->competition_p }}</td>
                            <td>{{ $row->low_bid_p }}</td>
                            <td>{{ $row->high_bid_p }}</td>
                            <td>{{ $row->clicks_p }}</td>
                            <td>{{ $row->ctr_p }}</td>
                            <td>₹{{ $row->impressions_p }}</td>
                            <td>₹{{ $row->position_p }}</td>
                            <td><a target="_blank" href="{{url('extracted-aio-result/'.$row->keyword_p)}}" class="btn btn-primary">Analysis</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Not Added to Bucket</h5>
        </div>
        <div class="card-body">
            @if($notAddedBucket->isEmpty())
            <p class="text-warning">All keywords are in bucket.</p>
            @else
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Keyword</th>
                            <th>Monthly Search</th>
                            <th>Competition</th>
                            <th>Low Bid (₹)</th>
                            <th>High Bid (₹)</th>
                            <th>Clicks</th>
                            <th>CTR (%)</th>
                            <th>Impressions</th>
                            <th>Position</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notAddedBucket as $row)
                        <tr>
                            <td>{{ $row->keyword_term }}</td>
                            <td>{{ $row->monthlysearch_p }}</td>
                            <td>{{ $row->competition_p }}</td>
                            <td>{{ $row->low_bid_p }}</td>
                            <td>{{ $row->high_bid_p }}</td>
                            <td>{{ $row->clicks_p }}</td>
                            <td>{{ $row->ctr_p }}</td>
                            <td>₹{{ $row->impressions_p }}</td>
                            <td>₹{{ $row->position_p }}</td>
                            <td><a target="_blank" href="{{url('extracted-aio-result/'.$row->keyword_p)}}" class="btn btn-primary">Analysis</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>