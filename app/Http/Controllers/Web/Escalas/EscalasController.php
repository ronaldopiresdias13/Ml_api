<?php

namespace App\Http\Controllers\Web\Escalas;

use App\Http\Controllers\Controller;
use App\Models\CuidadoEscala;
use App\Models\Escala;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EscalasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function medicao(Request $request)
    {
        // $user = $request->user();
        // $empresa_id = $user->pessoa->profissional->empresa->id;

        $hoje = getdate();
        $data = $hoje['year'] . '-' . ($hoje['mon'] < 10 ? '0' . $hoje['mon'] : $hoje['mon']) . '-' . $hoje['mday'];

        $dados = Escala::with([
            'pontos',
            'servico',
            'prestador.pessoa'
        ])
            // ->where('empresa_id', $empresa_id)
            ->where('ordemservico_id', 'like', $request->ordemservico_id ? $request->ordemservico_id : '%')
            ->where('servico_id', 'like', $request->servico_id ? $request->servico_id : '%')
            ->where('dataentrada', '>=', $request->data_ini ? $request->data_ini : $data)
            ->where('dataentrada', '<=', $request->data_fim ? $request->data_fim : $data)
            ->where('ativo', true)
            ->orderBy("dataentrada")->orderBy("periodo")
            ->get([
                "id",
                "empresa_id",
                "ordemservico_id",
                "prestador_id",
                "servico_id",
                "horaentrada",
                "horasaida",
                "dataentrada",
                "datasaida",
                "periodo",
                // "observacao",
                "status",
                // "folga",
                // "substituto",
                // "tipo",
                "valorhoradiurno",
                "valorhoranoturno",
                "valoradicional",
                "valordesconto",
                // "motivoadicional"
            ]);

        // return $dados;

        $escalas = [];

        foreach ($dados as $key => $dado) {
            $escala = [];
            $servico = $dado->servico ? $dado->servico->descricao : '';
            switch ($servico) {
                case 'CUIDADOR':
                case 'TECNICO EM ENFERMAGEM':
                case 'Auxiliar de Enfermagem':
                case 'ENFERMEIRO (A)':
                    $escala = $this->calcularPontos($dado, false);
                    break;
                default:
                    $escala = $this->calcularPontos($dado, true);
                    break;
            }
            // if (!array_key_exists($dado->dataentrada, $escalas)) {
            //     $escalas[$dado->dataentrada] = [];
            // }
            array_push($escalas, $escala);
        }
        return $escalas;
    }

    private function calcularPontos(Escala $dado, bool $mult)
    {
        $escala['escala_id'] = $dado->id;
        $escala['prestador_id'] = $dado->prestador_id;
        $escala['prestador'] = $dado->prestador->pessoa->nome;
        $escala['pessoa_id'] = $dado->prestador->pessoa->id;
        $escala['periodo'] = $dado->periodo;

        $escala['servico']['id']        = $dado->servico ? $dado->servico->id : null;
        $escala['servico']['descricao'] = $dado->servico ? $dado->servico->descricao : null;

        // $escala['servico_id'] = $dado->servico ? $dado->servico->id : null;
        // $escala['servico'] = $dado->servico ? $dado->servico->descricao : null;
        $escala['valorhora'] = (float)($dado->periodo == 'DIURNO' ?
            ($dado->valorhoradiurno ? $dado->valorhoradiurno : 0)
            : ($dado->valorhoranoturno ? $dado->valorhoranoturno : 0));
        foreach ($dado->pontos as $key => $ponto) {
            $dataPonto  = $ponto->data;
            $horaPonto  = $ponto->hora;
            if ($ponto->tipo == "Check-in") {
                $escala['checkin']['id'] = $ponto->id;
                $dataEscala = $dado->dataentrada;
                $horaEscala = $dado->horaentrada;
                if ($dataEscala == $dataPonto && $horaEscala == $horaPonto) {
                    $escala['checkin']['data'] = $dado->dataentrada;
                    $escala['checkin']['hora'] = $dado->horaentrada;
                } else {
                    $horaPrevista   = gmmktime((int)substr($horaEscala, 0, 2), (int)substr($horaEscala, 3, 2), 00, (int)substr($dataEscala, 5, 2), (int)substr($dataEscala, 8, 2), (int)substr($dataEscala, 0, 4));
                    $horaRealizada = gmmktime((int)substr($horaPonto, 0, 2), (int)substr($horaPonto, 3, 2), 00, (int)substr($dataPonto, 5, 2), (int)substr($dataPonto, 8, 2), (int)substr($dataPonto, 0, 4));
                    if ($horaRealizada > $horaPrevista + 900 || $horaRealizada < $horaPrevista - 900) {
                        $escala['checkin']['data'] = $ponto->data;
                        $escala['checkin']['hora'] = $ponto->hora;
                    } else {
                        $escala['checkin']['data'] = $dado->dataentrada;
                        $escala['checkin']['hora'] = $dado->horaentrada;
                    }
                }
                $escala['checkin']['alterado'] = $ponto->status;
            } else {
                $escala['checkout']['id'] = $ponto->id;
                $dataEscala = $dado->datasaida;
                $horaEscala = $dado->horasaida;
                if ($dataEscala == $dataPonto && $horaEscala == $horaPonto) {
                    $escala['checkout']['data'] = $dado->datasaida;
                    $escala['checkout']['hora'] = $dado->horasaida;
                } else {
                    $horaPrevista   = gmmktime((int)substr($horaEscala, 0, 2), (int)substr($horaEscala, 3, 2), 00, (int)substr($dataEscala, 5, 2), (int)substr($dataEscala, 8, 2), (int)substr($dataEscala, 0, 4));
                    $horaRealizada = gmmktime((int)substr($horaPonto, 0, 2), (int)substr($horaPonto, 3, 2), 00, (int)substr($dataPonto, 5, 2), (int)substr($dataPonto, 8, 2), (int)substr($dataPonto, 0, 4));
                    if ($horaRealizada > $horaPrevista + 900 || $horaRealizada < $horaPrevista - 900) {
                        $escala['checkout']['data'] = $ponto->data;
                        $escala['checkout']['hora'] = $ponto->hora;
                    } else {
                        $escala['checkout']['data'] = $dado->datasaida;
                        $escala['checkout']['hora'] = $dado->horasaida;
                    }
                }
                $escala['checkout']['alterado'] = $ponto->status;
            }
        }
        if (array_key_exists('checkin', $escala) && array_key_exists('checkout', $escala)) {
            $dataEntrada = $escala['checkin']['data'];
            $horaEntrada = $escala['checkin']['hora'];
            $dataSaida = $escala['checkout']['data'];
            $horaSaida = $escala['checkout']['hora'];
            $entrada = gmmktime((int)substr($horaEntrada, 0, 2), (int)substr($horaEntrada, 3, 2), 00, (int)substr($dataEntrada, 5, 2), (int)substr($dataEntrada, 8, 2), (int)substr($dataEntrada, 0, 4));
            $saida   = gmmktime((int)substr($horaSaida, 0, 2), (int)substr($horaSaida, 3, 2), 00, (int)substr($dataSaida, 5, 2), (int)substr($dataSaida, 8, 2), (int)substr($dataSaida, 0, 4));
            $intervalo = abs($saida - $entrada);
            $minutos   = round($intervalo / 60, 2);
            $horas     = round($minutos / 60, 2);
            $escala['totalhoras'] = $horas;
        } else {
            $escala['totalhoras'] = 0;
        }
        $escala['valoradicional'] = (float)$dado->valoradicional;
        $escala['valordesconto'] = (float)$dado->valordesconto;
        $escala['status'] = $dado->status;
        if ($dado->status) {
            if ($mult) {
                $escala['valortotal'] = ($escala['valorhora'] + (float)$dado->valoradicional) - (float)$dado->valordesconto;
            } else {
                $escala['valortotal'] = (($escala['valorhora'] * $escala['totalhoras']) + (float)$dado->valoradicional) - (float)$dado->valordesconto;
            }
        } else {
            $escala['valortotal'] = 0;
        }

        return $escala;
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
     * @param  \App\Models\Escala  $escala
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
     * @param  \App\Models\Escala  $escala
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Escala $escala)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Escala  $escala
     * @return \Illuminate\Http\Response
     */
    public function destroy(Escala $escala)
    {
        //
    }

    public function listaescalascalendario(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return DB::select(
            "SELECT e.id, ppr.nome AS prestador, ppac.nome AS paciente, pcli.nome AS cliente, s.descricao AS servico,
                e.dataentrada, e.datasaida, 
                e.horaentrada, e.horasaida, e.periodo, e.prestador_id, e.observacao
                FROM escalas AS e
                INNER JOIN prestadores AS pr
                ON pr.id = e.prestador_id
                INNER JOIN pessoas AS ppr
                ON ppr.id = pr.pessoa_id
                INNER JOIN ordemservicos AS os
                ON os.id = e.ordemservico_id
                INNER JOIN orcamentos AS o
                ON o.id = os.orcamento_id
                INNER JOIN homecares AS hc
                ON hc.orcamento_id = o.id
                INNER JOIN pacientes AS pac
                ON pac.id = hc.paciente_id
                INNER JOIN pessoas AS ppac
                ON ppac.id = pac.pessoa_id
                INNER JOIN clientes AS cli
                ON cli.id = o.cliente_id
                INNER JOIN pessoas AS pcli
                ON pcli.id = cli.pessoa_id
                INNER JOIN servicos AS s
                ON s.id = e.servico_id

                WHERE e.dataentrada BETWEEN ? AND ?
                AND e.empresa_id = ?
                AND e.ativo = 1
                AND e.ordemservico_id LIKE ?
                ORDER BY e.periodo
                ",
            [
                $request->data_ini,
                $request->data_fim,
                $empresa_id,
                $request->ordemservico_id ? $request->ordemservico_id : '%'
            ]
        );
    }

    public function clonarEscalas(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        $escalas = Escala::where('ativo', true)
            ->where('empresa_id', $empresa_id)
            ->whereBetween('dataentrada', [$request->data_ini, $request->data_fim,])
            ->where('ordemservico_id', 'like', $request->ordemservico_id ? $request->ordemservico_id : '%')
            // ->limit(10)
            ->get();
        $teste = date('Y-m-d', strtotime('+1 month', strtotime('2021-02-28')));
        $last_day = date('d', strtotime('last day of this month', strtotime($request->data_ini)));
        $last_date = date('Y-m-d', strtotime('last day of this month', strtotime($request->data_ini)));
        $next_month_end = date('Y-m-d', strtotime('last day of next month', strtotime($request->data_ini)));
        $last_day_next_mont = date('d', strtotime('last day of next month', strtotime($request->data_ini)));
        // return response()->json([
        //     'teste' => $teste,
        //     'last_day' => $last_day,
        //     'last_date' => $last_date,
        //     'next_month_end' => $next_month_end,
        //     'last_day_next_mont' => $last_day_next_mont
        // ]);
        foreach ($escalas as $key => $escala) {
            $escala->dataentrada = date('Y-m-d', strtotime($last_day % 2 == 1 ? '30 days' : '+1 month', strtotime($escala->dataentrada)));
            $escala->datasaida = date('Y-m-d', strtotime($last_day % 2 == 1 ? '30 days' : '+1 month', strtotime($escala->datasaida)));
            // $escala->cuidados;
            // $e = Escala::create([

            //     // $e = new Escala();
            //     'empresa_id'             => $escala->empresa_id,
            //     'ordemservico_id'        => $escala->ordemservico_id,
            //     'prestador_proprietario' => $escala->prestador_proprietario,
            //     'prestador_id'           => $escala->prestador_proprietario,
            //     'servico_id'             => $escala->servico_id,
            //     'formacao_id'            => $escala->formacao_id,
            //     'horaentrada'            => $escala->horaentrada,
            //     'horasaida'              => $escala->horasaida,
            //     'dataentrada'            =>  $escala->dataentrada,
            //     'datasaida'              => $escala->datasaida,
            //     'periodo'                => $escala->periodo,
            //     'tipo'                   => $escala->tipo,
            //     'valorhoradiurno'        => $escala->valorhoradiurno,
            //     'valorhoranoturno'       => $escala->valorhoranoturno,
            //     // $e->valoradicional         = $escala->valoradicional;
            //     // $e->valordesconto          = $escala->valordesconto;
            //     // $e->motivoadicional        = $escala->motivoadicional;
            //     // $e->motivodesconto         = $escala->motivodesconto;
            //     // $e->ativo                  = $escala->ativo;
            //     // $e->editavel               = $escala->editavel;
            //     // $e->save();
            // ]);
            // foreach ($escala->cuidados as $key => $cuidado) {
            //     CuidadoEscala::create([
            //         'escala_id'  => $e->id,
            //         'cuidado_id' => $cuidado['id'],
            //         'data'       => null,
            //         'hora'       => null,
            //         'status'     => false,
            //     ]);
            // }
            //     // return $e;
        }
        $this->salvarEscalasClonadas($escalas);
        // return 'Churrasco por conta do Romulo!!!';
    }
    public function salvarEscalasClonadas($escalas)
    {
        foreach ($escalas as $key => $escala) {
            $e = Escala::create([
                'empresa_id'             => $escala->empresa_id,
                'ordemservico_id'        => $escala->ordemservico_id,
                'prestador_proprietario' => $escala->prestador_proprietario,
                'prestador_id'           => $escala->prestador_proprietario,
                'servico_id'             => $escala->servico_id,
                'formacao_id'            => $escala->formacao_id,
                'horaentrada'            => $escala->horaentrada,
                'horasaida'              => $escala->horasaida,
                'dataentrada'            =>  $escala->dataentrada,
                'datasaida'              => $escala->datasaida,
                'periodo'                => $escala->periodo,
                'tipo'                   => $escala->tipo,
                'valorhoradiurno'        => $escala->valorhoradiurno,
                'valorhoranoturno'       => $escala->valorhoranoturno,
            ]);
            foreach ($escala->cuidados as $key => $cuidado) {
                CuidadoEscala::create([
                    'escala_id'  => $e->id,
                    'cuidado_id' => $cuidado['id'],
                    'data'       => null,
                    'hora'       => null,
                    'status'     => false,
                ]);
            }
        }
        return $escalas;
    }
}
