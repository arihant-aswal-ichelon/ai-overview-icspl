<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client_landing_page_urlModel extends Model
{
    use HasFactory;
    protected $table = 'client_landing_page_url';
    protected $fillable = [
        'id',
        'domainmanagement_id',
        'client_properties_id',
        'lms_url',
        'url',
        'impression',
        'position',
        'click',
        'ctr',
        'created_at',
        'updated_at',
    ];
}
