<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use HasFactory;
    protected $table = 'history_log';
    protected $fillable = [
        'id', 'domainmanagement_id', 'client_property_id', 'keyword_request_id', 'keyword_planner_id', 'aio_status', 'search_status', 'created_at', 'updated_at',
    ];
}
