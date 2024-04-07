<?php namespace SoftWorksPy\AppAuth\Models;

use Model;

/**
 * Model
 */
class DeletedUser extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\SoftDelete;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $fillable = [
        'datas',
    ];

    public $jsonable = [
        'datas',
    ];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'softworkspy_appauth_deleted_users';

    /**
     * @var array Validation rules
     */
    public $rules = [];    
}
