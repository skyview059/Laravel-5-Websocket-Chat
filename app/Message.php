<?php namespace Chat;

use Illuminate\Database\Eloquent\Model;
use Log;

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
    return $query
      ->orWhere(function($q) use ($ids){
        $q->where('to_id', $ids[0])->where('from_id', $ids[1]);
      })
      ->orWhere(function($q) use ($ids){
        $q->where('to_id', $ids[1])->where('from_id', $ids[0]);
      });
  }

  public function scopeLatestFirst($query)
  {
    return $query->orderBy('created_at', 'desc');
  }

}
