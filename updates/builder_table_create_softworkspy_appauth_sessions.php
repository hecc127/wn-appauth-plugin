<?php namespace SoftWorksPy\AppAuth\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateSoftWorksPyAppAuthSessions extends Migration
{
    public function up()
    {
         Schema::create('softworkspy_appauth_sessions', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('user_id');
            $table->text('token');
            $table->timestamp('last_activity');
            
        });    
    }

    public function down()
    {
       Schema::dropIfExists('softworkspy_appauth_sessions');
    }
}