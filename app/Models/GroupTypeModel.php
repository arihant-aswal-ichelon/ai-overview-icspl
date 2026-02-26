<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupTypeModel extends Model
{
    use HasFactory;

    protected $table = 'group_types';

    public function table1()
    {
        return $this->belongsTo(DomainManagementModel::class, 'domainmanagement_id');
    }

}
