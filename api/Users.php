<?php

namespace SoftWorksPy\AppAuth\Api;

use DB;
use Log;
use Url;
use Auth;
use Lang;
use Mail;
use Input;
use Event;
use Validator;
use Exception;
use Carbon\Carbon;
use Winter\Storm\Auth\AuthException;
use SoftWorksPy\AppAuth\Models\Settings;
use SoftWorksPy\AppAuth\Classes\EventNames;
use Winter\User\Models\User as UserModel;
use SoftWorksPy\AppConfig\Api\SimpleResponse;
use Winter\User\Models\Settings as UserSettings;

class Users extends Controller
{
    /**
     * Login an user
     */
    public function login(\SoftWorksPy\AppAuth\Classes\JWT $token)
    {
        if (!Settings::get('enable_user_login', true)) {
            return SimpleResponse::create('El login de usuarios no está habilitado.', 403);
        }

        $data = request()->all();
        $rules = [];

        $rules['login'] = $this->loginAttribute() == UserSettings::LOGIN_USERNAME
            ? 'required|between:2,255'
            : 'required|email|between:6,255';

        $rules['password'] = 'required|between:4,255';

        $validation = Validator::make($data, $rules);

        if ($validation->fails()) {
            return SimpleResponse::create($validation->messages()->first(), 403);
        }

        try {
            $credentials = [
                'login'    => array_get($data, 'login'),
                'password' => array_get($data, 'password')
            ];

            Event::fire('winter.user.beforeAuthenticate', [$this, $credentials]);

            $user = Auth::authenticate($credentials, false);

            if ($user->isBanned()) {
                Auth::logout();
                return SimpleResponse::create(Lang::get('winter.user::lang.account.banned'), 403);
            }

            Event::fire('softworkspy.appauth.user_logged_in', $user);

            //Suplantación de identidad
            $ip = isset($_SERVER['HTTP_CLIENT_IP'])
                ? $_SERVER['HTTP_CLIENT_IP']
                : (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                    ? $_SERVER['HTTP_X_FORWARDED_FOR']
                    : $_SERVER['REMOTE_ADDR']);

            $user->authentication = $token->signIn([
                'user_id' => $user->id,
                'user_type' => 'user',
                'ip' => $ip,
                'user_agent' => request()->header('User-Agent'),
                'app_authorization' => request()->header('Authorization'),
            ], Settings::get('exp'));

            $user->session_lifetime = Settings::get('exp');

            //Sesiones concurrentes
            $expira = Settings::get('exp'); //parametro de tiempo de expiracion
            $date = Carbon::now();
            $date->modify('-' . $expira . ' minute');

            //Borra todas las sessiones expiradas
            Db::table('softworkspy_appauth_sessions')
                ->where('last_activity', '<', $date)
                ->where('user_id', $user->id)
                ->delete();

            $modo = Settings::get('login_type');
            // $modo = 1 Permisivo: Autoriza nueva session y cierra la anterior
            if ($modo == 1) {
                Db::table('softworkspy_appauth_sessions')->where('user_id', $user->id)->delete();
                Db::table('softworkspy_appauth_sessions')->insert(
                    [
                        'user_id' => $user->id,
                        'token' => $user->authentication,
                        'last_activity' => now()
                    ]
                );
            }

            // $modo = 2 Restrictivo: Impide nueva session hasta que se cierre la anterior
            if ($modo == 2) {
                $r = Db::table('softworkspy_appauth_sessions')->where('user_id', $user->id)->count();
                if ($r > 0) {
                    return SimpleResponse::create('Hay sesión activa con su usuario, cierre sesión para poder continuar o espere que expire la sesión anterior para empezar una nueva', 403);
                } else {
                    $m = DB::table('softworkspy_appauth_sessions')->insert([
                        'user_id' => $user->id,
                        'token' => $user->authentication,
                        'last_activity' => now()
                    ]);
                }
            }

            return response()->json($user);

        } catch (AuthException $e) {
            Log::error('API login error: ' . $e->getMessage());
            return SimpleResponse::create('Datos de acceso incorrectos', 403);
        }
    }

