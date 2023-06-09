<?php

namespace App\Http\Controllers\Web\Produtos;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdutosController extends Controller
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
     * @param  \App\Models\Produto  $produto
     * @return \Illuminate\Http\Response
     */
    public function show(Produto $produto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Produto  $produto
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Produto $produto)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Produto  $produto
     * @return \Illuminate\Http\Response
     */
    public function destroy(Produto $produto)
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function buscaLocalizacaoProdutos(Request $request)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;

        $query = DB::select("
        SELECT pe.nome, p.descricao AS produto, c.nome AS cidade, c.uf , c.latitude, c.longitude
        FROM orcamentos o
        INNER JOIN orcamento_produto op ON op.orcamento_id = o.id
        INNER JOIN produtos p ON p.id = op.produto_id
        INNER JOIN homecares h ON h.orcamento_id = o.id
        INNER JOIN pacientes pa ON pa.id = h.paciente_id
        INNER JOIN pessoas pe ON pe.id = pa.pessoa_id
        INNER JOIN cidades c ON c.id = o.cidade_id
        INNER JOIN ordemservicos os ON os.orcamento_id = o.id
        WHERE o.ativo = 1 AND o.`status` = 1 AND os.empresa_id = ? AND op.ativo = 1", [$empresa_id]);
        return $query;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function buscaProdutoPorCodBarra(Request $request)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;

        $result = Produto::where('empresa_id', $empresa_id)
            ->where('codigobarra', $request->codigobarra)
            ->first();

        return $result;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function salvaProdutosImportados(Request $request)
    {
        $empresa_id = $request->user()->pessoa->profissional->empresa_id;
        DB::transaction(function () use ($request, $empresa_id) {
            foreach ($request['itens_brasindice'] as $key => $itens_brasindice) {
                Produto::create([
                    'empresa_id'                => $empresa_id,
                    'tabela'                    => $request['descricao'],
                    'descricao'                 => $itens_brasindice['nome_produto'] . ' ' . $itens_brasindice['nome_apresentacao'],
                    'tipoproduto_id'            => null,
                    'codigo'                    => $itens_brasindice['codigo_produto'],
                    'unidademedida_id'          => null,
                    'codigobarra'               => null,
                    'validade'                  => null,
                    'grupo'                     => null,
                    'observacoes'               => null,
                    'valorcusto'                => null,
                    'valorvenda'                => null,
                    'ultimopreco'               => $itens_brasindice['preco_produto'],
                    'estoqueminimo'             => null,
                    'estoquemaximo'             => null,
                    'quantidadeestoque'         => null,
                    'armazem'                   => null,
                    'localizacaofisica'         => null,
                    'datacompra'                => null,
                    'marca_id'                  => null,
                    'desvalorizacao'            => null,
                    'valorfinal'                => null,
                    'tipo'                      => 'Produto',
                    'codtuss'                   => $itens_brasindice['codigo_tuss'],
                    'categoria'                 => $request['categoria'],
                ]);
            }
        });
    }
    public function pegarProdutosPorId(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        $result = Produto::with('marca', 'tipoproduto', 'unidademedida');
        $result->where('empresa_id', $empresa_id);
        $result->where('tabela', '=' , 'Estoque');
        $result->where('ativo', true);

        if($request->categoria)
        {
            $result->where('categoria', 'like', '%' . $request->categoria . '%');
        };
        if($request->descricao)
        {
            $result->where('descricao', 'like', '%' . $request->descricao . '%');
        };

        $result = $result->paginate($request['per_page'] ? $request['per_page'] : 15);

       

        if (env("APP_ENV", 'production') == 'production') {
            return $result->withPath(str_replace('http:', 'https:', $result->path()));
        } else {
            return $result;
        }
    }
}
