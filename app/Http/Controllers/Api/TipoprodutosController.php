<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tipoproduto;
use Illuminate\Http\Request;

class TipoprodutosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return Tipoproduto::where('empresa_id', $empresa_id)->where('ativo', true)->orderBy('descricao')->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // $tipoproduto = new Tipoproduto;
        // $tipoproduto->descricao = $request->descricao;
        // $tipoproduto->empresa_id = $request->empresa_id;
        // $tipoproduto->status = $request->status;
        // $tipoproduto->save();
        Tipoproduto::create($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Tipoproduto  $tipoproduto
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Tipoproduto $tipoproduto)
    {
        $iten = $tipoproduto;

        if ($request->commands) {
            $request = json_decode($request->commands, true);
        }

        if ($request['adicionais']) {
            foreach ($request['adicionais'] as $key => $adicional) {
                if (is_string($adicional)) {
                    $iten[$adicional];
                } else {
                    $iten2 = $iten;
                    foreach ($adicional as $key => $a) {
                        if ($key == 0) {
                            if ($iten[0] == null) {
                                $iten2 = $iten[$a];
                            } else {
                                foreach ($iten as $key => $i) {
                                    $i[$a];
                                }
                            }
                        } else {
                            if ($iten2 != null) {
                                if ($iten2->count() > 0) {
                                    if ($iten2[0] == null) {
                                        $iten2 = $iten2[$a];
                                    } else {
                                        foreach ($iten2 as $key => $i) {
                                            $i[$a];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $iten;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tipoproduto  $tipoproduto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tipoproduto $tipoproduto)
    {
        $tipoproduto->update($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tipoproduto  $tipoproduto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tipoproduto $tipoproduto)
    {
        $tipoproduto->ativo = false;
        $tipoproduto->save();
    }
}
