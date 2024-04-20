<?php namespace SoftWorksPy\AppAuth;

use System\Classes\PluginBase;
use SoftWorksPy\AppAuth\Models\DeletedUser;
use Winter\User\Models\User as UserModel;
use SoftWorksPy\AppAuth\Middlewares\AuthenticationControl;

class Plugin extends PluginBase
{
    public $require = [
        'Winter.User',
        'SoftWorksPy.AppConfig',
    ];

    public function register()
    {
        $this->app->singleton(\SoftWorksPy\AppAuth\Classes\JWT::class, function () {
            $key = env('JWT_KEY');
            if (empty($key)) throw new \Exception('You need to set a JWT key.');
            $request = app(\Illuminate\Http\Request::class);
            return new \SoftWorksPy\AppAuth\Classes\JWT($key, ['HS256'], $request->bearerToken());
        });

        // register apis middleware
        $this->app->singleton(AuthenticationControl::class, function () {
            return new AuthenticationControl;
        });

        $this->app->bind(
            \SoftWorksPy\Foundation\RestApi\Interfaces\AuthenticationControl::class,
            Middlewares\AuthenticationControl::class,
        );
    }
    
    public function boot()
    {
        $this->_extendUserModel();
    }

    public function _extendUserModel()
    {
        UserModel::extend(function($model) {

            $model->bindEvent('model.beforeDelete', function () use ($model) {

                $userdb = \DB::table('users')->where('id', $model->id)->first();

                DeletedUser::create(['datas' => json_decode(json_encode($userdb),true)]);
            });
        });
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'AppAuth Settings',
                'description' => 'Configure AppAuth plugin.',
                'icon'        => 'icon-sign-in',
                'class'       => Models\Settings::class,
                'keywords'    => 'api auth softworkspy',
                'permissions' => ['softworkspy.appauth.access_settings']
            ]
        ];
    }
}
