<?php

namespace App\Http\Controllers\Web\Prestadores;

use App\Http\Controllers\Controller;
use App\Models\Prestador;
use App\Models\Tipopessoa;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrestadoresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listRecrutamento(Request $request)
    {
        $result = DB::table('prestadores')
            ->leftJoin('prestador_formacao', function ($join) {
                $join->on('prestadores.id', '=', 'prestador_formacao.prestador_id')
                    ->where('prestador_formacao.ativo', true);
            })
            ->leftJoin('formacoes', function ($join) {
                $join->on('formacoes.id', '=', 'prestador_formacao.formacao_id')
                    ->where('formacoes.deleted_at', null);
            })
            ->join('pessoas', function ($join) use ($request) {
                $join->on('prestadores.pessoa_id', '=', 'pessoas.id')
                    ->where('pessoas.ativo', true)
                    ->where('pessoas.nome', 'like', $request['nome'] ? '%' . $request['nome'] . '%' : '%');
            })
            ->leftJoin('pessoa_endereco', function ($join) {
                $join->on('pessoa_endereco.pessoa_id', '=', 'pessoas.id')
                    ->where('pessoa_endereco.ativo', true);
            })
            ->leftJoin('enderecos', function ($join) {
                $join->on('enderecos.id', '=', 'pessoa_endereco.endereco_id')
                    ->where('enderecos.ativo', true);
            })
            ->leftJoin('cidades', function ($join) {
                $join->on('cidades.id', '=', 'enderecos.cidade_id')
                    ->where('cidades.ativo', true);
            })
            ->leftJoin('conselhos', function ($join) {
                $join->on('pessoas.id', '=', 'conselhos.pessoa_id')
                    ->where('conselhos.ativo', true);
            })
            ->where('prestadores.ativo', true)
            ->select(
                'prestadores.id as id',
                'prestadores.created_at as created_at',
                'pessoas.nome as nome',
                'cidades.nome as cidade',
                'cidades.latitude',
                'cidades.longitude',
                'cidades.uf as uf',
                'formacoes.descricao as formacao',
                'conselhos.instituicao as conselho',
                'conselhos.uf as conselho_uf',
                'conselhos.numero as conselho_numero',
                DB::raw(
                    '
                    (6371 * ACOS(
                        cos(radians(' . $request['latitude'] .
                        ')) *
                        cos(RADIANS(cidades.latitude)) *
                        cos(radians(' . $request['longitude'] . ') - RADIANS(cidades.longitude)) +
                        sin(radians(' . $request['latitude'] . ')) *
                        sin(RADIANS(cidades.latitude))
                    )) AS campolatitude
                    '
                )
            )
            ->groupBy(
                'prestadores.id',
                'prestadores.created_at',
                'pessoas.nome',
                'pessoas.perfil',
                'cidades.nome',
                'cidades.uf',
                'cidades.latitude',
                'cidades.longitude',
                'formacoes.descricao',
                'conselhos.instituicao',
                'conselhos.uf',
                'conselhos.numero',
            )
            ->orderBy('nome');

        if ($request['formacao_id']) {
            $result->where('formacoes.id', $request['formacao_id']);
        }
        if ($request['uf'] && $request['km'] && $request['cidade_id']) {
            $result->having('campolatitude', '<=', $request['km']);
        } else {
            if ($request['uf']) {
                $result->where('cidades.uf', $request['uf']);
            }
            if ($request['cidade_id']) {
                $result->where('cidades.id', $request['cidade_id']);
            }
        }
        if ($request['data']) {
            $result->where('prestadores.created_at', 'like', $request['data'] . '%');
        }

        // return 'teste';

        $result = $result->paginate(12);
        // $result = $result->get();

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function buscaPrestadoresPorCliente(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa->id;

        return DB::select(
            "
            SELECT profp.nome AS prestador, profp.cpfcnpj, con.instituicao, con.numero, pp.nome AS paciente, e.periodo, e.tipo, s.descricao, COUNT(e.id) AS total  FROM escalas AS e
            INNER JOIN ordemservicos AS os
            ON e.ordemservico_id = os.id
            INNER JOIN orcamentos AS o
            ON os.orcamento_id = o.id
            INNER JOIN homecares AS hc
            ON hc.orcamento_id = o.id
            INNER JOIN pacientes AS pac
            ON pac.id = hc.paciente_id
            INNER JOIN pessoas AS pp
            ON pp.id = pac.pessoa_id
            INNER JOIN prestadores AS prof
            ON prof.id = e.prestador_id
            INNER JOIN pessoas AS profp
            ON profp.id = prof.pessoa_id
            left join conselhos con
            ON con.pessoa_id = profp.id
            INNER JOIN servicos AS s
            ON e.servico_id = s.id
            WHERE o.cliente_id like ?
            AND e.ativo = 1
            AND e.empresa_id = ?
            AND e.dataentrada BETWEEN ? AND ?
            GROUP BY pac.id, profp.nome, pp.nome, e.periodo, e.tipo, s.descricao, profp.cpfcnpj, con.instituicao, con.numero
            ORDER BY profp.nome",
            [
                $request->cliente_id ? $request->cliente_id : '%',
                $empresa_id,
                $request->data_ini,
                $request->data_fim
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Prestador  $prestador
     * @return \Illuminate\Http\Response
     */
    public function buscaprestadorexterno(Prestador $prestador)
    {
        return Prestador::with([
            'pessoa.telefones',
            'pessoa.enderecos.cidade',
            'pessoa.conselhos',
            'pessoa.emails',
            'especialidades',
            'formacoes'
        ])->find($prestador->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Prestador  $prestador
     * @return \Illuminate\Http\Response
     */
    public function buscaPrestadorComServicosPrestadosNaEmpresa(Request $request, Prestador $prestador)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;

        $prestadores = Prestador::with(
            [
                'ordemservicos.servico',
                'ordemservicos.ordemservico.orcamento.homecare.paciente.pessoa'
            ]
        )
            ->where('ativo', true)
            ->whereHas('ordemservicos.ordemservico', function (Builder $builder) use ($empresa_id) {
                $builder->where('empresa_id', $empresa_id);
            })
            ->find($prestador->id);

        return $prestadores;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Prestador  $prestador
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Prestador $prestador)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Prestador  $prestador
     * @return \Illuminate\Http\Response
     */
    public function destroy(Prestador $prestador)
    {
        //
    }
}
