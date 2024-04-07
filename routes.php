<?php

use SoftWorksPy\AppAuth\Middlewares\AuthenticationControl;
use SoftWorksPy\AppConfig\Middlewares\AuthorizationControl;

Route::group(['prefix' => 'api/softworkspy/appauth'], function () {
    Route::middleware([AuthorizationControl::class])->group(function () {
        Route::post('user/login', 'SoftWorksPy\AppAuth\Api\Users@login');
        Route::post('user/signin', 'SoftWorksPy\AppAuth\Api\Users@login');
        Route::post('user/logout', 'SoftWorksPy\AppAuth\Api\Users@logout');
        Route::post('user/register', 'SoftWorksPy\AppAuth\Api\Users@register');
        Route::post('user/restorePassword', 'SoftWorksPy\AppAuth\Api\Users@restorePassword');
        Route::post('user/register-guest', 'SoftWorksPy\AppAuth\Api\Users@registerGuest');
        Route::post('user/convert-guest', 'SoftWorksPy\AppAuth\Api\Users@convertGuest');

        Route::post('admin/login', 'SoftWorksPy\AppAuth\Api\Admin@login');
        Route::post('admin/signin', 'SoftWorksPy\AppAuth\Api\Admin@login');
        Route::post('admin/register', 'SoftWorksPy\AppAuth\Api\Admin@register');
    });

    Route::middleware([AuthenticationControl::class])->group(function () {
        Route::put('user/update', 'SoftWorksPy\AppAuth\Api\Users@update');
        Route::post('user/delete', 'SoftWorksPy\AppAuth\Api\Users@deleteUser');
        Route::post('user/change-password', 'SoftWorksPy\AppAuth\Api\Users@changePassword');
        Route::post('user/upload-avatar', 'SoftWorksPy\AppAuth\Api\Users@uploadAvatar');
    });

    Route::middleware([AuthenticationControl::class . ':admin'])->group(function () {
        Route::put('admin/update', 'SoftWorksPy\AppAuth\Api\Admin@update');
        Route::post('admin/change-password', 'SoftWorksPy\AppAuth\Api\Admin@changePassword');
        Route::post('admin/upload-avatar', 'SoftWorksPy\AppAuth\Api\Admin@uploadAvatar');
    });
});
