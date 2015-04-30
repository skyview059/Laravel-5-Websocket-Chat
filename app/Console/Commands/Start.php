<?php namespace Chat\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Chat\Server\ChatServer;
use Chat\User;
use DB;

class Start extends Command {

  protected $name = 'chat:start';
  protected $description = 'Chat serverını çalıştır.';

  public function __construct()
  {
    parent::__construct();
  }

  public function fire()
  {
    // Query logları tutulmasın
    // Server uzun süre çalıştığında memory leak sorunu yaratır
    DB::connection()->enableQueryLog();

    // Tüm userları offline yap.
    User::where('status', 1)->update(['status'=>0]);

    // Websocket Serverını Çalıştır
    $server = IoServer::factory(
      new HttpServer(
        new WsServer(
          new ChatServer()
        )
      ),
      env("WS_PORT")
    );

    $server->run();
  }

  protected function getArguments()
  {
    return [];
  }

  protected function getOptions()
  {
    return [];
  }

}
