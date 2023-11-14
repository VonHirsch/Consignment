<?php
/**
 * Table Migration
 * @package 4.8.21
**/

namespace Modules\Consignment\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFlightsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('airline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.  Performed when module is uninstalled
     *
     * @return void
     */
    public function down()
    {
        // drop tables here
    }
}
