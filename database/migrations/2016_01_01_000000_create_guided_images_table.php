<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ReliQArts\GuidedImage\Helpers\Config;

class CreateGuidedImagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $table = Config::getImageTable();
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50);
                $table->string('mime_type', 20);
                $table->string('extension', 10);
                $table->integer('size');
                $table->integer('height');
                $table->integer('width');
                $table->string('location');
                $table->string('full_path');
                $table->timestamps();
                $table->integer('creator_id')
                    ->unsigned()
                    ->nullable();
                $table->foreign('creator_id')
                    ->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $table = Config::getImageTable();
        Schema::dropIfExists($table);
    }
}
