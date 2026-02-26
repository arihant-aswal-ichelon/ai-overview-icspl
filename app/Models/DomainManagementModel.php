<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainManagementModel extends Model
{
    use HasFactory;
    protected $table = 'domainmanagement';

    public function client_properties()
    {
        return $this->hasMany(Client_propertiesModel::class, 'domainmanagement_id'); // assuming 'client_id' is the foreign key in the budget table
    }

    public function investment_client()
    {
        return $this->hasMany(Investment_clientModel::class, 'domainmanagement_id'); // assuming 'client_id' is the foreign key in the budget table
    }
}