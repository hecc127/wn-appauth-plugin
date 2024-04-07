<?php

namespace SoftWorksPy\AppAuth\Classes;

class EventNames
{
    const BEFORE_USER_REGISTER = 'softworkspy.appauth.before_user_register';
    const USER_REGISTER = 'softworkspy.appauth.user_register';

    const BEFORE_GUEST_REGISTER = 'softworkspy.appauth.before_guest_register';
    const GUEST_REGISTER = 'softworkspy.appauth.guest_register';
    const BEFORE_GUEST_CONVERT = 'softworkspy.appauth.before_guest_convert';
    const GUEST_CONVERT = 'softworkspy.appauth.guest_convert';

    const BEFORE_ADMIN_REGISTER = 'softworkspy.appauth.before_admin_register';
    const ADMIN_REGISTER = 'softworkspy.appauth.admin_register';
}
