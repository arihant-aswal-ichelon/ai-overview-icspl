<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedPromptsResponse extends Model
{
    use HasFactory;
    protected $table = 'generated_prompts_response';
    protected $fillable = [
        'id', 'domainmanagement_id', 'client_property_id', 'keyword_request_id', 'generated_prompt_id', 'source', 'prompt_json', 'created_at', 'updated_at',
    ];
}
