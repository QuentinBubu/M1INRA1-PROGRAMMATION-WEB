<?php

namespace App\Models;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

#[ApiResource]
class User extends Model
{

    protected $table = 'user';

    protected $primaryKey = 'user_id';

    const CREATED_AT = 'user_date_new';
    const UPDATED_AT = 'user_date_login';

    protected $fillable = [
        'user_login',
        'user_password',
        'user_compte_id',
        'user_mail',
    ];

    protected $hidden = [
        'user_password',
    ];
}
