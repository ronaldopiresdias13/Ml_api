<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServicoFormacao extends Model
{
    protected $table = 'servico_formacao';
    protected $guarded = [];

    public function servico()
    {
        return $this->belongsTo('App\Servico');
    }
}