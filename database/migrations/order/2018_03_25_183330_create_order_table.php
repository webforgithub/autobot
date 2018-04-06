<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        //
        if (!Schema::hasTable('order_history')) {
            Schema::create('order_history', function(Blueprint $table) {
                $table->increments('id');
                $table->integer('configuremacdbot_id')->nullable()->index('configuremacdbot_id');
                $table->integer('user_id')->nullable()->index('user_id');
                $table->string('symbol', 90)->nullable()->index('symbol');
                $table->string('orderId', 100)->nullable()->index('orderId');
                $table->string('clientOrderId', 100)->nullable()->index('clientOrderId');
                $table->string('transactTime', 100)->nullable()->index('transactTime');
                $table->float('price', 10, 10)->nullable();
                $table->float('origQty', 10, 10)->nullable();
                $table->float('executedQty', 10, 10)->nullable();
                $table->string('status', 50)->nullable();
                $table->string('timeInForce', 100)->nullable();
                $table->string('type', 100)->nullable();
                $table->string('side', 100)->nullable();
                $table->bigInteger('timestamp')->nullable()->index('timestamp');
                $table->dateTime('datetime')->nullable()->index('datetime1');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        //
        if (Schema::hasTable('order_history')) {
            Schema::drop('order_history');
        }
    }
}