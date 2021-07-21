<?php

namespace App\Http\Controllers\Web\Empresas;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmpresasController extends Controller
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
            Empresa::create([
                'razao'                    => $request['razao'],
                'cnpj'                     => $request['cnpj'],
                'ie'                       => $request['ie'],
                'registroANS'              => $request['registroANS'],
                'logo'                     => $request['logo'],
                'tiss_sequencialTransacao' => $request['tiss_sequencialTransacao'],
                'CNES'                     => $request['CNES'],
                'quantidadepaciente'       => $request['quantidadepaciente'],
                'quantidadead'             => $request['quantidadead'],
                'valorad'                  => $request['valorad'],
                'quantidadeid'             => $request['quantidadeid'],
                'valorid'                  => $request['valorid'],
                'telefone'                 => $request['telefone'],
                'celular'                  => $request['celular'],
                'endereco'                 => $request['endereco'],
                'site'                     => $request['site'],
                'email'                    => $request['email'],
            ]);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Empresa $empresa)
    {
        // $user = $request->user();
        // $empresa_id = $user->pessoa->profissional->empresa_id;

        // $result = Empresa::find($empresa_id);
        return $empresa;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Empresa $empresa)
    {
        $empresa =  DB::transaction(function () use ($request, $empresa) {
            $empresa->update([
                'razao'                    => $request['razao'],
                'cnpj'                     => $request['cnpj'],
                'ie'                       => $request['ie'],
                'registroANS'              => $request['registroANS'],
                'logo'                     => $request['logo'],
                'tiss_sequencialTransacao' => $request['tiss_sequencialTransacao'],
                'CNES'                     => $request['CNES'],
                'quantidadepaciente'       => $request['quantidadepaciente'],
                'quantidadead'             => $request['quantidadead'],
                'valorad'                  => $request['valorad'],
                'quantidadeid'             => $request['quantidadeid'],
                'valorid'                  => $request['valorid'],
                'telefone'                 => $request['telefone'],
                'celular'                  => $request['celular'],
                'endereco'                 => $request['endereco'],
                'site'                     => $request['site'],
                'email'                    => $request['email'],
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Empresa $empresa)
    {
        $empresa->ativo = false;
        $empresa->save();
    }
}
