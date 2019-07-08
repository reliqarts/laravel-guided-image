<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use ReliqArts\GuidedImage\Services\ConfigProvider;

class CreateGuidedImageablesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $tableName = ConfigProvider::getImageablesTable();
        $imagesTableName = ConfigProvider::getImageTable();

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($imagesTableName) {
            $table->increments('id');
            $table->integer('image_id')->unsigned();
            $table->foreign('image_id')
                ->references('id')
                ->on($imagesTableName)
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
        $table = ConfigProvider::getImageablesTable();
        Schema::dropIfExists($table);
    }
}
