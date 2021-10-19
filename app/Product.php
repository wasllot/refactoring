<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_data';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'product_sessions', 'product_views', 'product_url', 'product_said', 'product_stock', 'analytics_date'
    ];

    public $incrementing = true;
}
