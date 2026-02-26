<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClusterRequest extends Model
{
    use HasFactory;
    protected $table = 'cluster_request';
    protected $fillable = [
        'id', 'domainmanagement_id', 'client_property_id', 'keyword', 'date_from', 'date_to', 'created_at', 'updated_at',
    ];
}
