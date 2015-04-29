<?php namespace Chat\Server;

use Symfony\Component\Console\Output\ConsoleOutput;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Chat\Client;
use Chat\User;
use Chat\Message;
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

    // Yeni kullanıcı listesini gönder
    $this->sendUsersList();
  }

  /*
  |--------------------------------------------------------------------------
  | Kullanıcıdan mesaj geldiğinde
  |--------------------------------------------------------------------------
  */
  public function onMessage(ConnectionInterface $socket, $data) {
    try {
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
            // user'ı bul
            $user = User::findOrFail($msg->data->user_id);
            $sender->user = $user;
            $sender->user->setOnline();
            $this->console("User {$user->name} bağlandı.");

            // bağlı kullanıcıların listesini gönder
            $this->sendUsersList();

            // genel mesaj geçmişini gönder
            $this->sendMessageLogTo( $sender );

          } catch (Exception $e) {
            $this->console("User bulunamadı", "error");
          }
          break;

        // kullanıcıdan gelen mesaj
        case 'new_message':
          
          // mesaj geçmişine kaydet
          $message = Message::create([
            'from_id' => $sender->user->id,
            'to_id'   => $msg->data->to_id,
            'message' => $msg->data->message
          ]);

          // login olmuş kullanıcılara gelen mesajı ilet
          if ($msg->data->to_id == null)
            foreach ($this->clients as $client) {
              if ($client->isLoggedIn())
                $client->send([
                  'topic' => 'messages',
                  'data'  => [$message]
                ]);
            }
          else
            $this->findClientByUserId( $msg->data->to_id )->send([
              'topic' => 'messages',
              'data'  => [$message]
            ]);

          break;
        
        default:
          break;
      }
    } catch (Exception $e) {
      $this->console($e->getMessage(), "error");
      Log::info($e);
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Kullanıcı çıktığında
  |--------------------------------------------------------------------------
  */
  public function onClose(ConnectionInterface $socket) {
    // Bağlantı kesildiğinden kullanıcıyı listeden çıkarabiliriz.
    $client = $this->findClientByConnection($socket);
    if ($client) {
      if ($client->isLoggedIn()) 
        $client->user->setOffline();

      $this->clients->detach($client);
      $this->console("Bağlantı {$socket->resourceId} çıkış yaptı.", "error");
    }

    // Yeni kullanıcı listesini gönder
    $this->sendUsersList();
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

  /*
  |--------------------------------------------------------------------------
  | Kullanıcı listelerini gönder
  |--------------------------------------------------------------------------
  */
  public function sendUsersList( $to = null )
  {
    $users = User::orderBy('status', 'desc')->orderBy('name', 'asc')->get();
    
    $message['topic'] = 'users';
    $message['data']['users'] = $users;

    if ($to)
      $to->send( $message );
    else{
      // herkese gonder
      foreach ($this->clients as $client)
        if ($client->isLoggedIn())
          $client->send( $message );
    }
  }

  /*
  |--------------------------------------------------------------------------
  | Belirtilen ID'li kullanıcılar arasındaki mesajları bul
  | $with_id alanı Null girilirse genel gönderilen mesajları bulur
  |--------------------------------------------------------------------------
  */
  public function sendMessageLogTo( $to, $with_id = null )
  {
    if ($with_id) {
      $ids = [$with_id, $to->user->id];
      $messages = Message::ofWith( $ids );
    }else
      $messages = Message::ofWith();

    $messages = $messages->latestFirst()->take(50)->get()->toArray();

    $message['topic'] = 'messages';
    $message['data'] = $messages;

    $to->send( $message );
  }

  /*
  |--------------------------------------------------------------------------
  | User ID'ye göre socket client bul
  |--------------------------------------------------------------------------
  */
  public function findClientByUserId( $user_id )
  {
    foreach ($this->clients as $client)
      if ($client->isLoggedIn() && $client->user->id == $user_id)
        return $client;
    return null;
  }
}