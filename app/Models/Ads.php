<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    use HasFactory;
    protected $table = 'ads';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_property_id',
        'keyword_request_id',
        'position',
        'block_position',
        'title',
        'link',
        'source',
        'domain',
        'displayed_link',
        'tracking_link',
        'snippet',
        'snippet_highlighted_word',
        'sitelinks',
        'favicon',
        'advertiser_info_token',
        'date',
        'json',
        'created_at',
        'updated_at',
    ];
}
