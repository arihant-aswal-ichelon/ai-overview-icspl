<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investment_clientModel extends Model
{
    use HasFactory;
    protected $table = 'investment_client';

    public function budget()
    {
        return $this->hasMany(BudgetModel::class, 'investment_client_id'); // assuming 'client_id' is the foreign key in the budget table
    }

}