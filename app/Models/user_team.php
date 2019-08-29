<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| user_team Model 
|--------------------------------------------------------------------------
|
| This Model is used to retrieve and store information from our user_team database table
|
*/
class user_team extends Model
{
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'user_team';
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
