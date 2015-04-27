<?php namespace Chat\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Ratchet\Server\IoServer;
use Chat\Server\ChatServer;

class Start extends Command {

  /**
   * The console command name.
   *
   * @var string
   */
  protected $name = 'chat:start';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Chat serverını çalıştır.';

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
   * @return mixed
   */
  public function fire()
  {
    $server = IoServer::factory(
      new ChatServer(), 8080
    );

    $server->run();
  }

  /**
   * Get the console command arguments.
   *
   * @return array
   */
  protected function getArguments()
  {
    return [];
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    return [];
  }

}
