/*
|--------------------------
| Chat Client
|--------------------------
*/
var Chat = {
  // degiskenler
  user_id: null,

  // konsol mesajlarını yenile
  console_refresh: function(){
    $("#messages .timeago").timeago();
  },

  init: function(){
    this.user_id = $("#user_id").val();
    this.console_refresh();

    // Enter'a basıldığında mesaj gönder
    $("#message").on("keydown", function(event){
      if (event.keyCode == 13)
        Chat.send();
    });
  },

  // Konsola mesaj yazdır
  log: function(message, sender){
    if (!sender) sender = "Server";

    // konsol mesajını oluştur
    var tpl = '';
    tpl += '<div class="message">';
    tpl += '<div class="sender"><strong><mark>'+sender+'</mark></strong></div>';
    tpl += '<div class="content">'+message+'</div>';
    tpl += '<div class="date text-muted"><small class="timeago" title="'+(moment().toISOString())+'"></small></div>';
    tpl += '</div>';

    $("#messages").append( tpl );
    this.console_refresh();
  },

  // Servera mesaj gönder
  send: function(message){
    // mesaj parametre olarak girilmemişse input'dan al
    if (!message) {
      message = $("#message").val();
      Chat.clearInput();
    }
    
    // mesaj yoksa iptal et
    if (!message) return;

    // mesajı gönder
    message = {
      topic: 'message',
      data: {
        sender: Chat.user_id,
        content: message
      }
    }
    Websocket.send( message );
  },

  // input'daki mesajı temizle
  clearInput: function(){
    $("#message").val("");
  },

  // servera bağlanıldığında
  onConnect: function(event){
    // giriş yapılan user bilgilerini servera gönder
    var message = {
      'topic': 'login',
      'data': {
        'user_id': Chat.user_id
      },
    }
    Websocket.send( message );

    Chat.log("Bağlanıldı !");
  },

  // server ile bağlantı koptuğunda
  onDisconnect: function(event){
    Chat.log("Bağlantı kesildi !");
  },

  // serverdan mesaj geldiğinde
  onMessage: function(event){
    var message = JSON.parse(event.data);
    
    switch(message.topic) {
      // kullanıcıdan mesaj var
      case 'message':
        var sender = UserList.getUserById( message.data.sender );
        console.log(sender);

        if (!sender) sender = message.data.sender;
        else sender = sender.name;

        this.log( message.data.content, sender );
        break;

      // kullanıcı listesini al
      case 'users':
        UserList.init( message.data.users );
        break;

      default:
        break;
    }
  },

  // bağlantıda hata oluştuğunda
  onError: function(event){
    console.log(event);
  },
};

/*
|--------------------------
| Websocket
|--------------------------
*/
var Websocket = {
  url: "ws://localhost:8080/",
  ws: null,
  
  init: function(){
    Chat.log("Bağlanılıyor...");
    this.ws = new WebSocket(this.url);
    this.ws.onopen = function(evt) { Chat.onConnect(evt) };
    this.ws.onclose = function(evt) { Chat.onDisconnect(evt) };
    this.ws.onmessage = function(evt) { Chat.onMessage(evt) };
    this.ws.onerror = function(evt) { Chat.onError(evt) };
  },

  send: function(data){
    if (this.ws != null)
      this.ws.send( JSON.stringify(data) );
  },
};

/*
|--------------------------
| User List
|--------------------------
*/
var UserList = {
  users: null,

  getUserById: function(id){
    for (var i = 0; i < this.users.length; i++) {
      if (this.users[i].id == id)
        return this.users[i];
    };
    return null;
  },

  init: function(users){
    this.users = users; // users arrayini güncelle
    $("#active-users ul").html(""); // listeyi temizle

    this.users.forEach(function(user){ // userları listeye ekle
      var tpl = "";
      tpl += '<li class="list-group-item" data-user-id="'+user.id+'" id="user-'+user.id+'">';
      tpl += '<span>'+user.name+'</span>';
      tpl += '</li>';

      // online durumu
      $tpl = $(tpl);
      if (user.status)
        $tpl.addClass("online");

      $("#active-users ul").append( $tpl );
    });
  },

  update: function(users){
    this.init(users);
  },

  // delete: function(user){
  //   for (var i = 0; i < this.users.length; i++) {
  //     if (this.users[i].id == user.id)
  //       this.users.splice( i, 1 );
  //   };
  // },
}

/*
|--------------------------
| Init
|--------------------------
*/
$(function(){
  Websocket.init();
  Chat.init();
});