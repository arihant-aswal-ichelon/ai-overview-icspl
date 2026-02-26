<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordRequest extends Model
{
    use HasFactory;
    protected $table = 'keyword_request';
    protected $fillable = [
        'id', 'domainmanagement_id', 'client_property_id', 'keyword', 'ai_overview', 'created_at', 'updated_at',
    ];
}
