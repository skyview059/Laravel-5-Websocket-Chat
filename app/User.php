<?php namespace Chat;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword;

	protected $table = 'users';
	protected $fillable = ['name', 'email', 'password', 'status'];
	protected $hidden = ['password', 'remember_token'];

  public function setOnline()
  {
    $this->status = 1;
    $this->save();
  }

  public function setOffline()
  {
    $this->status = 0;
    $this->save();
  }

  public function getIsOnlineAttribute()
  {
    return (bool) $this->status;
  }

  public function getIsOfflineAttribute()
  {
    return ! $this->is_online;
  }

}
