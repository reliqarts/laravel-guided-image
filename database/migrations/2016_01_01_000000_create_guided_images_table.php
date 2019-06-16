<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\GuidedImage\Services\ConfigProvider;

class CreateGuidedImagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $tableName = ConfigProvider::getImageTable();

        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
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
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $tableName = ConfigProvider::getImageTable();

        Schema::dropIfExists($tableName);
    }
}
