<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
|--------------------------------------------------------------------------
| industry_type Model 
|--------------------------------------------------------------------------
|
| This Model is used to retrieve and store information from our industry_type database table
|
*/
class industry_type extends Model
{
    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'industry_type';
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
