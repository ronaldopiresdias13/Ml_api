<?php

namespace App\Http\Controllers\Api;

use App\Endereco;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EnderecosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Endereco::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $endereco = new Endereco;
        $endereco->cep         = $request->cep;
        $endereco->cidade      = $request->cidade;
        $endereco->rua         = $request->rua;
        $endereco->bairro      = $request->bairro;
        $endereco->numero      = $request->numero;
        $endereco->complemento = $request->complemento;
        $endereco->tipo        = $request->tipo;
        $endereco->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Endereco  $endereco
     * @return \Illuminate\Http\Response
     */
    public function show(Endereco $endereco)
    {
        return $endereco;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Endereco  $endereco
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Endereco $endereco)
    {
        $endereco->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Endereco  $endereco
     * @return \Illuminate\Http\Response
     */
    public function destroy(Endereco $endereco)
    {
        $endereco->delete();
    }
}