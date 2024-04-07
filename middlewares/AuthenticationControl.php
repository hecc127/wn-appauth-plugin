<?php

namespace SoftWorksPy\AppAuth\Middlewares;

use DB;
use Auth;
use Closure;
use Exception;
use Carbon\Carbon;
use SoftWorksPy\AppAuth\Classes\JWT;
use SoftWorksPy\AppAuth\Models\Settings;
use SoftWorksPy\AppConfig\Models\Application;
use SoftWorksPy\AppConfig\Api\SimpleResponse;

class AuthenticationControl
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $userType = 'user')
    {

        try {

            \Event::fire('api.beforeRoute', [], true);

            if ($request->hasHeader('Agent')) {
                $userAgent = $request->header('Agent');
            } else {
                $userAgent = $request->header('User-Agent');
            }

            list($appCode,) = explode('/', $userAgent );
            $application = Application::findByCode($appCode);

            $token = \App::make(JWT::class);
            $token->check();
            $datas = $token->getData();

            
            if (!Settings::get('enabled_identity_control', false)) {

                $identity = true;

            } else {

                $identity = false;
                
                //Suplantación de identidad
                $ip = isset($_SERVER['HTTP_CLIENT_IP'])
                    ? $_SERVER['HTTP_CLIENT_IP']
                    : (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                        ? $_SERVER['HTTP_X_FORWARDED_FOR']
                        : $_SERVER['REMOTE_ADDR']);
    
                if ($userAgent != ($datas->user_agent ?? null) || $ip != ($datas->ip ?? null)) {
                    $identity = false;
                }
            }

            if (!$identity) {
                throw new Exception('Falló la validacion de identidad');
            }

            //Sesiones concurrentes
            $expira = Settings::get('exp'); //parametro de tiempo de expiracion
            $date = Carbon::now();
            $date->modify('-' . $expira . ' minute');

            //Borra todas las sessiones expiradas
            Db::table('softworkspy_appauth_sessions')
                ->where('last_activity', '<', $date)
                ->where('user_id', $datas->user_id)
                ->delete();

            //Aumenta tiempo se session
            Db::table('softworkspy_appauth_sessions')
                ->where('user_id', $datas->user_id)
                ->where('token', $token->getToken())
                ->update(['last_activity' => now()]);

            $user = Auth::getUser();

            //Verifica si el token usado es el que el usuario tiene permitido usar
            $r = Db::table('softworkspy_appauth_sessions')
                ->where('token', $token->getToken())
                ->count();

            $modo = Settings::get('login_type');

            if ($modo == 1 && intval($r) == 0) {
                return SimpleResponse::create('Su sesión ya no es válida', 401);
            }

            if ($application) {
                $authorization = $application->authCodes()
                    ->where('code', 'like', $datas->app_authorization)
                    ->where('is_active', true)
                    ->count();

                if ($authorization) {
                    switch ($datas->user_type) {
                        case 'user':
                            if ($userType !== 'user') throw new Exception('El tipo de usuario no corresponde');
                            $user = \Winter\User\Models\User::find($datas->user_id);
                            \Auth::onceUsingId($user->id);
                            break;

                        case 'admin':
                            if ($userType !== 'admin') throw new Exception('El tipo de usuario no corresponde');
                            $user = \Backend\Models\User::find($datas->user_id);
                            \BackendAuth::onceUsingId($user->id);
                            break;
                    }
                }

                return $next($request);
            }

        } catch (Exception $e) {
            \Log::error('Error in authentication: ' . $e->getMessage());

            if ($e->getMessage() === 'Expired token') {
                return SimpleResponse::create('Sesión expirada', 403);
            } elseif ($e->getMessage() === 'Invalid token supplied.') {
                return SimpleResponse::create('Token inválido', 403);
            } else {
                return SimpleResponse::create($th->getMessage(), 405);
            }
        }

        return SimpleResponse::create('No está autenticado para esta petición', 403);
    }
}
