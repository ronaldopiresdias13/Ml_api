<?php

namespace App\Http\Controllers\Api\Web\Compras;

use App\Models\Email;
use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\Fornecedor;
use App\Models\PessoaEmail;
use App\Models\PessoaEndereco;
use App\Models\PessoaTelefone;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Tipopessoa;
use Illuminate\Support\Facades\DB;

class FornecedoresController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllByEmpresaId(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        return Fornecedor::with(['pessoa.emails', 'pessoa.telefones', 'pessoa.enderecos.cidade'])
            ->where('empresa_id', $empresa_id)
            ->where('ativo', true)
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $empresa_id = $user->pessoa->profissional->empresa_id;
        $fornecedor = Fornecedor::create([
            'empresa_id' => $empresa_id,
            'pessoa_id'  => Pessoa::create(
                [
                    'nome'        => $request['pessoa']['nome'],
                    'nascimento'  => $request['pessoa']['nascimento'],
                    'cpfcnpj'     => $request['pessoa']['cpfcnpj'],
                    'rgie'        => $request['pessoa']['rgie'],
                    'observacoes' => $request['pessoa']['observacoes'],
                    'perfil'      => $request['pessoa']['perfil'],
                    'status'      => $request['pessoa']['status'],
                ]
            )->id,
        ]);
        $tipopessoa = Tipopessoa::create([
            'tipo'      => 'Fornecedor',
            'pessoa_id' => $fornecedor->pessoa_id,
            'ativo'     => 1
        ]);
        foreach ($request['pessoa']['telefones'] as $key => $telefone) {
            if ($telefone['telefone']) {
                PessoaTelefone::firstOrCreate([
                    'pessoa_id'   => $fornecedor->pessoa_id,
                    'telefone_id' => Telefone::firstOrCreate(
                        [
                            'telefone'  => $telefone['telefone'],
                        ]
                    )->id,
                    'tipo'      => $telefone['pivot']['tipo'],
                    'descricao' => $telefone['pivot']['descricao'],
                ]);
            }
        }
        foreach ($request['pessoa']['emails'] as $key => $email) {
            foreach ($request['pessoa']['emails'] as $key => $email) {
                if ($email['email']) {
                    PessoaEmail::firstOrCreate([
                        'pessoa_id' => $fornecedor->pessoa_id,
                        'email_id'  => Email::firstOrCreate(
                            [
                                'email' => $email['email'],
                            ]
                        )->id,
                        'tipo'      => $email['pivot']['tipo'],
                        'descricao' => $email['pivot']['descricao'],
                    ]);
                }
            }
        }
        foreach ($request['pessoa']['enderecos'] as $key => $endereco) {
            PessoaEndereco::firstOrCreate([
                'pessoa_id'   => $fornecedor->pessoa_id,
                'endereco_id' => Endereco::updateOrCreate(
                    [
                        'id' => $endereco['id'],
                    ],
                    // $endereco,
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
        return $fornecedor;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fornecedor  $fornecedor
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Fornecedor $fornecedor)
    {
        $fornecedor->pessoa->emails;
        $fornecedor->pessoa->telefones;
        if ($fornecedor->pessoa->enderecos) {
            foreach ($fornecedor->pessoa->enderecos as $key => $endereco) {
                $endereco->cidade;
            }
        }
        return $fornecedor;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fornecedor  $fornecedor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Fornecedor $fornecedor)
    {
        DB::transaction(function () use ($request) {
            $pessoa = Pessoa::find($request['pessoa']['id']);
            if ($pessoa) {
                $pessoa->update([
                    'nome'        => $request['pessoa']['nome'],
                    'nascimento'  => $request['pessoa']['nascimento'],
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
                        PessoaTelefone::firstOrCreate(
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
                    $pessoa_endereco = PessoaEndereco::updateOrCreate(
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

        return response()->json('Fornecedor atualizado com sucesso!', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fornecedor  $fornecedor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Fornecedor $fornecedor)
    {
        $fornecedor->ativo = false;
        $fornecedor->save();
    }
}
