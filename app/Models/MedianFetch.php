<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedianFetch extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table='median-fetch';
    protected $fillable = [
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'median_name',
        'date_from',
        'date_to',
        'keyword_p',
        'monthlysearch_p',
        'bucket',
        'competition_p',
        'low_bid_p',
        'high_bid_p',
        'clicks_p',
        'ctr_p',
        'impressions_p',
        'position_p',
    ];
}
