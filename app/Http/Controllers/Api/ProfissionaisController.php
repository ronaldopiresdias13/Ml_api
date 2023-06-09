<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Email;
use App\Models\Pessoa;
use App\Models\Acesso;
use App\Models\Telefone;
use App\Models\Endereco;
use App\Models\UserAcesso;
use App\Models\PessoaEmail;
use App\Models\Profissional;
use App\Models\Dadosbancario;
use App\Models\PessoaTelefone;
use App\Models\PessoaEndereco;
use App\Models\Dadoscontratual;
use Illuminate\Http\Request;
use App\Models\ProfissionalFormacao;
use App\Models\ProfissionalConvenio;
use App\Models\ProfissionalBeneficio;
use App\Http\Controllers\Controller;
use App\Models\Tipopessoa;
use Illuminate\Support\Facades\DB;

class ProfissionaisController extends Controller
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
            $itens = Profissional::with($with)->where('ativo', true);
        } else {
            $itens = Profissional::where('ativo', true);
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
        if ($request['pessoa']) {
            $pessoa = Pessoa::where(
                'cpfcnpj',
                $request['pessoa']['cpfcnpj']
            )->first();
        } elseif ($request['pessoa_id']) {
            $pessoa = Pessoa::find($request['pessoa_id']);
        }

        $profissional = null;

        if ($pessoa) {
            $profissional = Profissional::firstWhere(
                'pessoa_id',
                $pessoa->id,
            );
        }

        if ($profissional) {
            return response()->json('Profissional já existe!', 400)->header('Content-Type', 'text/plain');
        }

        DB::transaction(function () use ($request, $profissional) {
            $profissional = Profissional::create([
                'pessoafisica' => 1,
                'empresa_id'   => 1,
                'pessoa_id'    => Pessoa::firstOrCreate(
                    [
                        'cpfcnpj'     => $request['pessoa']['cpfcnpj'],
                    ],
                    [
                        'nome'        => $request['pessoa']['nome'],
                        'nascimento'  => $request['pessoa']['nascimento'],
                        'rgie'        => $request['pessoa']['rgie'],
                        'observacoes' => $request['pessoa']['observacoes'],
                        'perfil'      => $request['pessoa']['perfil'],
                        'status'      => $request['pessoa']['status'],
                    ]
                )->id,
                'sexo'                   => $request['sexo'],
                'setor_id'               => $request['setor_id'],
                'cargo_id'               => $request['cargo_id'],
                'pis'                    => $request['pis'],
                'numerocarteiratrabalho' => $request['numerocarteiratrabalho'],
                'numerocnh'              => $request['numerocnh'],
                'categoriacnh'           => $request['categoriacnh'],
                'validadecnh'            => $request['validadecnh'],
                'numerotituloeleitor'    => $request['numerotituloeleitor'],
                'zonatituloeleitor'      => $request['zonatituloeleitor'],
                'meiativa'               => $request['meiativa'],
                'dataverificacaomei'     => $request['dataverificacaomei'],
                'dadoscontratuais_id'    => Dadoscontratual::create([
                    'tiposalario'             => $request['dadoscontratuais']['tiposalario'],
                    'salario'                 => $request['dadoscontratuais']['salario'],
                    'cargahoraria'            => $request['dadoscontratuais']['cargahoraria'],
                    'insalubridade'           => $request['dadoscontratuais']['insalubridade'],
                    'percentualinsalubridade' => $request['dadoscontratuais']['percentualinsalubridade'],
                    'admissao'                => $request['dadoscontratuais']['admissao'],
                    'demissao'                => $request['dadoscontratuais']['demissao'],
                ])->id,
            ]);
            $tipopessoa = Tipopessoa::create([
                'tipo'      => 'Profissional',
                'pessoa_id' => $profissional->pessoa_id,
                'ativo'     => 1
            ]);
            if ($request['formacoes']) {
                foreach ($request['formacoes'] as $key => $formacao) {
                    $profissional_formacao = ProfissionalFormacao::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'formacao_id'     => $formacao['formacao_id'],
                    ]);
                }
            }
            if ($request['beneficios']) {
                foreach ($request['beneficios'] as $key => $beneficio) {
                    $profissional_beneficio = ProfissionalBeneficio::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'beneficio_id'    => $beneficio['beneficio_id']
                    ]);
                }
            }
            if ($request['convenios']) {
                foreach ($request['convenios'] as $key => $convenio) {
                    $profissional_convenio = ProfissionalConvenio::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'convenio_id'    => $convenio['convenio_id']
                    ]);
                }
            }
            if ($request['dadosBancario']) {
                foreach ($request['dadosBancario'] as $key => $dadosbancario) {
                    $dados_bancario = Dadosbancario::firstOrCreate([
                        'empresa_id'  => $profissional['empresa_id'],
                        'banco_id'    => $dadosbancario['banco_id'],
                        'agencia'     => $dadosbancario['agencia'],
                        'conta'       => $dadosbancario['conta'],
                        'digito'      => $dadosbancario['digito'],
                        'tipoconta'   => $dadosbancario['tipoconta'],
                        'pessoa_id'   => $profissional->pessoa_id,
                    ]);
                }
            }

            if ($request['pessoa']['enderecos']) {
                foreach ($request['pessoa']['enderecos'] as $key => $endereco) {
                    $pessoa_endereco = PessoaEndereco::firstOrCreate([
                        'pessoa_id'   => $profissional->pessoa_id,
                        'endereco_id' => Endereco::firstOrCreate(
                            [
                                'cep'         => $endereco['cep'],
                                'cidade_id'   => $endereco['cidade_id'],
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

            if ($request['pessoa']['telefones']) {
                foreach ($request['pessoa']['telefones'] as $key => $telefone) {
                    $pessoa_telefone = PessoaTelefone::firstOrCreate([
                        'pessoa_id'   => $profissional->pessoa_id,
                        'telefone_id' => Telefone::firstOrCreate(
                            [
                                'telefone'  => $telefone['telefone'],
                            ]
                        )->id,
                        'tipo'      => $telefone['tipo'],
                        'descricao' => $telefone['descricao'],
                    ]);
                }
            }

            if ($request['pessoa']['emails']) {
                foreach ($request['pessoa']['emails'] as $key => $email) {
                    $pessoa_email = PessoaEmail::firstOrCreate([
                        'pessoa_id' => $profissional->pessoa_id,
                        'email_id'  => Email::firstOrCreate(
                            [
                                'email' => $email['email'],
                            ]
                        )->id,
                        'tipo'      => $email['tipo'],
                        'descricao' => $email['descricao'],
                    ]);
                }
            }

            // if ($request['pessoa']['user']) {
            //     if ($request['pessoa']['user']['email'] !== '') {
            //         $user = new User();
            //         if ($request['pessoa']['user']['password'] !== '') {
            //             $user = User::updateOrCreate(
            //                 [
            //                     'email'      =>        $request['pessoa']['user']['email'],
            //                 ],
            //                 [
            //                     // 'empresa_id' =>        $request['empresa_id'],
            //                     'cpfcnpj'    =>        $request['pessoa']['user']['cpfcnpj'],
            //                     'password'   => bcrypt($request['pessoa']['user']['password']),
            //                     'pessoa_id'  =>        $profissional->pessoa_id,
            //                 ]
            //             );
            //         } else {
            //             $user = User::firstOrCreate(
            //                 [
            //                     'email'      =>        $request['pessoa']['user']['email'],
            //                 ],
            //                 [
            //                     // 'empresa_id' =>        $request['empresa_id'],
            //                     'cpfcnpj'    =>        $request['pessoa']['user']['cpfcnpj'],
            //                     'password'   => bcrypt($request['pessoa']['user']['password']),
            //                     'pessoa_id'  =>        $profissional->pessoa_id,
            //                 ]
            //             );
            //         }
            //         if ($request['pessoa']['user']['acessos']) {
            //             foreach ($request['pessoa']['user']['acessos'] as $key => $acesso) {
            //                 $user_acesso = UserAcesso::firstOrCreate([
            //                     'user_id'   => $user->id,
            //                     'acesso_id' => $acesso,
            //                 ]);
            //             }
            //         }
            //     }
            // }
        });

        return response()->json('Profissional cadastrado com sucesso!', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Profissional  $profissional
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Profissional $profissional)
    {
        $iten = $profissional;

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
     * @param  \App\Profissional  $profissional
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Profissional $profissional)
    {
        DB::transaction(function () use ($request, $profissional) {
            $profissional = Profissional::updateOrCreate(
                [
                    'id' => $request['id'],
                ],
                [
                    'pessoafisica' => 1,
                    'empresa_id'   => $request['empresa_id'],
                    'pessoa_id'    => Pessoa::updateOrCreate(
                        [
                            // 'id' => ($request['pessoa']['id'] != '') ? $request['pessoa']['id'] : null,
                            'id' => $request['pessoa_id'],
                        ],
                        [
                            // 'empresa_id'  => $request['pessoa']['empresa_id'],
                            'nome'        => $request['pessoa']['nome'],
                            'nascimento'  => $request['pessoa']['nascimento'],
                            'rgie'        => $request['pessoa']['rgie'],
                            'observacoes' => $request['pessoa']['observacoes'],
                            'perfil'      => $request['pessoa']['perfil'],
                            'status'      => $request['pessoa']['status'],
                        ]
                    )->id,
                    'sexo'                   => $request['sexo'],
                    'setor_id'               => $request['setor_id'],
                    'cargo_id'               => $request['cargo_id'],
                    'pis'                    => $request['pis'],
                    'numerocarteiratrabalho' => $request['numerocarteiratrabalho'],
                    'numerocnh'              => $request['numerocnh'],
                    'categoriacnh'           => $request['categoriacnh'],
                    'validadecnh'            => $request['validadecnh'],
                    'numerotituloeleitor'    => $request['numerotituloeleitor'],
                    'zonatituloeleitor'      => $request['zonatituloeleitor'],
                    'meiativa'               => $request['meiativa'],
                    'dataverificacaomei'     => $request['dataverificacaomei'],
                    'dadoscontratuais_id'    => Dadoscontratual::updateOrCreate([
                        'tiposalario'             => $request['dadoscontratuais']['tiposalario'],
                        'salario'                 => $request['dadoscontratuais']['salario'],
                        'cargahoraria'            => $request['dadoscontratuais']['cargahoraria'],
                        'insalubridade'           => $request['dadoscontratuais']['insalubridade'],
                        'percentualinsalubridade' => $request['dadoscontratuais']['percentualinsalubridade'],
                        'admissao'                => $request['dadoscontratuais']['admissao'],
                        'demissao'                => $request['dadoscontratuais']['demissao'],
                    ])->id,
                ]
            );
            if ($request['formacoes']) {
                foreach ($request['formacoes'] as $key => $formacao) {
                    $profissional_formacao = ProfissionalFormacao::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'formacao_id'     => $formacao['id'],
                    ]);
                }
            }
            if ($request['beneficios']) {
                foreach ($request['beneficios'] as $key => $beneficio) {
                    $profissional_beneficio = ProfissionalBeneficio::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'beneficio_id'    => $beneficio['id']
                    ]);
                }
            }
            if ($request['convenios']) {
                foreach ($request['convenios'] as $key => $convenio) {
                    $profissional_convenio = ProfissionalConvenio::firstOrCreate([
                        'profissional_id' => $profissional->id,
                        'convenio_id'    => $convenio['id']
                    ]);
                }
            }
            if ($request['dadosBancario']) {
                foreach ($request['dadosBancario'] as $key => $dadosbancario) {
                    $dados_bancario = Dadosbancario::firstOrCreate([
                        'empresa_id'  => $request['empresa_id'],
                        'banco_id'    => $dadosbancario['banco_id'],
                        'agencia'     => $dadosbancario['agencia'],
                        'conta'       => $dadosbancario['conta'],
                        'digito'      => $dadosbancario['digito'],
                        'tipoconta'   => $dadosbancario['tipoconta'],
                        'pessoa_id'   => $profissional->pessoa_id,
                    ]);
                }
            }
            if ($request['pessoa']['enderecos']) {
                foreach ($request['pessoa']['enderecos'] as $key => $endereco) {
                    $pessoa_endereco = PessoaEndereco::firstOrCreate([
                        'pessoa_id'   => $profissional->pessoa_id,
                        'endereco_id' => Endereco::firstOrCreate(
                            [
                                'cep'         => $endereco['cep'],
                                'cidade_id'   => $endereco['cidade_id'],
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

            if ($request['pessoa']['telefones']) {
                foreach ($request['pessoa']['telefones'] as $key => $telefone) {
                    $pessoa_telefone = PessoaTelefone::firstOrCreate([
                        'pessoa_id'   => $profissional->pessoa_id,
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

            if ($request['pessoa']['emails']) {
                foreach ($request['pessoa']['emails'] as $key => $email) {
                    $pessoa_email = PessoaEmail::firstOrCreate([
                        'pessoa_id' => $profissional->pessoa_id,
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

            // if ($request['pessoa']['user']) {
            //     if ($request['pessoa']['user']['email'] !== '') {
            //         $user = new User();

            //         if ($request['pessoa']['user']['password'] !== '') {
            //             $user = User::where('email', $request['pessoa']['user']['email'])->first();
            //             if ($user) {
            //                 // return 'Teste 1';
            //                 $user->update([
            //                     'empresa_id' =>        $request['empresa_id'],
            //                     'cpfcnpj'    =>        $request['pessoa']['user']['cpfcnpj'],
            //                     'password'   => bcrypt($request['pessoa']['user']['password']),
            //                     'pessoa_id'  =>        $profissional->pessoa_id,
            //                 ]);
            //             } else {
            //                 $user = User::where('cpfcnpj', $request['pessoa']['user']['cpfcnpj'])->first();
            //                 if ($user) {
            //                     // return 'Teste 2';
            //                     $user->update([
            //                         'email'      =>        $request['pessoa']['user']['email'],
            //                         'empresa_id' =>        $request['empresa_id'],
            //                         'password'   => bcrypt($request['pessoa']['user']['password']),
            //                         'pessoa_id'  =>        $profissional->pessoa_id,
            //                     ]);
            //                 } else {
            //                     // return 'Teste 3';
            //                     $user = User::create([
            //                         'empresa_id' =>        $request['empresa_id'],
            //                         'email'      =>        $request['pessoa']['user']['email'],
            //                         'cpfcnpj'    =>        $request['pessoa']['user']['cpfcnpj'],
            //                         'password'   => bcrypt($request['pessoa']['user']['password']),
            //                         'pessoa_id'  =>        $profissional->pessoa_id,
            //                     ]);
            //                 }
            //             }
            //         }

            //         if ($request['pessoa']['user']['acessos']) {
            //             if ($user) {
            //                 foreach ($request['pessoa']['user']['acessos'] as $key => $acesso) {
            //                     $user_acesso = UserAcesso::firstOrCreate([
            //                         'user_id'   => $user->id,
            //                         'acesso_id' => $acesso,
            //                     ]);
            //                 }
            //             }
            //         }
            //     }
            // }
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Profissional  $profissional
     * @return \Illuminate\Http\Response
     */
    public function destroy(Profissional $profissional)
    {
        $profissional->ativo = false;
        $profissional->save();
    }
}
