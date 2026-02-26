<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordPlanner extends Model
{
    use HasFactory;
    protected $table = 'keyword_planner';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'lms_domain',
        'keyword_request_id',
        'cluster_request_id',
        'keyword_p',
        'monthlysearch_p',
        'competition_p',
        'low_bid_p',
        'high_bid_p',
        'monthlysearchvolume_p',
        'clicks_p',
        'ctr_p',
        'impressions_p',
        'position_p',
        'ai_status',
        'created_at',
        'updated_at',
    ];

    // KeywordPlanner.php
    public function aiOverview()
    {
        return $this->hasOne(AiOverview::class, 'keyword_planner_id');
    }

}
