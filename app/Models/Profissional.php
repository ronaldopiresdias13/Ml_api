<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Profissional extends Model
{
    protected $table = 'profissionais';
    protected $guarded = [];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function setor()
    {
        return $this->belongsTo(Setor::class);
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function dadoscontratual()
    {
        return $this->belongsTo(Dadoscontratual::class, 'dadoscontratuais_id');
    }

    public function formacoes()
    {
        return $this->belongsToMany(Formacao::class, 'profissional_formacao')->withPivot('id')->wherePivot('ativo', true);
    }

    // public function beneficios()
    // {
    //     return $this->belongsToMany(Beneficio::class, 'profissional_beneficio')->wherePivot('ativo', true);
    // }
    public function beneficios()
    {
        return $this->hasMany(ProfissionalBeneficio::class)->where('ativo', true);
    }

    public function convenios()
    {
        return $this->belongsToMany(Convenio::class, 'profissional_convenio')->withPivot('id')->wherePivot('ativo', true);
    }
    public function anexos()
    {
        return $this->morphMany(Anexo::class, 'anexo');
    }
}
