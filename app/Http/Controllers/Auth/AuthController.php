<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Email;
use App\Models\Pessoa;
use App\Models\Conselho;
use App\Models\Prestador;
use Carbon\Carbon;
use App\Models\Tipopessoa;
use App\Models\PessoaEmail;
use App\Models\PrestadorFormacao;
use App\Mail\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            // 'email'       => 'string|email',
            'password'    => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $user = User::with(['acessos', 'pessoa.prestador', 'pessoa.cliente.empresa', 'pessoa.profissional.empresa', 'pessoa.responsavel.empresa'])
            ->where('email', $request['email'])
            ->orWhere('cpfcnpj', $request['email'])
            ->first();

        // return Storage::disk('local')->get('perfil/728/58c21109b8fa38e7fdf18d56a6fd58ef.png');



        // return storage_path('app');

        // return Storage::get(storage_path('app') . '/' . $user->pessoa->perfil);

        // if (Storage::exists($user->pessoa->perfil)) {
        //     return "Tem";
        // } else {
        //     return "Não tem";
        // }

        // return "Stop";
        // return Storage::exists($user->pessoa->perfil);

        if (!$user) {
            return response()->json([
                'message' => 'Email não cadastrado!'
            ], 404);
        }

        if (!password_verify($request['password'], $user['password'])) {
            return response()->json([
                'message' => 'E-mail e/ou Senha incorretos.'
            ], 401);
        }

        $tokenResult = $user->createToken('Personal Access Token');
        $token       = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();

        if (Storage::disk('local')->exists($user->pessoa->perfil)) {
            $user->pessoa->perfil = Storage::disk('local')->get($user->pessoa->perfil);
        } else {
            $user->pessoa->perfil = null;
        }

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'user' => $user
        ]);

        // password_verify = 145   ms
        // Auth::attempt   = 148,6 ms
        // Hash::check     = 145,8 ms
        ////////////////////////////////////////////////////////////////////
        // com             = 157,6 ms 4.5 KB
        // sem             = 152,8 ms 1031 B

        // $request->validate([
        //     // 'cpfcnpj'  => 'string',
        //     'email'       => 'string|email',
        //     'password'    => 'required|string',
        //     'remember_me' => 'boolean'
        // ]);
        // $credentials = request(['email', 'password']);
        // if (!Auth::attempt($credentials)) {
        //     return response()->json([
        //         'message' => 'E-mail e/ou Senha incorretos.'
        //     ], 401);
        // }
        // $user = $request->user();
        // $user->acessos;
        // $user->pessoa;
        // // if ($user->pessoa['tipo'] == 'Prestador') {
        // $user->pessoa->prestador;
        // // }
        // // if ($user->pessoa['tipo'] == 'Cliente') {
        // $user->pessoa->cliente;
        // // }
        // // if ($user->pessoa['tipo'] == 'Profissional') {
        // $user->pessoa->profissional;
        // // }
        // $tokenResult = $user->createToken('Personal Access Token');
        // $token       = $tokenResult->token;
        // if ($request->remember_me) {
        //     $token->expires_at = Carbon::now()->addWeeks(1);
        // }
        // $token->save();
        // return response()->json([
        //     'access_token' => $tokenResult->accessToken,
        //     'token_type'   => 'Bearer',
        //     'expires_at'   => Carbon::parse(
        //         $tokenResult->token->expires_at
        //     )->toDateTimeString(),
        //     'user' => $user
        // ]);
    }

    public function register(Request $request)
    {
        $cpfcnpj = User::firstWhere('cpfcnpj', $request['cpfcnpj']);

        $email = User::firstWhere('email', $request['user']['email']);

        $user = null;

        if ($cpfcnpj) {
            $user = $cpfcnpj;
        } elseif ($email) {
            $user = $email;
        }

        if ($user) {
            $prestador = Prestador::firstWhere('pessoa_id', $user->pessoa->id);
            if ($prestador) {
                return response()->json('Você já possui cadastro!', 400)->header('Content-Type', 'text/plain');
            } else {
                DB::transaction(function () use ($request, $user) {
                    $pessoa_email = PessoaEmail::firstOrCreate([
                        'pessoa_id' => $user->pessoa_id,
                        'email_id'  => Email::firstOrCreate(
                            [
                                'email' => $user->email,
                            ]
                        )->id,
                        'tipo'      => 'Pessoal',
                    ]);

                    $conselho = Conselho::create(
                        [
                            'instituicao' => $request['conselho']['instituicao'],
                            'numero'      => $request['conselho']['numero'],
                            'pessoa_id'   => $user->pessoa_id
                        ]
                    );

                    $formacao = PrestadorFormacao::create(
                        [
                            'prestador_id' => Prestador::create(
                                [
                                    'pessoa_id' => $user->pessoa_id,
                                    'sexo'      => $request['prestador']['sexo']
                                ]
                            )->id,
                            'formacao_id'  => $request['prestador']['formacao_id']
                        ]
                    );
                });
            }
        } else {
            DB::transaction(function () use ($request) {
                $user = User::create(
                    [
                        'cpfcnpj'    => $request['cpfcnpj'],
                        'email'      => $request['user']['email'],
                        'password'   =>  bcrypt($request['user']['password']),
                        'pessoa_id'  => Pessoa::create(
                            [
                                'nome'       => $request['nome'],
                                // 'nascimento' => $request['nascimento'],
                                'cpfcnpj'    => $request['cpfcnpj'],
                                'status'     => $request['status']
                            ]
                        )->id
                    ]
                );
                $tipopessoa = Tipopessoa::create([
                    'tipo'      => 'Prestador',
                    'pessoa_id' => $user->pessoa_id,
                    'ativo'     => 1
                ]);
                $pessoa_email = PessoaEmail::firstOrCreate([
                    'pessoa_id' => $user->pessoa_id,
                    'email_id'  => Email::firstOrCreate(
                        [
                            'email' => $user->email,
                        ]
                    )->id,
                    'tipo'      => 'Pessoal',
                ]);

                $conselho = Conselho::create(
                    [
                        'instituicao' => $request['conselho']['instituicao'],
                        'numero'      => $request['conselho']['numero'],
                        'pessoa_id'   => $user->pessoa_id
                    ]
                );

                $formacao = PrestadorFormacao::create(
                    [
                        'prestador_id' => Prestador::create(
                            [
                                'pessoa_id' => $user->pessoa_id,
                                'sexo'      => $request['prestador']['sexo']
                            ]
                        )->id,
                        'formacao_id'  => $request['prestador']['formacao_id']
                    ]
                );
            });
        }
    }

    public function reset(Request $request)
    {
        $user = User::firstWhere(
            ['email' => $request->email]
        );

        if ($user == null) {
            return response()->json([
                'message' => 'Email não cadastrado!'
            ], 404);
        }

        // $senha = Str::random(8);

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $senha = '';
        for ($i = 0; $i < 8; $i++) {
            $senha .= $characters[rand(0, $charactersLength - 1)];
        }

        $user->password = bcrypt($senha);
        $user->save();
        Mail::send(new ResetPassword($user, $senha));
        // return $senha;
    }

    public function change(Request $request)
    {
        $request->validate([
            'email'       => 'string|email',
            'password'    => 'required|string',
            'newPassword' => 'required|string'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'E-mail e/ou Senha incorretos.'
            ], 401);
        }
        $user        = $request->user();
        $user->password = bcrypt($request->newPassword);
        $user->save();
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Desconectado com Sucesso!'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->pessoa;
        return response()->json($user);
        // return response()->json($request->user());
    }
}
