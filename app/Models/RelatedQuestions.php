<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelatedQuestions extends Model
{
    use HasFactory;
    protected $table = 'related_questions';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'keyword_planner_id',
        'cluster_request_id',
        'history_log_id',
        'question',
        'answer',
        'source_title',
        'source_link',
        'source_source',
        'source_domain',
        'source_displayed_link',
        'source_favicon',
        'json',
        'date',
        'created_at',
        'updated_at',
        
    ];
}
