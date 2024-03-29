<?php namespace Chat;

use Ratchet\ConnectionInterface;

class Client
{
  public $socket;
  public $user;

  function __construct(ConnectionInterface $socket)
  {
    $this->socket = $socket;
  }

  public function isLoggedIn()
  {
    return isset($this->user);
  }

  public function send( $data )
  {
    if (is_array($data))
      $data = json_encode($data);
    $this->socket->send( $data );
  }
}