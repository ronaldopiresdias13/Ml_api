<?php

namespace App;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class SaidaProduto extends Model
{
    // use Uuid;

    // protected $keyType = 'string';
    protected $table = 'saida_produto';
    protected $guarded = [];
}
