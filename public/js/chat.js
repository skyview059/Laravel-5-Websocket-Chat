/*
|--------------------------
| Chat Client
|--------------------------
*/
var chat = {
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
        chat.send();
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
      chat.clearInput();
    }
    
    // mesaj yoksa iptal et
    if (!message) return;

    // mesajı gönder
    message = {
      topic: 'message',
      data: {
        sender: chat.user_id,
        content: message
      }
    }
    websocket.send( message );
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
        'user_id': chat.user_id
      },
    }
    websocket.send( message );

    chat.log("Bağlanıldı !");
  },

  // server ile bağlantı koptuğunda
  onDisconnect: function(event){
    chat.log("Bağlantı kesildi !");
  },

  // serverdan mesaj geldiğinde
  onMessage: function(event){
    var message = JSON.parse(event.data);
    
    switch(message.topic) {
      // kullanıcıdan mesaj var
      case 'message':
        this.log( message.data.content, message.data.sender );
        break;

      // giriş reddedildi mesajı
      case 'denied':
        this.log( message.data.content );

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
var websocket = {
  url: "ws://localhost:8080/",
  ws: null,
  
  init: function(){
    chat.log("Bağlanılıyor...");
    websocket.ws = new WebSocket(websocket.url);
    websocket.ws.onopen = function(evt) { chat.onConnect(evt) };
    websocket.ws.onclose = function(evt) { chat.onDisconnect(evt) };
    websocket.ws.onmessage = function(evt) { chat.onMessage(evt) };
    websocket.ws.onerror = function(evt) { chat.onError(evt) };
  },

  send: function(data){
    if (websocket.ws != null)
      websocket.ws.send( JSON.stringify(data) );
  }
};

/*
|--------------------------
| Init
|--------------------------
*/
$(function(){
  websocket.init();
  chat.init();
});