<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| industries Model 
|--------------------------------------------------------------------------
|
| This Model is used to retrieve and store information from our industries database table
|
*/
class industries extends Model
{
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'industries';
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
