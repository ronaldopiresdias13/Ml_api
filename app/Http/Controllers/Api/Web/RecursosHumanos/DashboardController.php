<?php

namespace App\Http\Controllers\Api\Web\RecursosHumanos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function dashboardProfissionaisExternos(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return DB::select(
            "SELECT ep.status, COUNT(ep.id) AS total FROM empresa_prestador AS ep
                WHERE ep.empresa_id = ?
                 AND ep.status != 'Recusado'
                 GROUP BY ep.status
                 ORDER BY total desc",
            [
                $empresa_id,
            ]
        );
    }
    public function dashboardMapaPacientesPorEspecialidade(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return DB::select(
            "SELECT s.descricao, COUNT(oss.id) AS total FROM ordemservico_servico AS oss
                INNER JOIN servicos AS s
                ON s.id = oss.servico_id
                WHERE s.empresa_id = ?
                GROUP BY oss.servico_id, s.descricao  
                ORDER BY total desc",
            [
                $empresa_id,
            ]
        );
    }
}
