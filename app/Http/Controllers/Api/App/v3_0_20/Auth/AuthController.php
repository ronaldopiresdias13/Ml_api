<?php

namespace App\Http\Controllers\Api\App\v3_0_20\Auth;

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

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // $request->validate([
        //     'email'       => 'string|email',
        //     'password'    => 'required|string',
        //     'remember_me' => 'boolean'
        // ]);

        $user = User::firstWhere('email', $request['email']);

        if (!$user || !$user->pessoa->prestador) {
            return response()->json([
                'alert' => [
                    'title' => 'Ops!',
                    'text' => 'Email não cadastrado!'
                ]
            ], 400)
                ->header('Content-Type', 'application/json');
        }

        // $credentials = request(['email', 'password']);
        // if (!Auth::attempt($credentials)) {
        //     return response()->json([
        //         'message' => 'E-mail e/ou Senha incorretos.'
        //     ], 401);
        // }

        if (!password_verify($request['password'], $user['password'])) {
            // return response()->json([
            //     'message' => 'E-mail e/ou Senha incorretos.'
            // ], 404);

            return response()->json([
                'alert' => [
                    'title' => 'Ops!',
                    'text' => 'E-mail e/ou Senha incorretos.'
                ]
            ], 400)
                ->header('Content-Type', 'application/json');
        }

        // if (!Hash::check($request['password'], $user['password'])) {
        //     return response()->json([
        //         'message' => 'E-mail e/ou Senha incorretos.'
        //     ], 401);
        // }

        $tokenResult = $user->createToken('Personal Access Token');
        $token       = $tokenResult->token;
        // if ($request->remember_me) {
        //     $token->expires_at = Carbon::now()->addWeeks(1);
        // }
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    public function reset(Request $request)
    {
        $user = User::firstWhere(
            ['email' => $request->email]
        );

        if ($user == null) {
            return response()->json([
                'alert' => [
                    'title' => 'Ops!',
                    'text' => 'E-mail e/ou Senha incorretos.'
                ]
            ], 202)
                ->header('Content-Type', 'application/json');
        }

        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $senha = '';
        for ($i = 0; $i < 8; $i++) {
            $senha .= $characters[rand(0, $charactersLength - 1)];
        }

        $user->password = bcrypt($senha);
        $user->save();
        Mail::send(new ResetPassword($user, $senha));

        return response()->json([
            'alert' => [
                'title' => 'Parabéns!',
                'text' => 'Se informou os dados corretos, você receberá um email contendo uma nova senha para acessar o aplicativo!'
            ]
        ], 200)
            ->header('Content-Type', 'application/json');
    }

    public function change(Request $request)
    {
        // $credentials = request(['email', 'password']);
        // if (!Auth::attempt($credentials)) {
        //     return response()->json([
        //         'message' => 'E-mail e/ou Senha incorretos.'
        //     ], 401);
        // }
        $user = $request->user();
        // return $user;

        // return $request['password'];

        if (!password_verify($request['password'], $user['password'])) {
            return response()->json([
                'message' => 'Senha atual inválida!'
            ], 0);
        }

        // $request->validate([
        //     'email'       => 'string|email',
        //     'password'    => 'required|string',
        //     'newPassword' => 'required|string'
        // ]);
        // $credentials = request(['email', 'password']);
        // if (!Auth::attempt($credentials)) {
        //     return response()->json([
        //         'message' => 'E-mail e/ou Senha incorretos.'
        //     ], 401);
        // }
        // $user        = $request->user();
        $user->password = bcrypt($request->newPassword);
        $user->save();
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
                // return response()->json('Você já possui cadastro!', 400)->header('Content-Type', 'text/plain');
                return response()->json([
                    'alert' => [
                        'title' => 'Ops!',
                        'text' => 'Você já possui cadastro!!'
                    ]
                ], 200)
                    ->header('Content-Type', 'application/json');
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

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }
}
