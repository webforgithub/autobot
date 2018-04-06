<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBalanceMasterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable('balance_master')) {
            Schema::create('balance_master', function(Blueprint $table) {
                $table->increments('id');
                $table->integer('bh_exchanges_id')->nullable()->index('bh_exchanges_id3');
                $table->integer('user_id')->nullable()->index('user_id');
                $table->string('symbol', 90)->nullable()->index('symbol');
                $table->float('available_balance', 10, 0)->nullable();
                $table->float('allocated_balance', 10, 0)->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['bh_exchanges_id', 'symbol', 'user_id'], 'exchanges_symbol_user_id');
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
        //
        Schema::drop('balance_master');
    }
}