<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetricsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('total_views');
            $table->bigInteger('total_sessions');
            $table->bigInteger('total_product_views');
            $table->bigInteger('total_product_sessions');
            $table->bigInteger('total_views_outs');
            $table->bigInteger('total_sessions_outs');
            $table->bigInteger('products_out_stock');
            $table->bigInteger('products_stock');
            $table->bigInteger('total_products');
            $table->bigInteger('percent_product_stock');
            $table->bigInteger('percent_product_outs');
            $table->date('analytics_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('metrics');
    }
}
