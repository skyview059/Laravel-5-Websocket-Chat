<?php namespace Chat\Server;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Chat\Client;
use Chat\User;
use Log;

class ChatServer implements MessageComponentInterface
{
  protected $clients;

  public function __construct() {
    $this->clients = new \SplObjectStorage;
  }

  /*
  |--------------------------------------------------------------------------
  | Yeni kullanıcı bağlandığında
  |--------------------------------------------------------------------------
  */
  public function onOpen(ConnectionInterface $socket) {
    // Kullanıcıyı listeye kaydet
    $client = new Client($socket);
    $this->clients->attach($client);

    echo "New connection! ({$socket->resourceId})\n";
  }

  /*
  |--------------------------------------------------------------------------
  | Kullanıcıdan mesaj geldiğinde
  |--------------------------------------------------------------------------
  */
  public function onMessage(ConnectionInterface $socket, $data) {
    // mesaj gönderen client'i bul
    $sender = $this->findClientByConnection($socket);

    // mesaj içeriğini al
    $msg = json_decode($data);

    if (!$msg) return; // mesaj boşsa çık
    if (!isset($msg->topic)) return; // mesaj başlığı yoksa çık

    // mesaj başlığına göre işlem yapılacak
    switch ($msg->topic) {
      
      // login bildirimi
      case 'login':
        echo "Login mesajı geldi.\n";
        try {
          $user = User::findOrFail($msg->data->user_id);
          $sender->user = $user;
          echo "User {$user->name} bağlandı.\n";
        } catch (Exception $e) {
          echo "User not found\n";
        }
        break;

      // kullanıcıdan gelen mesaj
      case 'message':
        echo "Mesaj geldi.\n";
        // login olmuş kullanıcılara gelen mesajı ilet
        foreach ($this->clients as $client) {
          if ($client->isLoggedIn())
            $client->socket->send( $data );
        }
        break;
      
      default:
        break;
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Kullanıcı çıktığında
  |--------------------------------------------------------------------------
  */
  public function onClose(ConnectionInterface $socket) {
    // Bağlantı kesildiğinden kullanıcıyı listeden çıkarabiliriz.
    $user = $this->findClientByConnection($socket);
    if ($user) {
      $this->clients->detach($user);
      echo "Connection {$socket->resourceId} has disconnected\n";
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Hata oluştuğunda
  |--------------------------------------------------------------------------
  */
  public function onError(ConnectionInterface $socket, \Exception $e) {
    Log::error( $e );
    echo "An error has occurred: {$e->getMessage()}\n";
    $socket->close();
  }

  /*
  |--------------------------------------------------------------------------
  | Socket'e göre User'ı bul 
  |--------------------------------------------------------------------------
  */
  public function findClientByConnection(ConnectionInterface $socket)
  {
    foreach ($this->clients as $client)
      if ($client->socket == $socket)
        return $client;
    return null;
  }
}