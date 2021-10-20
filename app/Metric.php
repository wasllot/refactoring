<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'metrics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'total_views', 'total_sessions', 'total_product_views', 'total_product_sessions', 'total_views_outs', 'total_sessions_outs','products_out_stock', 'products_stock', 'total_products', 'percent_product_stock', 'percent_product_outs', 'analytics_date'
    ];

    public $incrementing = true;
}
