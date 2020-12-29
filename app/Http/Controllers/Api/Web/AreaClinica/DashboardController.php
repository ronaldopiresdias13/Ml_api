<?php

namespace App\Http\Controllers\Api\Web\AreaClinica;

use App\Escala;
use App\Http\Controllers\Controller;
use App\Ordemservico;
use App\Relatorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Boolean;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function relatorioDiario(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa->id;

        // $request->data_ini = '2020-10-01';
        // $request->data_fim = '2020-10-02';

        $hoje = getdate();
        $data = $hoje['year'] . '-' . ($hoje['mon'] < 10 ? '0' . $hoje['mon'] : $hoje['mon']) . '-' . $hoje['mday'];

        $escalas = Escala::with([
            'ordemservico' => function ($query) {
                $query->select('id', 'orcamento_id', 'profissional_id');
                $query->with(['orcamento' => function ($query) {
                    $query->select('id', 'cliente_id');
                    $query->with([
                        'cliente' => function ($query) {
                            $query->select('id', 'pessoa_id');
                            $query->with(['pessoa' => function ($query) {
                                $query->select('id', 'nome');
                            }]);
                        },
                        'homecare' => function ($query) {
                            $query->select('id', 'orcamento_id', 'paciente_id');
                            $query->with(['paciente' => function ($query) {
                                $query->select('id', 'pessoa_id');
                                $query->with(['pessoa' => function ($query) {
                                    $query->select('id', 'nome', 'nascimento', 'cpfcnpj', 'rgie');
                                }]);
                            }]);
                        }
                    ]);
                }]);
            },
            'servico' => function ($query) {
                $query->select('id', 'descricao');
            },
            'formacao',
            'prestador' => function ($query) {
                $query->select('id', 'pessoa_id');
                $query->with(['formacoes' => function ($query) {
                    $query->select('prestador_id', 'descricao');
                }]);
                $query->with(['pessoa' => function ($query) {
                    $query->select('id', 'nome');
                    $query->with(['conselhos' => function ($query) {
                        $query->select('pessoa_id', 'instituicao', 'uf', 'numero');
                    }]);
                }]);
            },
            // 'pontos',
            // 'cuidados',
            'relatorios',
            'monitoramentos',
            // 'acaomedicamentos.transcricaoProduto.produto'
        ])
            ->where('ativo', true)
            ->where('empresa_id', $empresa_id)
            ->where('ordemservico_id', 'like', $request->ordemservico_id ? $request->ordemservico_id : '%')
            ->where('dataentrada', '>=', $request->data_ini ? $request->data_ini : $data)
            ->where('dataentrada', '<=', $request->data_fim ? $request->data_fim : $data)
            ->where('prestador_id', 'like', $request->prestador_id ? $request->prestador_id : '%')
            ->where('servico_id', 'like', $request->servico_id ? $request->servico_id : '%')
            // ->where('empresa_id', 'like', $request->empresa_id ? $request->empresa_id : '%')
            // ->limit(5)
            ->orderBy('dataentrada')
            ->get([
                'id',
                'dataentrada',
                'datasaida',
                'horaentrada',
                'horasaida',
                'valorhoradiurno',
                'valorhoranoturno',
                'valoradicional',
                'motivoadicional',
                'servico_id',
                'formacao_id',
                'periodo',
                'tipo',
                'prestador_id',
                'ordemservico_id',
                'assinaturaprestador',
                'assinaturaresponsavel',
                'status'
            ]);

        $relatorio = [];

        foreach ($escalas as $key => $escala) {
            if ($escala->formacao) {
                switch ($escala->formacao->descricao) {
                    case 'Cuidador':
                    case 'Técnico de Enfermagem':
                    case 'Auxiliar de Enfermagem':
                    case 'Enfermagem':
                        $relatorio = $this->pushDiario($relatorio, $escala, true);
                        break;
                    default:
                        $relatorio = $this->pushDiario($relatorio, $escala, false);
                        break;
                }
            }
        }

        return $relatorio;
        // return $escalas;
    }

    private function pushDiario($array, $item, $enfermagem)
    {
        if ($enfermagem) {
            if (!key_exists('Enfermagem', $array)) {
                $array['Enfermagem'] = [];
            }
            if (!key_exists($item->dataentrada, $array['Enfermagem'])) {
                $array['Enfermagem'][$item->dataentrada] = [];
            }
            array_push($array['Enfermagem'][$item->dataentrada], $item);
        } else {
            if (!key_exists($item->formacao->descricao, $array)) {
                $array[$item->formacao->descricao] = [];
            }
            if (!key_exists($item->dataentrada, $array[$item->formacao->descricao])) {
                $array[$item->formacao->descricao][$item->dataentrada] = [];
            }
            array_push($array[$item->formacao->descricao][$item->dataentrada], $item);
        }
        return $array;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function relatorioProdutividade(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa->id;

        // $request->data_ini = '2020-10-01';
        // $request->data_fim = '2020-10-02';

        $hoje = getdate();
        $data = $hoje['year'] . '-' . ($hoje['mon'] < 10 ? '0' . $hoje['mon'] : $hoje['mon']) . '-' . $hoje['mday'];

        $escalas = Escala::with([
            'ordemservico' => function ($query) {
                $query->select('id', 'orcamento_id', 'profissional_id');
                $query->with(['orcamento' => function ($query) {
                    $query->select('id', 'cliente_id');
                    $query->with([
                        'cliente' => function ($query) {
                            $query->select('id', 'pessoa_id');
                            $query->with(['pessoa' => function ($query) {
                                $query->select('id', 'nome');
                            }]);
                        },
                        'homecare' => function ($query) {
                            $query->select('id', 'orcamento_id', 'paciente_id');
                            $query->with(['paciente' => function ($query) {
                                $query->select('id', 'pessoa_id');
                                $query->with(['pessoa' => function ($query) {
                                    $query->select('id', 'nome', 'nascimento', 'cpfcnpj', 'rgie');
                                }]);
                            }]);
                        }
                    ]);
                }]);
            },
            'servico' => function ($query) {
                $query->select('id', 'descricao');
            },
            'formacao',
            'prestador' => function ($query) {
                $query->select('id', 'pessoa_id');
                $query->with(['formacoes' => function ($query) {
                    $query->select('prestador_id', 'descricao');
                }]);
                $query->with(['pessoa' => function ($query) {
                    $query->select('id', 'nome');
                    $query->with(['conselhos' => function ($query) {
                        $query->select('pessoa_id', 'instituicao', 'uf', 'numero');
                    }]);
                }]);
            },
            'pontos',
            // 'cuidados',
            // 'relatorios',
            // 'monitoramentos',
            // 'acaomedicamentos.transcricaoProduto.produto'
        ])
            ->where('ativo', true)
            ->where('empresa_id', $empresa_id)
            ->where('ordemservico_id', 'like', $request->ordemservico_id ? $request->ordemservico_id : '%')
            ->where('dataentrada', '>=', $request->data_ini ? $request->data_ini : $data)
            ->where('dataentrada', '<=', $request->data_fim ? $request->data_fim : $data)
            ->where('prestador_id', 'like', $request->prestador_id ? $request->prestador_id : '%')
            ->where('servico_id', 'like', $request->servico_id ? $request->servico_id : '%')
            // ->where('empresa_id', 'like', $request->empresa_id ? $request->empresa_id : '%')
            // ->limit(5)
            ->orderBy('dataentrada')
            ->get([
                'id',
                'dataentrada',
                'datasaida',
                'horaentrada',
                'horasaida',
                'valorhoradiurno',
                'valorhoranoturno',
                'valoradicional',
                'motivoadicional',
                'servico_id',
                'formacao_id',
                'periodo',
                'tipo',
                'prestador_id',
                'ordemservico_id',
                'assinaturaprestador',
                'assinaturaresponsavel',
                'status'
            ]);

        $relatorio = [];

        foreach ($escalas as $key => $escala) {
            switch ($escala->formacao->descricao) {
                case 'Cuidador':
                case 'Técnico de Enfermagem':
                case 'Auxiliar de Enfermagem':
                case 'Enfermagem':
                    $relatorio = $this->pushProdutividade($relatorio, $escala, true);
                    break;
                default:
                    $relatorio = $this->pushProdutividade($relatorio, $escala, false);
                    break;
            }
        }

        return $relatorio;
    }

    private function pushProdutividade($array, $item, $enfermagem)
    {
        if ($enfermagem) {
            if (!key_exists('Enfermagem', $array)) {
                $array['Enfermagem'] = [];
            }
            array_push($array['Enfermagem'], $item);
        } else {
            if (!key_exists($item->formacao->descricao, $array)) {
                $array[$item->formacao->descricao] = [];
            }
            array_push($array[$item->formacao->descricao], $item);
        }
        return $array;
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function relatorioMedicamentos(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa->id;

        // $request->data_ini = '2020-10-01';
        // $request->data_fim = '2020-10-02';

        $hoje = getdate();
        $data = $hoje['year'] . '-' . ($hoje['mon'] < 10 ? '0' . $hoje['mon'] : $hoje['mon']) . '-' . $hoje['mday'];

        $escalas = Escala::with([
            'ordemservico' => function ($query) {
                $query->select('id', 'orcamento_id', 'profissional_id');
                $query->with(['orcamento' => function ($query) {
                    $query->select('id', 'cliente_id');
                    $query->with([
                        'cliente' => function ($query) {
                            $query->select('id', 'pessoa_id');
                            $query->with(['pessoa' => function ($query) {
                                $query->select('id', 'nome');
                            }]);
                        },
                        'homecare' => function ($query) {
                            $query->select('id', 'orcamento_id', 'paciente_id');
                            $query->with(['paciente' => function ($query) {
                                $query->select('id', 'pessoa_id');
                                $query->with(['pessoa' => function ($query) {
                                    $query->select('id', 'nome', 'nascimento', 'cpfcnpj', 'rgie');
                                }]);
                            }]);
                        }
                    ]);
                }]);
            },
            'servico' => function ($query) {
                $query->select('id', 'descricao');
            },
            'formacao',
            'prestador' => function ($query) {
                $query->select('id', 'pessoa_id');
                $query->with(['formacoes' => function ($query) {
                    $query->select('prestador_id', 'descricao');
                }]);
                $query->with(['pessoa' => function ($query) {
                    $query->select('id', 'nome');
                    $query->with(['conselhos' => function ($query) {
                        $query->select('pessoa_id', 'instituicao', 'uf', 'numero');
                    }]);
                }]);
            },
            'acaomedicamentos.transcricaoProduto.produto',
            'acaomedicamentos'
            // => function ($query) {
            //     $query->select('id');
            //     $query->with(['pessoa' => function ($query) {
            //         $query->select('id', 'nome');
            //         $query->with(['conselhos' => function ($query) {
            //             $query->select('pessoa_id', 'instituicao', 'uf', 'numero');
            //         }]);
            //     }]);
            // },
        ])
            ->where('ativo', true)
            ->where('empresa_id', $empresa_id)
            ->where('ordemservico_id', 'like', $request->ordemservico_id ? $request->ordemservico_id : '%')
            ->where('dataentrada', '>=', $request->data_ini ? $request->data_ini : $data)
            ->where('dataentrada', '<=', $request->data_fim ? $request->data_fim : $data)
            ->where('prestador_id', 'like', $request->prestador_id ? $request->prestador_id : '%')
            ->where('servico_id', 'like', $request->servico_id ? $request->servico_id : '%')
            // ->where('empresa_id', 'like', $request->empresa_id ? $request->empresa_id : '%')
            // ->limit(5)
            ->orderBy('dataentrada')
            ->get([
                'id',
                'dataentrada',
                'datasaida',
                'horaentrada',
                'horasaida',
                // 'valorhoradiurno',
                // 'valorhoranoturno',
                // 'valoradicional',
                // 'motivoadicional',
                'servico_id',
                'formacao_id',
                'periodo',
                'tipo',
                'prestador_id',
                'ordemservico_id',
                'assinaturaprestador',
                'assinaturaresponsavel',
                'status'
            ]);

        $relatorio = [];

        foreach ($escalas as $key => $escala) {
            switch ($escala->formacao->descricao) {
                case 'Cuidador':
                case 'Técnico de Enfermagem':
                case 'Auxiliar de Enfermagem':
                case 'Enfermagem':
                    $relatorio = $this->pushMedicamentos($relatorio, $escala, true);
                    break;
                default:
                    $relatorio = $this->pushMedicamentos($relatorio, $escala, false);
                    break;
            }
        }

        return $relatorio;
    }
    private function pushMedicamentos($array, $item, $enfermagem)
    {
        if ($enfermagem) {
            if (!key_exists('Enfermagem', $array)) {
                $array['Enfermagem'] = [];
            }
            if (!key_exists($item->dataentrada, $array['Enfermagem'])) {
                $array['Enfermagem'][$item->dataentrada] = [];
            }
            array_push($array['Enfermagem'][$item->dataentrada], $item);
        } else {
            if (!key_exists($item->formacao->descricao, $array)) {
                $array[$item->formacao->descricao] = [];
            }
            if (!key_exists($item->dataentrada, $array[$item->formacao->descricao])) {
                $array[$item->formacao->descricao][$item->dataentrada] = [];
            }
            array_push($array[$item->formacao->descricao][$item->dataentrada], $item);
        }
        return $array;
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
     * @param  \App\Escala  $escala
     * @return \Illuminate\Http\Response
     */
    public function show(Escala $escala)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Escala  $escala
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Escala $escala)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Escala  $escala
     * @return \Illuminate\Http\Response
     */
    public function destroy(Escala $escala)
    {
        //
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboardTotalProfissionaisEscalasPorPeriodo(Request $request)
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa->id;
        return DB::table('escalas')
            ->select(DB::raw('pessoas.nome, count(escalas.id) as total'))
            ->join('prestadores', 'prestadores.id', '=', 'escalas.prestador_id')
            ->join('pessoas', 'pessoas.id', '=', 'prestadores.pessoa_id')
            ->where('escalas.ativo', 1)
            ->where('escalas.empresa_id', $empresa_id)
            ->where('escalas.dataentrada', '>=', $request->data_ini)
            ->where('escalas.dataentrada', '<=', $request->data_fim)
            ->groupBy('escalas.prestador_id', 'pessoas.nome')
            ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboardTotalPacientesAtivosPorPeriodo(Request $request)
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa_id;
        return Ordemservico::where('empresa_id', $empresa_id)
            ->where('inicio', '>=', $request->data_ini)
            ->where('inicio', '<=', $request->data_fim)
            ->get();
        // return DB::table('escalas')
        //     ->select(DB::raw('pessoas.nome, count(escalas.id) as total'))
        //     ->join('prestadores', 'prestadores.id', '=', 'escalas.prestador_id')
        //     ->join('pessoas', 'pessoas.id', '=', 'prestadores.pessoa_id')
        //     ->where('escalas.ativo', 1)
        //     ->where('escalas.empresa_id', $empresa_id)
        //     ->where('escalas.dataentrada', '>=', $request->data_ini)
        //     ->where('escalas.dataentrada', '<=', $request->data_fim)
        //     ->groupBy('escalas.prestador_id', 'pessoas.nome')
        //     ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboardTotalPacientesServicosPorPeriodo(Request $request)
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa_id;
        return DB::table('escalas')
            ->select(DB::raw('escalas.servico_id, servicos.descricao, count(escalas.id) as total'))
            ->join('servicos', 'servicos.id', '=', 'escalas.servico_id')
            ->where('escalas.ativo', 1)
            ->where('escalas.empresa_id', $empresa_id)
            ->where('escalas.dataentrada', '>=', $request->data_ini)
            ->where('escalas.dataentrada', '<=', $request->data_fim)
            ->groupBy('escalas.servico_id', 'servicos.descricao')
            ->orderByDesc('total')
            ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboardTotalRelatoriosPorPeriodo(Request $request)
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa_id;
        return DB::table('relatorios')
            ->select(DB::raw('count(relatorios.id) as total'))
            ->join('escalas', 'escalas.id', '=', 'relatorios.escala_id')
            ->where('relatorios.ativo', 1)
            ->where('escalas.empresa_id', $empresa_id)
            ->where('relatorios.data', '>=', $request->data_ini)
            ->where('relatorios.data', '<=', $request->data_fim)
            ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function dashboardTotalProfissionaisCategoriaPorPeriodo(Request $request)
    {
        $empresa_id = Auth::user()->pessoa->profissional->empresa_id;
        return DB::table('escalas')
            ->select(DB::raw('formacoes.descricao,count(formacoes.id) as total'))
            ->join('prestadores', 'prestadores.id', '=', 'escalas.prestador_id')
            ->join('prestador_formacao', 'prestadores.id', '=', 'prestador_formacao.prestador_id')
            ->join('formacoes', 'formacoes.id', '=', 'escalas.formacao_id')
            ->where('escalas.ativo', 1)
            ->where('escalas.empresa_id', $empresa_id)
            ->where('escalas.dataentrada', '>=', $request->data_ini)
            ->where('escalas.dataentrada', '<=', $request->data_fim)
            ->groupBy('formacoes.id', 'formacoes.descricao')
            ->orderByDesc('total')
            ->get();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboarTotalContratosDesativadosPorPeriodo(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return Ordemservico::select(DB::raw('motivo, count(motivo) AS total'))
            ->where('empresa_id', $empresa_id)
            ->where('dataencerramento', '>=', $request->data_ini)
            ->where('dataencerramento', '<=', $request->data_fim)
            ->groupBy('motivo')
            ->orderByDesc('total')
            ->get();
        // return Ordemservico::select(DB::raw('motivo, count(motivo) AS total'))
        // ->where('status', 0)
        // ->where('empresa_id', $empresa_id)
        //     ->groupBy('motivo')
        //     ->orderByDesc('total')
        //     ->get();
    }
}
