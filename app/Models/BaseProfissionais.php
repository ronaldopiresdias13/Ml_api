<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseProfissionais extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'baseprofissionais';

    protected $guarded = [];
}
