<?php

namespace App\Http\Controllers\Api\Web\GestaoOrcamentaria;

use App\Models\Cliente;
use App\Models\Email;
use App\Models\Endereco;
use App\Http\Controllers\Controller;
use App\Models\Pessoa;
use App\Models\PessoaEmail;
use App\Models\PessoaEndereco;
use App\Models\PessoaTelefone;
use App\Models\Telefone;
use App\Models\Tipopessoa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientesController extends Controller
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
        return Cliente::with(['pessoa.emails', 'pessoa.telefones', 'pessoa.enderecos.cidade', 'pessoa.user.acessos'])
            ->where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->get();
        // ->orderBy('pessoas.nome', 'DESC');
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
            $user = $request->user();
            $empresa_id = $user->pessoa->profissional->empresa_id;
            $cliente = Cliente::firstOrCreate(
                [
                    'pessoa_id'  => Pessoa::firstOrCreate(
                        [
                            'cpfcnpj'     => $request['pessoa']['cpfcnpj'],
                        ],
                        [
                            'id' => ($request['pessoa']['id'] != '') ? $request['id'] : null,
                            'nome'        => $request['pessoa']['nome'],
                            'nascimento'  => $request['pessoa']['nascimento'],
                            'rgie'        => $request['pessoa']['rgie'],
                            'observacoes' => $request['pessoa']['observacoes'],
                            'perfil'      => $request['pessoa']['perfil'],
                            'status'      => $request['pessoa']['status'],
                        ]
                    )->id,
                    'empresa_id' => $empresa_id,
                ],
                [
                    'versaoTiss'        => $request['versaoTiss'],
                    'tipo'              => $request['tipo'],
                    'CNES'              => $request['CNES'],
                    'registroAns'       => $request['registroAns'],
                    'numeroAutorizacao' => $request['numeroAutorizacao'],
                ]
            );

            Tipopessoa::updateOrCreate(
                [
                    'tipo'      => 'Cliente',
                    'pessoa_id' => $cliente->pessoa_id,
                ],
                [
                    'ativo'     => 1
                ]
            );

            if ($request['pessoa']['telefones']) {
                foreach ($request['pessoa']['telefones'] as $key => $telefone) {
                    if ($telefone['telefone']) {
                        PessoaTelefone::firstOrCreate([
                            'pessoa_id'   => $cliente->pessoa_id,
                            'telefone_id' => Telefone::firstOrCreate(
                                [
                                    'telefone'  => $telefone['telefone'],
                                ]
                            )->id,
                            'tipo'       => $telefone['pivot']['tipo'],
                            'descricao'  => $telefone['pivot']['descricao']
                        ]);
                    }
                }
            }

            if ($request['pessoa']['enderecos']) {
                foreach ($request['pessoa']['enderecos'] as $key => $endereco) {
                    PessoaEndereco::firstOrCreate([
                        'pessoa_id'   => $cliente->pessoa_id,
                        'endereco_id' => Endereco::firstOrCreate(
                            [
                                'cep'         => $endereco['cep'],
                                'cidade_id'   => $endereco['cidade']['id'],
                                'rua'         => $endereco['rua'],
                                'bairro'      => $endereco['bairro'],
                                'numero'      => $endereco['numero'],
                                'complemento' => $endereco['complemento'],
                                'tipo'        => $endereco['tipo'],
                                'descricao'   => $endereco['descricao'],
                            ]
                        )->id,
                    ]);
                }
            }

            if ($request['pessoa']['emails']) {
                foreach ($request['pessoa']['emails'] as $key => $email) {
                    if ($email['email']) {
                        PessoaEmail::firstOrCreate([
                            'pessoa_id' => $cliente->pessoa_id,
                            'email_id'  => Email::firstOrCreate(
                                [
                                    'email'     => $email['email'],
                                ]
                            )->id,
                            'tipo'       => $email['pivot']['tipo'],
                            'descricao'  => $email['pivot']['descricao']
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function show(Cliente $cliente)
    {
        $cliente->pessoa;
        $cliente->pessoa->telefones;
        $cliente->pessoa->telefones;
        $cliente->pessoa->emails;
        if ($cliente->pessoa->enderecos) {
            foreach ($cliente->pessoa->enderecos as $key => $endereco) {
                $endereco->cidade;
            }
        }
        return $cliente;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cliente $cliente)
    {
        DB::transaction(function () use ($request, $cliente) {
            $cliente->update([
                'tipo'             => $request['tipo'],
                'versaoTiss'       => $request['versaoTiss'],
                'CNES'             => $request['CNES'],
                'registroAns'      => $request['registroAns'],
                'numeroAutorizacao' => $request['numeroAutorizacao'],
                'empresa_id'        => $request['empresa_id'],
            ]);
            $pessoa = Pessoa::find($request['pessoa']['id']);
            if ($pessoa) {
                $pessoa->update([
                    'nome'        => $request['pessoa']['nome'],
                    'nascimento'  => $request['pessoa']['nascimento'],
                    // 'tipo'        => $request['pessoa']['tipo'],
                    'cpfcnpj'     => $request['pessoa']['cpfcnpj'],
                    'rgie'        => $request['pessoa']['rgie'],
                    'observacoes' => $request['pessoa']['observacoes'],
                    'perfil'      => $request['pessoa']['perfil'],
                    'status'      => $request['pessoa']['status'],
                ]);
            }

            foreach ($pessoa->telefones as $key => $telefone) {
                $pessoatelefone = Pessoatelefone::find($telefone->pivot->id);
                $pessoatelefone->delete();
            }

            if ($request['pessoa']['telefones']) {
                foreach ($request['pessoa']['telefones'] as $key => $telefone) {
                    if ($telefone['telefone']) {
                        PessoaTelefone::updateOrCreate(
                            [
                                'pessoa_id'   => $pessoa->id,
                                'telefone_id' => Telefone::firstOrCreate(
                                    [
                                        'telefone'  => $telefone['telefone'],
                                    ]
                                )->id,
                            ],
                            [
                                'tipo'      => $telefone['pivot']['tipo'],
                                'descricao' => $telefone['pivot']['descricao'],

                            ]
                        );
                    }
                }
            }
            if ($request['pessoa']['enderecos']) {
                foreach ($request['pessoa']['enderecos'] as $key => $endereco) {
                    PessoaEndereco::updateOrCreate(
                        [
                            'pessoa_id'   => $pessoa->id,
                            'endereco_id' => Endereco::firstOrCreate(
                                [
                                    'cep'         => $endereco['cep'],
                                    'cidade_id'   => $endereco['cidade']['id'],
                                    'rua'         => $endereco['rua'],
                                    'bairro'      => $endereco['bairro'],
                                    'numero'      => $endereco['numero'],
                                    'complemento' => $endereco['complemento'],
                                    'tipo'        => $endereco['tipo'],
                                    'descricao'   => $endereco['descricao'],
                                ]
                            )->id,
                        ]
                    );
                }
            }

            foreach ($pessoa->emails as $key => $email) {
                $pessoaemail = Pessoaemail::find($email->pivot->id);
                $pessoaemail->delete();
            }

            if ($request['pessoa']['emails']) {
                foreach ($request['pessoa']['emails'] as $key => $email) {
                    if ($email['email']) {
                        PessoaEmail::updateOrCreate(
                            [
                                'pessoa_id' => $pessoa->id,
                                'email_id'  => Email::firstOrCreate(
                                    [
                                        'email' => $email['email'],
                                    ]
                                )->id,
                            ],
                            [
                                'tipo'      => $email['pivot']['tipo'],
                                'descricao' => $email['pivot']['descricao'],
                            ]
                        );
                    }
                }
            }
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cliente $cliente)
    {
        $cliente->ativo = false;
        $cliente->save();
    }
    public function clientePage(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        $result = Cliente::with(['pessoa.emails', 'pessoa.telefones', 'pessoa.enderecos.cidade', 'pessoa.user.acessos']);
        $result->where('empresa_id', $empresa_id);
        $result->where('ativo', true);

        if($request->nome)
        {
            $result->whereHas('pessoa', function (Builder $query) use ($request) {
                $query->where('nome', 'like', '%' . $request->nome . '%');
            });
        };

        $result = $result->paginate($request['per_page'] ? $request['per_page'] : 15);

       

        if (env("APP_ENV", 'production') == 'production') {
            return $result->withPath(str_replace('http:', 'https:', $result->path()));
        } else {
            return $result;
        }
    }


}
