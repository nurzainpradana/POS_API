<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model {
    protected $table    = 'transaction';

    public function detail(){
        return $this->hasMany('App\Models\TransactionDetail', 'id_transaction');
    }
}