<?php namespace SoftWorksPy\AppAuth\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateUsersDeleted extends Migration
{
    public function up()
    {
        Schema::create('users_deleted', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('activation_code')->nullable()->index();
            $table->string('persist_code')->nullable();
            $table->string('reset_password_code')->nullable()->index();
            $table->text('permissions')->nullable();
            $table->boolean('is_activated')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('username')->nullable()->index();
            $table->string('surname')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->boolean('is_superuser')->default(false);
            $table->string('created_ip_address')->nullable();
            $table->string('last_ip_address')->nullable();
            $table->string('mobile')->nullable();
            $table->text('datos_factura')->nullable();
            $table->string('fcm_token')->nullable();
            $table->text('rom')->nullable();
            $table->boolean('two_factor_authentication')->default(0);
            $table->text('birthday')->nullable();
            $table->text('documentType')->nullable();
            $table->text('document')->nullable();
            $table->decimal('calificacion')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('users_deleted');
    }
}