<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedidocompra;
use App\Models\PedidocompraProduto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidocomprasController extends Controller
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
            $itens = Pedidocompra::with($with)->where('ativo', true);
        } else {
            $itens = Pedidocompra::where('ativo', true);
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
        DB::transaction(function () use ($request) {
            $pedidocompra = Pedidocompra::create([
                'empresa_id'   => $request['empresa_id'],
                'pessoa_id'    => $request['pessoa_id'],
                'data'         => $request['data'],
                'numeropedido' => $request['numeropedido'],
                'observacao'   => $request['observacao'],
                'status'       => $request['status'],
            ]);
            if ($request['produtos']) {
                foreach ($request['produtos'] as $key => $produto) {
                    $requisicao_produto = PedidocompraProduto::create([
                        'pedidocompra_id' => $pedidocompra->id,
                        'produto_id'      => $produto['pivot']['produto_id'],
                        'quantidade'      => $produto['pivot']['quantidade'],
                        'observacao'      => $produto['pivot']['observacao'],
                        'status'          => $produto['pivot']['status'],
                    ]);
                }
            }
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Pedidocompra  $pedidocompra
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Pedidocompra $pedidocompra)
    {
        $iten = $pedidocompra;

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
     * @param  \App\Pedidocompra  $pedidocompra
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pedidocompra $pedidocompra)
    {
        DB::transaction(function () use ($request, $pedidocompra) {
            $pedidocompra->update([
                'empresa_id'   => $request['empresa_id'],
                'pessoa_id'    => $request['pessoa_id'],
                'data'         => $request['data'],
                'numeropedido' => $request['numeropedido'],
                'observacao'   => $request['observacao'],
                'status'       => $request['status'],
            ]);
            if ($request['produtos']) {
                foreach ($request['produtos'] as $key => $produto) {
                    $requisicao_produto = PedidocompraProduto::updateOrCreate(
                        [ 
                             'pedidocompra_id' => $pedidocompra->id,
                             'produto_id'      => $produto['pivot']['produto_id'],
                        ],
                        [ 'quantidade'      => $produto['pivot']['quantidade'],
                          'observacao'      => $produto['pivot']['observacao'],
                          'status'          => $produto['pivot']['status']
                        ]
                    );
                }
            }
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pedidocompra  $pedidocompra
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pedidocompra $pedidocompra)
    {
        $pedidocompra->ativo = false;
        $pedidocompra->save();
    }

    public function apagaritempedidocompra(PedidocompraProduto $pedidocompraProduto)
    {
        // $pedidocompraProduto->ativo = false;
        $pedidocompraProduto->delete();
    }
}
