<?php
/**
 * Table Migration
 * @package 4.8.21
**/

namespace Modules\Consignment\Migrations;

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConsignorPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::namespace( 'nexopos.consignment' );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew([ 'namespace' => 'nexopos.consignment' ]);
            $permission->name = __( 'Consignment' );
            $permission->namespace = 'nexopos.consignment';
            $permission->description = __( 'Allow user access to consignment features.' );
            $permission->save();
        }

        Role::namespace( 'user' )->addPermissions( $permission );
        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.store.administrator' )->addPermissions( $permission );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // drop tables here
    }
}
