<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| user_competitors Model 
|--------------------------------------------------------------------------
|
| This Model is used to retrieve and store information from our user_competitors database table
|
*/
class user_competitors extends Model
{
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'user_competitors';
     /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
