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

    // Mesaj input'una focus yap
    $("#message").focus();
  },

  // Konsola mesaj yazdır
  log: function(message, sender, date){
    if (!sender) sender = "Server";
    if (!date) date = moment();
    else date = moment(date);

    // konsol mesajını oluştur
    var tpl = '';
    tpl += '<div class="message">';
    tpl += '<div class="sender"><strong><mark>'+sender+'</mark></strong></div>';
    tpl += '<div class="content">'+message+'</div>';
    tpl += '<div class="date text-muted"><small class="timeago" title="'+(date.toISOString())+'"></small></div>';
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
      topic: 'new_message',
      data: {
        to_id: null,
        message: message
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

    // yeşil renk yak
    $("#active-users .groups li").addClass("online");
  },

  // server ile bağlantı koptuğunda
  onDisconnect: function(event){
    Chat.log("Bağlantı kesildi !");

    // kırmızı renk yak
    $("#active-users li").removeClass("online");
  },

  // serverdan mesaj geldiğinde
  onMessage: function(event){
    var message = JSON.parse(event.data);
    
    switch(message.topic) {
      // gelen mesajları yazdır
      case 'messages':
        for (var i = message.data.length - 1; i >= 0; i--) {
          // gönderen kullanıcı adını bul
          var from = UserList.getUserById( message.data[i].from_id );
          if (!from) from = message.data[i].from_id;
          else from = from.name;

          // konsola yazdır
          this.log( message.data[i].message, from, message.data[i].created_at );
        };

        // en alta scroll yap
        var messagesDiv = document.getElementById("messages");
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

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
    $("#active-users ul.users").html(""); // listeyi temizle

    this.users.forEach(function(user){ // userları listeye ekle
      var tpl = "";
      tpl += '<li class="list-group-item" data-user-id="'+user.id+'" id="user-'+user.id+'">';
      tpl += '<span>'+user.name+'</span>';
      tpl += '</li>';

      // online durumu
      $tpl = $(tpl);
      if (user.status)
        $tpl.addClass("online");

      $("#active-users ul.users").append( $tpl );
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
function resized () {
  $("#messages").height( $(window).height() - 250 );
}
$(function(){
  Websocket.init();
  Chat.init();
  resized();
  $(window).resize(function(){
    resized();
  });
});
