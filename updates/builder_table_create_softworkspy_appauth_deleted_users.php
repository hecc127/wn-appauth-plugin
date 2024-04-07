<?php namespace SoftWorksPy\AppAuth\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateSoftWorksPyAppAuthDeletedUsers extends Migration
{
    public function up()
    {
        Schema::create('softworkspy_appauth_deleted_users', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->text('datas')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('softworkspy_appauth_deleted_users');
    }
}
