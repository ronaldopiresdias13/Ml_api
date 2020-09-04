<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;

class CuidadoPaciente extends Model
{
    use Uuid;

    protected $keyType = 'string';

    protected $table = 'cuidado_paciente';
    protected $guarded = [];
}
