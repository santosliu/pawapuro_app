<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('keywords', function ($table) {
            $table->string('game')->after('id')->nullable();
            $table->string('type')->after('game')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('keywords', function ($table) {
            $table->dropColumn('deleted_at');
            $table->dropColumn('game');
            $table->dropColumn('type');
        });
    }
}
