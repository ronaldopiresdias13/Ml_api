<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contasbancaria extends Model
{
    protected $guarded = [];

    public function banco()
    {
        return $this->belongsTo('App\Banco');
    }
}