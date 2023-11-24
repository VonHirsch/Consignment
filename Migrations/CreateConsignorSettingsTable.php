<?php
/**
 * Table Migration
 * @package 4.8.21
**/

namespace Modules\Consignment\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConsignorSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( ! Schema::hasTable( 'consignor_settings' ) ) {
            Schema::createIfMissing( 'consignor_settings', function ( Blueprint $table ) {
                $table->bigIncrements( 'id' );
                $table->string( 'email' )->nullable();
                $table->string( 'share_email' )->nullable();
                $table->string( 'phone' )->nullable();
                $table->string( 'share_phone' )->nullable();
                $table->string( 'paypal_email' )->nullable();
                $table->string( 'street' )->nullable();
                $table->string( 'city' )->nullable();
                $table->string( 'state' )->nullable();
                $table->string( 'zip' )->nullable();
                $table->string( 'payout_preference' )->nullable();
                $table->text( 'notes' )->nullable();
                $table->string( 'reference' )->nullable();
                $table->integer( 'author' );
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
        Schema::dropIfExists( 'consignor_settings' );
    }
}
