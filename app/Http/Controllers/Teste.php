<?php

namespace App\Http\Controllers;

use App\Prestador;
use Illuminate\Http\Request;

class Teste extends Controller
{
    public function teste(Request $request)
    {
        $prestadores = Prestador::with([
            'pessoa:id,nome',
            'formacoes:formacoes.id,descricao',
            'pessoa.conselhos:conselhos.id,instituicao,numero,uf,pessoa_id',
            'pessoa.enderecos.cidade',
            'pessoa.telefones:telefones.id,telefone'
        ])
            ->get(['id', 'pessoa_id']);

        $result = [];

        foreach ($prestadores as $key => $prestador) {
            $inserir = true;

            if ($inserir && $request['nome']) {
                if (str_contains(strtolower($prestador->pessoa->nome), strtolower($request['nome']))) {
                    $inserir = true;
                } else {
                    $inserir = false;
                }
            }
            if ($inserir && $request['formacao']) {
                $contain = false;
                foreach ($prestador->formacoes as $key => $formacao) {
                    if (str_contains(strtolower($formacao->descricao), strtolower($request['formacao']))) {
                        $contain = true;
                    }
                }
                if ($contain) {
                    $inserir = true;
                } else {
                    $inserir = false;
                }
            }
            if ($inserir && $request['cidade']) {
                if ($prestador->pessoa->enderecos) {
                    $contain = false;
                    foreach ($prestador->pessoa->enderecos as $key => $endereco) {
                        if ($endereco->cidade) {
                            if (str_contains(strtolower($endereco->cidade->nome), strtolower($request['cidade']))) {
                                $contain = true;
                            }
                        }
                    }
                    if ($contain) {
                        $inserir = true;
                    } else {
                        $inserir = false;
                    }
                } else {
                    $inserir = false;
                }
            }

            if ($inserir && ($request['nome'] || $request['formacao'] || $request['cidade'])) {
                array_push($result, $prestador);
            }
        }

        return $result;
    }
}
