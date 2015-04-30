/*
|--------------------------
| Chat Client
|--------------------------
*/
var Chat = {
  // degiskenler
  user_id: null,        // login olunan user id
  active_user_id: null, // konuşma penceresi açık olan user id

  // ----------------------------
  Console: {
    // konsolu yenile
    refresh: function(){
      $("#messages .timeago").timeago(); // tarihleri düzelt
    },

    // Konsola mesaj yazdır
    log: function(message, sender, date){
      if (!sender) sender = "Server";
      if (!date) date = moment();
      else date = moment(date);

      // konsol mesajını oluştur
      var tpl = '';
      tpl += '<div class="message"><div class="message-wrapper">';
      tpl += '<div class="sender">'+sender+'</div>';
      tpl += '<div class="content">'+message+'</div>';
      tpl += '<div class="date text-muted"><small class="timeago" title="'+(date.toISOString())+'"></small></div>';
      tpl += '</div></div>';

      $("#messages").append( tpl );
      Chat.Console.refresh();
    },
    
    // Konsolu temizle
    clear: function(){
      $("#messages").html("");
    },

    // En alta scroll yap
    scroll: function(){
      var messagesDiv = document.getElementById("messages");
      messagesDiv.scrollTop = messagesDiv.scrollHeight;
    },

    // input'daki mesajı temizle
    clearInput: function(){
      $("#message").val("");
    },

    addMessage: function(data){
      // konuşma penceresi açık değilse
      if (data.from_id != Chat.active_user_id && data.to_id != Chat.active_user_id) {
        // sağ listeden mesaj sayısını güncelle
        $listItem = $("#user-"+data.from_id).find(".badge");
        var count = parseInt($listItem.text() || 0);
        $listItem.html( ++count );

        // ekrana yazdırmadan çık
        return;
      }

      // gönderen kullanıcı adını bul
      var from = UserList.getUserById( data.from_id );
      if (!from) from = data.from_id;
      else from = from.name;

      // konsola yazdır
      Chat.Console.log( data.message, from, data.created_at );
    },
  },
  // ----------------------------

  init: function(){
    this.user_id = $("#user_id").val();
    this.Console.refresh();

    // Enter'a basıldığında mesaj gönder
    $("#message").on("keydown", function(event){
      if (event.keyCode == 13)
        Chat.send();
    });

    // Mesaj input'una focus yap
    $("#message").focus();

    // Kullanıcıya tıklandığında mesaj geçmişini sunucudan iste
    $("#active-users").on("click", "li", function(){
      var id = $(this).data("user-id");
      
      Chat.Console.clear(); // ekranı temizle
      Chat.active_user_id = id; // aktif user'ı güncelle
      Chat.getMessages( id ); // konuşma geçmişini al

      // pencere başlığını güncelle
      var title = $(this).find(".name").text();
      $("#chat-name").html( title );

      // okunmamış mesaj sayısını sıfırla
      $("#user-"+id).find(".badge").html("");
    });
  },

  // Serverdan mesaj geçmişini iste
  getMessages: function(with_id){
    var message = {
      'topic': 'request',
      'data': {
        'with_id': with_id
      },
    };
    Websocket.send( message );
  },

  // Servera mesaj gönder
  send: function(message){
    // mesaj parametre olarak girilmemişse input'dan al
    if (!message) {
      message = $("#message").val();
      Chat.Console.clearInput();
    }
    
    // mesaj yoksa iptal et
    if (!message) return;

    // mesajı gönder
    message = {
      topic: 'new_message',
      data: {
        to_id: Chat.active_user_id,
        message: message
      }
    };
    Websocket.send( message );
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

    Chat.Console.log("Bağlanıldı !");

    // yeşil renk yak
    $("#active-users .groups li").addClass("online");
  },

  // server ile bağlantı koptuğunda
  onDisconnect: function(event){
    Chat.Console.log("Bağlantı kesildi !");

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
          Chat.Console.addMessage(message.data[i]);
        };

        // en alta scroll yap
        Chat.Console.scroll();
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
  url: WS_URL,
  ws: null,
  
  init: function(){
    Chat.Console.log("Bağlanılıyor...");
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
      tpl += '<span class="name">'+user.name+'</span>';
      tpl += '<span class="badge"></span>';
      tpl += '</li>';

      // online durumu
      $tpl = $(tpl);
      if (parseInt(user.status) === 1)
        $tpl.addClass("online");

      $("#active-users ul.users").append( $tpl );
    });
  },

  update: function(users){
    this.init(users);
  },
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
  Chat.init();
  Websocket.init();

  resized();
  $(window).resize(function(){
    resized();
  });
});
