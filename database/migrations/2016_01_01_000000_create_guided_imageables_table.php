<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ReliQArts\GuidedImage\Helpers\Config;

class CreateGuidedImageablesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $table = Config::getImageablesTable();
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('image_id')->unsigned();
            $table->foreign('image_id')
                ->references('id')
                ->on('images')
                ->onDelete('CASCADE');
            $table->integer('imageable_id');
            $table->string('imageable_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $table = Config::getImageablesTable();
        Schema::dropIfExists($table);
    }
}
