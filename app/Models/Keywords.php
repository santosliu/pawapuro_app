<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keywords extends Model
{
    // protected $table = 'keywords';
    use SoftDeletes;
    protected $dates = ['deleted_at'];
}
