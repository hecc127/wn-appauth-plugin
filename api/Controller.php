<?php

namespace SoftWorksPy\AppAuth\Api;

use Input;
use SoftWorksPy\AppConfig\Api\SimpleResponse;

abstract class Controller extends \Illuminate\Routing\Controller
{

    abstract function login(\SoftWorksPy\AppAuth\Classes\JWT $token);

    abstract function register();

    abstract function update();

    abstract function changePassword();

    abstract function uploadAvatar();

    protected function saveAvatar($user)
    {
        if (!Input::hasFile('file')) {
            if (isset($user->avatar->path)) {

                $user->avatar()->delete();
                return SimpleResponse::create('Imagen eliminada', 200);
                
            } else {

                return SimpleResponse::create('No se recivió ningún archivo', 403);
            }
        }

        $user->avatar = Input::file('file');
        $user->save();

        return response()->json($user->avatar);
    }
}
