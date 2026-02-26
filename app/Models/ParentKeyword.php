<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentKeyword extends Model
{
    use HasFactory;
    protected $table = 'parent_keyword';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'cluster_request_id',
        'parent_keyword',
        'clicks',
        'ctr',
        'impressions',
        'position',
        'created_at',
        'updated_at',
    ];
}
