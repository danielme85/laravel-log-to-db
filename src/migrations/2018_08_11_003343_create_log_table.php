<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::connection(config('logtodb.connection'))->hasTable(config('logtodb.collection')) === false) {
            Schema::connection(config('logtodb.connection'))->create(config('logtodb.collection'), function (Blueprint $table) {
                $table->increments('id');
                $table->text('message')->nullable();
                $table->string('channel')->nullable();
                $table->integer('level')->default(0);
                $table->string('level_name', 20);
                $table->integer('unix_time');
                $table->string('datetime')->nullable();
                $table->longText('context')->nullable();
                $table->text('extra')->nullable();
                $table->timestamps();
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
        Schema::connection(config('logtodb.connection'))->dropIfExists(config('logtodb.collection'));
    }
}
