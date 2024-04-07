<?php return [
    'plugin' => [
        'name' => 'API Auth',
        'description' => 'Crea endpoints para hacer login',
    ],
    'permissions' => [
        'access_settings' => 'Configurar el plugin AuthAPI',
    ],
    'settings' => [
        '_login_section' => 'Configuración de sesión',
        '_user_section' => 'Endpoints para usuarios',
        '_user_section_comment' => 'Endpoints para usuarios del plugin Winter.User',
        '_admin_section' => 'Endpoints para administradores',
        '_admin_section_comment' => 'Endpoints para usuarios administradores con acceso al backend.',
        'exp' => 'Expiración',
        'exp_comment' => 'Expiración del token en minutos. Para que no expire asignar 0 (cero).',
        'enable_user_login' => 'Inicio de sesión',
        'enable_user_login_comment' => 'Habilitar el inicio de sesión',
        'enable_user_register' => 'Registro',
        'enable_user_register_comment' => 'Habilitar el registro de nuevos usuarios',
        'user_activation_page' => 'Página de activación',
        'user_activation_page_comment' => 'La página a la que debe apuntar el link que se enviará al usuario para activar su cuenta (debe tener implementado el componente Perfil). No es requerido en caso de que el plugin User esté configurado para activar las cuentas de forma automática.',
        'enable_user_update' => 'Actualización',
        'enable_user_update_comment' => 'Habilitar la actualización de datos de usuarios',
        'enable_user_password' => 'Cambio de clave',
        'enable_user_password_comment' => 'Habilitar el cambio de clave de acceso',
        'enable_user_restore_password' => 'Restaurar Contraseña',
        'enable_user_restore_password_comment' => 'Habilitar el registro de restaurar contraseña',
        'user_restore_password_page' => 'Página de restauración de contraseña',
        'user_restore_password_page_comment' => 'La página a la que debe apuntar el link que se enviará al usuario para restaurar su contraseña.',
        'enabled_identity_control' => 'Verificar suplantación',
        'enabled_identity_control_comment' => 'Habilita la verificación ante consultas con suplantación de identidad.',
        'enable_guest_register' => 'Invitados',
        'enable_guest_register_comment' => 'Habilitar creación de usuarios sin contraseña',
        'enable_admin_login' => 'Inicio de sesión',
        'enable_admin_login_comment' => 'Habilita el inicio de sesión',
        'enable_admin_register' => 'Registro',
        'enable_admin_register_comment' => 'Habilita el registro de nuevos administradores.',
        'enable_admin_update' => 'Actualización',
        'enable_admin_update_comment' => 'Habilita la actualización de datos',
        'enable_admin_password' => 'Cambio de clave',
        'enable_admin_password_comment' => 'Habilitar el cambio de clave de acceso',
        'user_login_type' => 'Tipo de Login de usuarios',
        'user_login_type_comment' => 'Gestiona las sesiones simultaneas',
        'enabled_user_deleted' => 'Eliminar usuarios',
        'enabled_user_deleted_comment' => 'Habilitar la opción de eliminar usuarios. Los usuarios eliminados se guardarán en deleted_users',
    ],
    'fields' => [
        'switch' => [
            'on' => 'Sí',
            'off' => 'No',
        ],
    ],
];