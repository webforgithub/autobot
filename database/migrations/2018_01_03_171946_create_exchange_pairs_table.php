<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateExchangePairsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('exchange_pairs', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('exchange_id')->nullable();
            $table->integer('market_id')->nullable()->default(0);
            $table->string('exchange_pair', 90)->nullable();
			$table->string('baseAsset', 90)->nullable();
			$table->string('quoteAsset', 90)->nullable();
			$table->integer('quotePrecision')->nullable()->default(8);
			$table->integer('baseAssetPrecision')->nullable()->default(8);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['exchange_id', 'market_id', 'exchange_pair'], 'exchange_id2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('exchange_pairs');
    }
}