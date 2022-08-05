<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmailTemp extends Model {
    const UPDATED_AT = null;
    public $table = 'user_email_temp';
    use HasFactory;
}
