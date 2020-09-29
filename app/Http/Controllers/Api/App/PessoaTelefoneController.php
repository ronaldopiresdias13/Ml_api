<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\PessoaTelefone;
use App\Telefone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PessoaTelefoneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            PessoaTelefone::updateOrCreate(
                [
                    'pessoa_id' => $request->pessoa_id,
                    'telefone_id'  => Telefone::firstOrCreate(
                        ['telefone' => $request['telefone']['telefone']]
                    )->id,
                ],
                [
                    'tipo'      => $request['tipo'],
                    'descricao' => $request['descricao'],
                    'ativo'     => true
                ]
            );
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\PessoaTelefone  $pessoaTelefone
     * @return \Illuminate\Http\Response
     */
    public function show(PessoaTelefone $pessoaTelefone)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\PessoaTelefone  $pessoaTelefone
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PessoaTelefone $pessoaTelefone)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\PessoaTelefone  $pessoaTelefone
     * @return \Illuminate\Http\Response
     */
    public function destroy(PessoaTelefone $pessoaTelefone)
    {
        $pessoaTelefone->ativo = false;
        $pessoaTelefone->save();
    }
}