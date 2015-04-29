<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MesajGecmisiniKaydet extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('messages', function($table){
		  $table->increments("id");
		  $table->integer("from_id")->unsigned();
		  $table->integer("to_id")->unsigned()->nullable();
		  $table->text("message");
		  $table->timestamps();

		  $table->index(['to_id', 'created_at']);
		  $table->index(['to_id', 'from_id', 'created_at']);
		  $table->index(['from_id', 'to_id', 'created_at']);

		  $table->foreign("from_id")->references("id")
		  	->on("users")->onDelete("cascade");
		  $table->foreign("to_id")->references("id")
		  	->on("users")->onDelete("cascade");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('messages', function($table){
		  $table->dropForeign("messages_from_id_foreign");
		  $table->dropForeign("messages_to_id_foreign");
		});
		Schema::dropIfExists('messages');
	}

}
