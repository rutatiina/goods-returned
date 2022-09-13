<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToRgGoodsReturnedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection('tenant')->hasColumn('rg_goods_returned', 'type')) 
        {
            Schema::connection('tenant')->table('rg_goods_returned', function (Blueprint $table) 
            {
                $table->string('type')->after('app_id')->default('goods-issued-return')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //do nothing
    }
}
