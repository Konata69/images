<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterImageAutoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('image_auto', function (Blueprint $table) {
            $table->integer('external_id')->nullable()->index();
            $table->boolean('migrated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('image_auto', function (Blueprint $table) {
            $table->dropColumn(['migrated', 'external_id']);
        });
    }
}
