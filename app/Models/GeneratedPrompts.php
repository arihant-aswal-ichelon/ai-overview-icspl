<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedPrompts extends Model
{
    use HasFactory;
    protected $table = 'generated_prompts';
    protected $fillable = [
        'id', 'domainmanagement_id', 'client_property_id', 'keyword_request_id', 'keyword_ids', 'prompt', 'created_at', 'updated_at',
    ];
}
