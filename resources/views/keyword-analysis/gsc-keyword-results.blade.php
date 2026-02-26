<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<div class="card">

    <div class="card-body">
        <table class="table" id="keywordsTable">
            <thead>
                <tr>
                    <th scope="col">Keyword</th>
                    <th scope="col">Monthly Search</th>
                    <th scope="col">Competition</th>
                    <th scope="col">Low Bid (₹)</th>
                    <th scope="col">High Bid (₹)</th>
                    <th>Clicks</th>
                    <th>CTR (%)</th>
                    <th>Impressions</th>
                    <th>Position</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($keywords)
                @foreach($keywords as $keyword_item)
                <tr>
                    <th scope="row">{{$keyword_item['keyword'] ?? $keyword_item['query']}}</th>
                    <td>{{$keyword_item['avg_monthly_searches'] ?? 0 }}</td>
                    <td>{{$keyword_item['competition'] ?? 0 }}</td>
                    <td>{{$keyword_item['low_top_of_page_bid'] ?? 0 }}</td>
                    <td>{{$keyword_item['high_top_of_page_bid'] ?? 0 }}</td>
                    <td class="text-end">{{ $keyword_item['clicks'] ?? 0 }}</td>
                    <td class="text-end">
                        <span class="{{ $keyword_item['ctr'] > 5 ? 'text-success' : ($keyword_item['ctr'] > 2 ? 'text-warning' : 'text-danger') }}">
                            {{ $keyword_item['ctr'] }}%
                        </span>
                    </td>
                    <td class="text-end">{{ $keyword_item['impressions'] }}</td>
                    <td class="text-end">
                        <span class="{{ $keyword_item['position'] <= 3 ? 'text-success' : ($keyword_item['position'] <= 10 ? 'text-warning' : 'text-danger') }}">
                            {{ $keyword_item['position'] }}
                        </span>
                    </td>
                    <td>

                        @php
                        $keywordExists = \App\Models\KeywordPlanner::where([
                        ['keyword_p', $keyword_item['keyword'] ?? $keyword_item['query']],
                        ['client_property_id', $client_property_id ?? session('client_property_id')],
                        ['domainmanagement_id', $domainmanagement_id ?? session('domainmanagement_id')],
                        ['keyword_request_id', $keyword_request_id ?? session('keyword_request_id')]
                        ])->first();
                        $aioExists = null;

                        if ($keywordExists) {
                        $aioExists = \App\Models\AiOverview::where([
                        ['keyword_planner_id', $keywordExists->id],
                        ['client_property_id', $client_property_id ?? session('client_property_id')],
                        ['domainmanagement_id', $domainmanagement_id ?? session('domainmanagement_id')],
                        ['keyword_request_id', $keyword_request_id ?? session('keyword_request_id')]
                        ])->first();
                        }

                        @endphp

                        @if($keywordExists)
                        <a href="{{ url('extracted-aio-result/'.$keywordExists->id) }}" class="btn {{ $aioExists ? 'btn-primary' : 'btn-warning' }}">{{ $aioExists ? 'AIO Insights' : 'Search Insights' }}</a>
                        @else
                        <button class="btn btn-success get-aio-btn"
                            onclick="fetchAioResult(this,`{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}`)"
                            data-keyword="{{ $keyword_item['keyword'] ?? $keyword_item['query'] }}"
                            id="aio-btn-{{ md5($keyword_item['keyword'] ?? $keyword_item['query']) }}">
                            Get Search Result
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
                @endif

            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function() {
        console.log("asdassssssssss");
        // Calculate the CTR column index
        // If $domain_name is not set, CTR is at index 6 (0-based)
        // If $domain_name is set, CTR is at index 2 (0-based)
        var ctrColumnIndex = {{!$domain_name ? 6 : 2}};

        $('#keywordsTable').DataTable({
            "order": [
                [ctrColumnIndex, "desc"]
            ], // Sort by CTR ascending (lowest to highest)
            "pageLength": 25,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"]
            ],
            "language": {
                "search": "Filter keywords:",
                "lengthMenu": "Show _MENU_ keywords",
                "info": "Showing _START_ to _END_ of _TOTAL_ keywords",
                "infoEmpty": "No keywords available",
                "infoFiltered": "(filtered from _MAX_ total keywords)",
                "zeroRecords": "No matching keywords found"
            },
            "columnDefs": [{
                    "targets": "text-end",
                    "className": "text-end"
                },
                {
                    "targets": [ctrColumnIndex], // CTR column
                    "type": "num", // Treat as numeric for proper sorting
                    "render": function(data, type, row) {
                        // For display, return the HTML with span
                        if (type === 'display') {
                            return data;
                        }
                        // For sorting, extract the numeric value from the span content
                        if (type === 'sort') {
                            // Extract the numeric value from the content (remove % sign)
                            var ctrValue = $(data).text().replace('%', '');
                            return parseFloat(ctrValue) || 0;
                        }
                        return data;
                    }
                }
            ],
            "initComplete": function() {
                // Add custom class to DataTables wrapper
                $(this).closest('.dataTables_wrapper').addClass('table-responsive');
            }
        });
    });
</script>