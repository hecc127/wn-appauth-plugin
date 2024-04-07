<?php namespace SoftWorksPy\AppAuth\Updates;

use DB;
use Seeder;
use Schema;
use SoftWorksPy\AppAuth\Models\DeletedUser;

class MigrationUsersDeleted extends Seeder
{
    public function run()
    {
        $usersdb = DB::table('users_deleted')->get();
        
        foreach ($usersdb as $userdb) {
            
            DeletedUser::create(['datas' => json_decode(json_encode($userdb),true)]);
        }
        
        Schema::dropIfExists('users_deleted');
    }
}