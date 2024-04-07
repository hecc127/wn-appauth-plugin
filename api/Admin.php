<?php

namespace SoftWorksPy\AppAuth\Api;

use Log;
use Validator;
use Exception;
use BackendAuth;
use Winter\Storm\Auth\AuthException;
use SoftWorksPy\AppAuth\Models\Settings;
use SoftWorksPy\AppAuth\Classes\EventNames;
use SoftWorksPy\AppConfig\Api\SimpleResponse;

class Admin extends Controller
{
    /**
     * Login an user
     */
    public function login(\SoftWorksPy\AppAuth\Classes\JWT $token)
    {
        if (!Settings::get('enable_admin_login', true)) {
            return SimpleResponse::create('El login de administradores no está habilitado.', 403);
        }

        $validator = Validator::make(request()->all(), [
            'login'     => 'required',
            'password'     => 'required',
        ]);

        if ($validator->fails()) {
            return SimpleResponse::create($validator->messages()->first(), 403);
        }

        try {
            $user = BackendAuth::authenticate([
                'login' => request()->login,
                'password' => request()->password,
            ], false);

            $user->authentication = $token->signIn([
                'user_id' => $user->id,
                'user_type' => 'admin',
                'app_authorization' => request()->header('Authorization'),
            ], Settings::get('exp'));

            return response()->json($user);
        } catch (AuthException $e) {
            Log::error('API login error: ' . $e->getMessage());
            return SimpleResponse::create('Datos de acceso incorrectos', 403);
        }
    }

    public function register()
    {
        if (!Settings::get('enable_admin_register', false)) {
            return SimpleResponse::create('El registro de administradores no está habilitado.', 403);
        }

        try {
            $request = request()->all();
            $data = Event::fire(EventNames::BEFORE_ADMIN_REGISTER, [$request]);
            $data = $data ? collect($data)->collapse() : $request;

            $user = BackendAuth::register($data);

            Event::fire(EventNames::ADMIN_REGISTER, [$user, $data]);

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('SoftWorksPy.AppAuth register new admin error: ' . $e->getMessage());
            return SimpleResponse::create('Los datos enviados no pudieron ser validados.', 403);
        }
    }

    public function update()
    {
        if (!Settings::get('enable_admin_update', false)) {
            return SimpleResponse::create('La actualización de datos para administradores no está habilitado.', 403);
        }

        try {
            $user = BackendAuth::getUser();
            $user->fill(request()->all());
            $user->save();
        } catch (\Winter\Storm\Exception\ValidationException $e) {
            return SimpleResponse::create($e->getFields(), 403);
        } catch (Exception $e) {
            Log::error('SoftWorksPy.AppAuth update admin datas error: ' . $e->getMessage());
            return SimpleResponse::create('Los datos enviados no pudieron ser validados.', 403);
        }
    }

    public function changePassword()
    {
        if (!Settings::get('enable_admin_password', false)) {
            return SimpleResponse::create('El cambio de clave de administradores no está habilitado.', 403);
        }

        $validator = Validator::make(request()->all(), [
            'current'     => 'required',
            'new'     => 'required|confirmed',
            'new_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return SimpleResponse::create($validator->messages()->first(), 403);
        }

        $user = BackendAuth::getUser();
        $loginName = $user->getLoginName();

        if (!BackendAuth::validate([
            $loginName => $user->{$loginName},
            'password' => request()->get('current'),
        ])) {
            return SimpleResponse::create('Clave actual incorrecta', 403);
        }

        $user->password = request()->get('new');
        $user->password_confirmation = request()->get('new_confirmation');
        $user->save();

        return response()->json(['success' => true, 'message' => 'Clave de acceso modificada']);
    }

    public function uploadAvatar()
    {
        return parent::saveAvatar(BackendAuth::getUser());
    }
}
