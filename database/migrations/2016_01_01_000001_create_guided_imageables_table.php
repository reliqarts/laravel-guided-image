<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ReliqArts\GuidedImage\Contracts\ConfigProvider;

class CreateGuidedImageablesTable extends Migration
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * CreateGuidedImagesTable constructor.
     *
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        $tableName = $this->configProvider->getImageablesTableName();
        $imagesTableName = $this->configProvider->getImagesTableName();

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
        $table = $this->configProvider->getImageablesTableName();
        Schema::dropIfExists($table);
    }
}
