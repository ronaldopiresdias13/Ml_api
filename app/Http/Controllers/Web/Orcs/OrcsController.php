<?php

namespace App\Http\Controllers\Web\Orcs;

use App\Http\Controllers\Controller;
use App\Models\Orc;
use App\Services\ContratoService;
use App\Services\OrcService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrcsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;
        if (!$empresa_id) {
            return 'error';
        }
        $result = Orc::with(
            [
                'cidade',
                'cliente.pessoa',
                'homecare_paciente.pessoa',
                'aph_cidade',
                'evento_cidade',
                'remocao_cidadeorigem',
                'remocao_cidadedestino',
                'produtos.produto',
                'servicos.servico',
                'custos'
            ],
        )
            ->where('empresa_id', $empresa_id);

        if ($request->filter_nome) {
            $result->whereHas('homecare_paciente.pessoa', function (Builder $query) use ($request) {
                $query->where('nome', 'like', '%' . $request->filter_nome . '%');
            });

            $result->orWhere(function ($query) use ($empresa_id, $request) {
                $query->where('empresa_id', $empresa_id)
                    ->where('remocao_nome', 'like', '%' . $request->filter_nome . '%');
            });
        }

        $result = $result->orderByDesc('id')->paginate($request['per_page'] ? $request['per_page'] : 15);

        if (env("APP_ENV", 'production') == 'production') {
            return $result->withPath(str_replace('http:', 'https:', $result->path()));
        } else {
            return $result;
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $orcService = new OrcService($request);
        return $orcService->store();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Orc $orc
     * @return \Illuminate\Http\Response
     */
    public function show(Orc $orc)
    {
        return Orc::with(
            [
                'cidade',
                'cliente.pessoa',
                'homecare_paciente.pessoa',
                'aph_cidade',
                'evento_cidade',
                'remocao_cidadeorigem',
                'remocao_cidadedestino',
                'produtos.produto',
                'servicos.servico',
                'custos'
            ],
        )->find($orc->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Orc  $orc
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Orc $orc)
    {
        $orcService = new OrcService($request, $orc);
        return $orcService->update();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Orc  $orc
     * @return \Illuminate\Http\Response
     */
    public function destroy(Orc $orc)
    {
        $orc->delete();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Orc  $orc
     * @return \Illuminate\Http\Response
     */
    public function criarcontrato(Request $request, Orc $orc)
    {
        $orcamento = Orc::with(
            [
                'cidade',
                'cliente.pessoa',
                'homecare_paciente.pessoa',
                'aph_cidade',
                'evento_cidade',
                'remocao_cidadeorigem',
                'remocao_cidadedestino',
                'produtos.produto',
                'servicos.servico',
                'custos'
            ],
        )->find($orc->id);

        // return $orcamento;

        $request['cliente_id']        = $orcamento->cliente_id;
        $request['numero']            = $orcamento->numero;
        $request['tipo']              = $orcamento->tipo;
        $request['data']              = $orcamento->data;
        $request['quantidade']        = $orcamento->quantidade;
        $request['unidade']           = $orcamento->unidade;
        $request['cidade_id']         = $orcamento->cidade_id;
        $request['processo']          = $orcamento->processo;
        $request['situacao']          = $orcamento->situacao;
        $request['descricao']         = $orcamento->descricao;
        $request['valortotalproduto'] = $orcamento->valortotalproduto;
        $request['valortotalcusto']   = $orcamento->valortotalcusto;
        $request['valortotalservico'] = $orcamento->valortotalservico;
        $request['observacao']        = $orcamento->observacao;
        $request['status']            = $orcamento->status;

        // $request->ordemservico = $orcamento->ordemservico;

        return $request;

        switch ($orcamento->tipo) {
            case 'Venda':
                $request['venda'] = [];
                $request['venda']['realizada'] = $orcamento->venda_realizada;
                $request['venda']['data']      = $orcamento->venda_data;
                break;
            case 'Home Care':
                $request['homecare'] = [];
                $request['homecare']['orcamento_id'] = $orcamento->id;
                $request['homecare']['paciente_id']  = $orcamento->homecare_paciente_id;
                break;
            case 'APH':
                # code...
                break;
            case 'Evento':
                # code...
                break;
            case 'Remocao':
                # code...
                break;

            default:
                # code...
                break;
        }

        // $contratoService = new ContratoService($request);
        // return $contratoService->store();
    }

    public function gerarCodigo(Request $request)
    {
        $now = now()->format('Y');
        $codigo = null;
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;
        $orcamento = Orc::where('empresa_id', $empresa_id)->orderBy('id', 'desc')->first();
        if ($orcamento) {
            $numero = substr($orcamento->numero, 5) + 1;
            $numero = $numero < 10 ? '0' . $numero : $numero;
            $codigo = $now . '/' . $numero;
        } else {
            $codigo = $now . '/01';
        }
        return response()->json(['codigo' => $codigo]);
    }
}


// { label: "Home Care", value: "Home Care" },
//     { label: "Remoção", value: "Remocao" },
//     { label: "Evento", value: "Evento" },
//     { label: "APH (Atendimento Pré Hospitalar)", value: "APH" },
//     { label: "Tomada de Preço", value: "Tomada de Preço" }
