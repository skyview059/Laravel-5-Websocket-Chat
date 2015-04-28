<?php namespace Chat\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Chat\Server\ChatServer;

class Start extends Command {

  protected $name = 'chat:start';
  protected $description = 'Chat serverını çalıştır.';

  public function __construct()
  {
    parent::__construct();
  }

  public function fire()
  {
    // Websocket Serverını Çalıştır
    $server = IoServer::factory(
      new HttpServer(
        new WsServer(
          new ChatServer()
        )
      ),
      8080
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
