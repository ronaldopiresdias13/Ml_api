<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Monitoramentoescala extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function escala()
    {
        return $this->belongsTo(Escala::class);
    }
}
