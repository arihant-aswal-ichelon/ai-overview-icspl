<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupModel extends Model
{
    use HasFactory;

    protected $table = 'groups';

    public function table1()
    {
        return $this->belongsTo(GroupTypeModel::class, 'group_type_id');
    }

    public function table2()
    {
        return $this->belongsTo(Client_propertiesModel::class, 'client_properties_id');
    }

    public function table3()
    {
        return $this->belongsTo(DomainManagementModel::class, 'client_id');
    }
}
