<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiOverview extends Model
{
    use HasFactory;
    protected $table = 'ai_overview';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'lms_domain',
        'keyword_request_id',
        'cluster_request_id',
        'keyword_planner_id',
        'text_blocks',
        'json',
        'markdown',
        'priority_sync',
        'created_at',
        'updated_at',

    ];
}
