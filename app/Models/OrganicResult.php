<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganicResult extends Model
{
    use HasFactory;
    protected $table = 'organic_results';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'keyword_planner_id',
        'cluster_request_id',
        'position',
        'title',
        'link',
        'source',
        'domain',
        'displayed_link',
        'snippet',
        'snippet_highlighted_word',
        'sitelinks',
        'favicon',
        'date',
        'json',
        'created_at',
        'updated_at',
    ];

}
