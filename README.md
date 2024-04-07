# Instalación

Para instalar el plugin se requiere agregar el siguiente código en el `composer.json` de un plugin o del WinterCMS.

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@bitbucket.org:SoftWorksPySA/softworkspywinter.git"
    },
    {
        "type": "vcs",
        "url": "git@bitbucket.org:SoftWorksPySA/wn-remoteconfig-plugin.git"
    },
    {
        "type": "vcs",
        "url": "git@bitbucket.org:SoftWorksPySA/wn-appauth-plugin.git"
    }
],
"require": {
    "softworkspy/wn-appauth-plugin": "^2.0.0"
}
```

Luego de debe ejecutar el comando `composer update && php artisan winter:up` para descargar e instalar el plugin y todas sus dependencias.
