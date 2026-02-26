<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class slc_client_requestModel extends Model
{
    use HasFactory;
    protected $table = 'slc_client_request';

    public function Slc_request()
    {
        return $this->hasMany(Slc_requestModel::class, 'request_id'); // assuming 'client_id' is the foreign key in the budget table
    }

}