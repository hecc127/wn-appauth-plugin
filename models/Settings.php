<?php

namespace SoftWorksPy\AppAuth\Models;

use Model;
use Cms\Classes\Page;

class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    // A unique code
    public $settingsCode = 'softworkspy_appauth_settings';

    // Reference to field configuration
    public $settingsFields = 'fields.yaml';

    public function getUserActivationPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function getUserRestorePasswordPageOptions()
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }
}