    public function logout(\SoftWorksPy\AppAuth\Classes\JWT $token)
    {
        $datas = request()->all();
        Db::table('softworkspy_appauth_sessions')
            ->where('user_id', $datas['user_id'])
            ->where('token', $token->getToken())
            ->delete();
    }

    public function register()
    {
        if (!Settings::get('enable_user_register', false)) {
            return SimpleResponse::create(Lang::get('winter.user::lang.account.registration_disabled'), 403);
        }

        try {
            $data = request()->all();

            Event::fire(EventNames::BEFORE_USER_REGISTER, [&$data]);

            $rules = [
                'email'    => 'required|email|between:6,255',
                'password' => 'required|between:4,255|confirmed'
            ];

            if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
                $rules['username'] = 'required|between:2,255';
            }

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                return SimpleResponse::create($validation->messages()->first(), 403);
            }

            $user = $this->registerUser($data);

            if (Input::hasFile('avatar')) {
                $user->avatar = Input::file('avatar');
            }

            Event::fire(EventNames::USER_REGISTER, [$user, $data]);

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('SoftWorksPy.AppAuth register new user error: ' . $e->getMessage());
            return SimpleResponse::create($e->getMessage(), 403);
        }
    }

    public function update()
    {
        if (!Settings::get('enable_user_update', false)) {
            return SimpleResponse::create('La actualización de datos para usuarios no está habilitada', 403);
        }

        try {
            $user = Auth::getUser();

            if (Input::hasFile('avatar')) {
                $user->avatar = Input::file('avatar');
            }

            $user->fill(request()->all());
            $user->save();

            return response()->json(['success' => true, 'message' => 'Datos actualizados correctamente']);

        } catch (\Winter\Storm\Exception\ValidationException $e) {
            
            return SimpleResponse::create($e->getFields(), 403);

        } catch (Exception $e) {
            
            Log::error('SoftWorksPy.AppAuth update user datas error: ' . $e->getMessage());
            return SimpleResponse::create('Los datos enviados no pudieron ser validados.', 403);
        }
    }

    public function changePassword()
    {
        if (!Settings::get('enable_user_password', false)) {
            return SimpleResponse::create('El cambio de clave de usuarios no está habilitado.', 403);
        }

        $validator = Validator::make(request()->all(), [
            'current'     => 'required',
            'password'     => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        if ($validator->fails()) {
            return SimpleResponse::create($validator->messages()->first(), 403);
        }

        $user = Auth::getUser();
        $loginName = $user->getLoginName();

        try {
            Auth::validate([
                $loginName => $user->{$loginName},
                'password' => request()->get('current'),
            ]);
        } catch (AuthException $th) {
            return SimpleResponse::create('Clave actual incorrecta', 403);
        }

        $user->password = request()->get('password');
        $user->password_confirmation = request()->get('password_confirmation');
        $user->save();

        return response()->json(['success' => true, 'message' => 'Clave de acceso modificada']);
    }

    public function uploadAvatar()
    {
        return parent::saveAvatar(Auth::getUser());
    }

    public function registerGuest()
    {
        if (!Settings::get('enable_guest_register', false)) {
            return SimpleResponse::create(Lang::get('winter.user::lang.account.registration_disabled'), 403);
        }

        try {
            $data = request()->all();

            Event::fire(EventNames::BEFORE_GUEST_REGISTER, [&$data]);

            $rules = [
                'email'    => 'required|email|between:6,255',
            ];

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                return SimpleResponse::create($validation->messages()->first(), 403);
            }

            $user = Auth::registerGuest($data);

            Event::fire(EventNames::GUEST_REGISTER, [$user, $data]);

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('SoftWorksPy.AppAuth register guest error: ' . $e->getMessage());
            return SimpleResponse::create($e->getMessage(), 403);
        }
    }

    public function convertGuest()
    {
        if (!Settings::get('enable_guest_register', false)) {
            return SimpleResponse::create(Lang::get('winter.user::lang.account.registration_disabled'), 403);
        }

        try {
            $data = request()->all();

            Event::fire(EventNames::BEFORE_GUEST_CONVERT, [&$data]);

            $rules = [
                'email'    => 'required|email|between:6,255',
                'password' => 'required|between:4,255|confirmed',
            ];

            if ($this->loginAttribute() == UserSettings::LOGIN_USERNAME) {
                $rules['username'] = 'required|between:2,255';
            }

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                return SimpleResponse::create($validation->messages()->first(), 403);
            }

            $user = $this->registerUser($data);

            Event::fire(EventNames::GUEST_CONVERT, [$user, $data]);

            return response()->json($user);
        } catch (Exception $e) {
            Log::error('SoftWorksPy.AppAuth convert guest error: ' . $e->getMessage());
            return SimpleResponse::create($e->getMessage(), 403);
        }
    }

    /**
     * Returns the login model attribute.
     */
    protected function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    protected function registerUser($credentials)
    {
        $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
        $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
        $user = Auth::register($credentials, $automaticActivation);

        /*
             * Activation is by the user, send the email
             */
        if ($userActivation) {
            $this->sendActivationEmail($user);
        }

        return $user;
    }

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    protected function sendActivationEmail($user)
    {
        $code = implode('!', [$user->id, $user->getActivationCode()]);

        $link = $this->makeActivationUrl($code);

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('winter.user::mail.activate', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }

    /**
     * Returns a link used to activate the user account.
     * @return string
     */
    protected function makeActivationUrl($code)
    {
        $params = [
            'code' => $code
        ];

        $url = Url::to(Settings::get('user_activation_page'), $params);

        if (strpos($url, $code) === false) {
            $url .= '?activate=' . $code;
        }

        return $url;
    }

    /**
     * Trigger the password reset email
     */
    public function restorePassword()
    {
        if (!Settings::get('enable_user_restore_password', false)) {
            return SimpleResponse::create('La restauración de la clave del usuario no está habilitada', 403);
        }

        $rules = [
            'email' => 'required|email|between:6,255'
        ];

        $validation = Validator::make(post(), $rules);
        if ($validation->fails()) {
            return SimpleResponse::create($validation->messages()->first(), 403);
        }

        $user = UserModel::findByEmail(post('email'));
        if (!$user || $user->is_guest) {
            return SimpleResponse::create(Lang::get('winter.user::lang.account.invalid_user'), 403);
        }

        $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

        $link = $this->makeResetUrl($code);

        $data = [
            'name' => $user->name,
            'username' => $user->username,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('winter.user::mail.restore', $data, function ($message) use ($user) {
            $message->to($user->email, $user->full_name);
        });

        return response()->json(['success' => true, 'message' => 'Email enviado']);
    }

    public function deleteUser()
    {
        try {
        
            $datas = post();

            $validation = Validator::make($datas, [
                'password' => 'required',
            ]);
    
            if ($validation->fails()) {
                throw new \Exception(json_encode($validation->messages()), 1);
            }

            if (!Settings::get('enabled_user_deleted', false)) {

                throw new \Exception('Funcionalidad no disponible via API', 1);
            }

            $user = Auth::getUser();

            $user = Auth::authenticate([
                'login'    => $user->username,
                'password' => $datas['password']
            ], true);

            $user->forceDelete();
            
            return response()->json(['success' => true, 'message' => 'Usuario eliminado'], 200);
            
        } catch (AuthException $e) {

            Log::error('API login error: ' . $e->getMessage());
            return response()->json(['error' => false, 'message' => "Datos de acceso incorrectos"], 400);

        } catch (Exception $e) {

            return response()->json(['error' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Returns a link used to reset the user account.
     * @return string
     */
    protected function makeResetUrl($code)
    {
        $params = [
            'code' => $code
        ];

        $url = Url::to(Settings::get('user_restore_password_page'), $params);

        if (strpos($url, $code) === false) {
            $url .= '?reset=' . $code;
        }

        return $url;
    }
}
