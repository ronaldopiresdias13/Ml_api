<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ChamadoWebSocketController;
use Illuminate\Console\Command;

class ChamadoWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chamado:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $loop = \React\EventLoop\Factory::create();
        $secure_websockets = new \React\Socket\Server('0.0.0.0:1122', $loop);
        $secure_websockets = new \React\Socket\SecureServer($secure_websockets, $loop, [
            'local_cert' => '/etc/ssl/private/b714b41bb0bd2d2e.crt',
            'local_pk' => '/etc/ssl/private/server.key',
            //  'local_cert' => env('SSL_CRT', '/etc/ssl/private/9dfd8ac04f6852d4.crt'),
            //  'local_pk' => env('SSL_KEY', '/etc/ssl/private/server.key'),
            'verify_peer' => false
        ]);
        $websocket = new \Ratchet\WebSocket\WsServer(
            new ChamadoWebSocketController()
        );
        $websocket->enableKeepAlive($loop, 30);
        $app = new \Ratchet\Http\HttpServer(
            $websocket
        );
        $server = new \Ratchet\Server\IoServer($app, $secure_websockets, $loop);
        $server->run();

        //$loop_ = \React\EventLoop\Factory::create();
        //$socket_ = new \React\Socket\Server('0.0.0.0:1122', $loop_);
        //$app_ = new \Ratchet\Http\HttpServer(
        //    new \Ratchet\WebSocket\WsServer(
        //        new ChamadoWebSocketController()
        //    )
        //);
        //$server_ = new \Ratchet\Server\IoServer($app_, $socket_, $loop_);
        //$server_->run();
    }
}
