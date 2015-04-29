<?php namespace Chat;

use Illuminate\Database\Eloquent\Model;

class Message extends Model {

  protected $table = 'messages';
  protected $fillable = ['from_id', 'to_id', 'message'];

  public function from()
  {
    return $this->belongsTo('User', 'from_id');
  }

  public function to()
  {
    return $this->belongsTo('User', 'to_id');
  }

  public function scopeOfWith($query, $ids = null)
  {
    // genel gönderilen mesajları getir
    if ($ids == null)
      return $query->whereNull('to_id');
      
    // iki kişi arasındaki mesajları getir
    foreach ($ids as $id) {
      return $query->where(function($q) use ($id){
        $q->orWhere('to_id', $id)->orWhere('from_id', $id);
      });
    }
  }

  public function scopeLatestFirst($query)
  {
    return $query->orderBy('created_at', 'desc');
  }

}
