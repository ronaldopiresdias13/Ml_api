<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pagamentopessoa;
use Illuminate\Http\Request;

class PagamentopessoasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $with = [];

        if ($request['adicionais']) {
            foreach ($request['adicionais'] as $key => $adicional) {
                if (is_string($adicional)) {
                    array_push($with, $adicional);
                } else {
                    $filho = '';
                    foreach ($adicional as $key => $a) {
                        if ($key == 0) {
                            $filho = $a;
                        } else {
                            $filho = $filho . '.' . $a;
                        }
                    }
                    array_push($with, $filho);
                }
            }
            $itens = Pagamentopessoa::with($with)->where('ativo', true);
        } else {
            $itens = Pagamentopessoa::where('ativo', true);
        }
        if ($request->commands) {
            $request = json_decode($request->commands, true);
        }
        if ($request['where']) {
            foreach ($request['where'] as $key => $where) {
                $itens->where(
                    ($where['coluna']) ? $where['coluna'] : 'id',
                    ($where['expressao']) ? $where['expressao'] : 'like',
                    ($where['valor']) ? $where['valor'] : '%'
                );
            }
        }
        if ($request['order']) {
            foreach ($request['order'] as $key => $order) {
                $itens->orderBy(
                    ($order['coluna']) ? $order['coluna'] : 'id',
                    ($order['tipo']) ? $order['tipo'] : 'asc'
                );
            }
        }
        $itens = $itens->get();
        if ($request['adicionais']) {
            foreach ($itens as $key => $iten) {
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
        }

        return $itens;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Pagamentopessoa::create($request->all());
        $pagamentopessoa = new Pagamentopessoa();
        $pagamentopessoa->pessoa_id = $request->pessoa_id;
        $pagamentopessoa->empresa_id = $request->empresa_id;
        $pagamentopessoa->ordemservico_id = $request->ordemservico_id;
        $pagamentopessoa->periodo1 = $request->periodo1;
        $pagamentopessoa->periodo2 = $request->periodo2;
        $pagamentopessoa->valor = $request->valor;
        $pagamentopessoa->observacao = $request->observacao;
        $pagamentopessoa->status     = $request->status;
        $pagamentopessoa->situacao   = $request->situacao;
        $pagamentopessoa->save();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Pagamentopessoa  $pagamentopessoa
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Pagamentopessoa $pagamentopessoa)
    {
        $iten = $pagamentopessoa;
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
     * @param  \App\Pagamentopessoa  $pagamentopessoa
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pagamentopessoa $pagamentopessoa)
    {
        $pagamentopessoa->empresa_id      = $request['empresa_id'];
        $pagamentopessoa->pessoa_id       = $request['pessoa_id'];
        $pagamentopessoa->ordemservico_id = $request['ordemservico_id'];
        $pagamentopessoa->periodo1        = $request['periodo1'];
        $pagamentopessoa->periodo2        = $request['periodo2'];
        $pagamentopessoa->valor           = $request['valor'];
        $pagamentopessoa->observacao      = $request['observacao'];
        $pagamentopessoa->status          = $request['status'];
        $pagamentopessoa->situacao        = $request['situacao'];
        $pagamentopessoa->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pagamentopessoa  $pagamentopessoa
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pagamentopessoa $pagamentopessoa)
    {
        $pagamentopessoa->ativo = false;
        $pagamentopessoa->save();
    }
}
