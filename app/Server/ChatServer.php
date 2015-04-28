<?php namespace Chat\Server;

use Symfony\Component\Console\Output\ConsoleOutput;
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
  | Konsola renkli mesaj yazdır
  |--------------------------------------------------------------------------
  */
  public function console($message, $type = "info")
  {
    $output = new ConsoleOutput();
    $output->writeln("<{$type}>{$message}</{$type}>");
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

    $this->console("Yeni bağlantı! ({$socket->resourceId})");
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
        $this->console("Login mesajı geldi.", "comment");
        try {
          $user = User::findOrFail($msg->data->user_id);
          $sender->user = $user;
          $this->console("User {$user->name} bağlandı.");
        } catch (Exception $e) {
          $this->console("User bulunamadı", "error");
        }
        break;

      // kullanıcıdan gelen mesaj
      case 'message':
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
      $this->console("Bağlantı {$socket->resourceId} çıkış yaptı.", "error");
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Hata oluştuğunda
  |--------------------------------------------------------------------------
  */
  public function onError(ConnectionInterface $socket, \Exception $e) {
    Log::error( $e );
    $this->console("An error has occurred: {$e->getMessage()}", "error");
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