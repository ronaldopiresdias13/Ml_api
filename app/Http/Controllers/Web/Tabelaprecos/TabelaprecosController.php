<?php

namespace App\Http\Controllers\Web\Tabelaprecos;

use App\Http\Controllers\Controller;
use App\Models\Tabelapreco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TabelaprecosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa_id;
        return Tabelapreco::where('empresa_id', $empresa_id)->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;
        $tabelapreco = new Tabelapreco();
        $tabelapreco->empresa_id = $empresa_id;
        $tabelapreco->nome       = $request->nome;
        $tabelapreco->padrao     = $request->padrao;
        $tabelapreco->save();
        return $tabelapreco;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tabelapreco  $tabelapreco
     * @return \Illuminate\Http\Response
     */
    public function show(Tabelapreco $tabelapreco)
    {
        return $tabelapreco;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tabelapreco  $tabelapreco
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tabelapreco $tabelapreco)
    {
        $tabelapreco->nome   = $request->nome;
        $tabelapreco->padrao = $request->padrao;
        $tabelapreco->save();
        return $tabelapreco;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tabelapreco  $tabelapreco
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tabelapreco $tabelapreco)
    {
        $tabelapreco->delete;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importacaoBrasindice(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importacaoSimpro(Request $request)
    {
        //
    }
}
