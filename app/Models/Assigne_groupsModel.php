<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assigne_groupsModel extends Model
{
    use HasFactory;

    protected $table = 'assigne_groups';

    public function table1()
    {
        return $this->belongsTo(GroupModel::class, 'group_id');
    }
}
