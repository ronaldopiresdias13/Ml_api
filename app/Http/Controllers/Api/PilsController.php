<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Pil;
use Illuminate\Http\Request;

class PilsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Pil::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $pil = new Pil;
        $pil->paciente = $request->paciente;
        $pil->profissional = $request->profissional;
        $pil->diagnosticoprincipal = $request->diagnosticoprincipal;
        $pil->data = $request->data;
        $pil->prognostico = $request->prognostico;
        $pil->avaliacao = $request->avaliacao;
        $pil->revisao = $request->revisao;
        $pil->evolucao = $request->evolucao;
        $pil->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Pil  $pil
     * @return \Illuminate\Http\Response
     */
    public function show(Pil $pil)
    {
        return $pil;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Pil  $pil
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pil $pil)
    {
        $pil->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pil  $pil
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pil $pil)
    {
        $pil->delete();
    }
}
