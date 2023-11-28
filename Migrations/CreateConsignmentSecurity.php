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

class CreateConsignmentSecurity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // -----------------------------
        // Add Permissions
        // -----------------------------

        // General consignment user permission
        $namespace = 'nexopos.consignment';
        $permission = Permission::namespace( $namespace );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew([ 'namespace' => $namespace ]);
            $permission->name = __( 'Consignment' );
            $permission->namespace = $namespace;
            $permission->description = __( 'Allow user access to basic consignment features.' );
            $permission->save();
        }

        // Consignment label printer permission
        $namespace = 'nexopos.consignment.print-labels';
        $permission = Permission::namespace( $namespace );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew([ 'namespace' => $namespace ]);
            $permission->name = __( 'Consignment Print Labels' );
            $permission->namespace = $namespace;
            $permission->description = __( 'Allow user to print labels for any consignor.' );
            $permission->save();
        }

        // Consignment payout permission
        $namespace = 'nexopos.consignment.manage-payouts';
        $permission = Permission::namespace( $namespace );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew([ 'namespace' => $namespace ]);
            $permission->name = __( 'Consignment Payouts' );
            $permission->namespace = $namespace;
            $permission->description = __( 'Allow user to manage payouts.' );
            $permission->save();
        }

        // -----------------------------
        // Add Roles
        // -----------------------------

        /**
         * consignment administrator role
         */
        $namespace = 'nexopos.consignment.administrator';
        $consignmentAdmin = Role::firstOrNew([ 'namespace' => $namespace ]);
        $consignmentAdmin->name = __( 'Consignment Administrator' );
        $consignmentAdmin->namespace = $namespace;
        $consignmentAdmin->locked = true;
        $consignmentAdmin->description = __( 'All Consignment Admin Functions.' );
        $consignmentAdmin->dashid = 'store';
        $consignmentAdmin->save();
        $consignmentAdmin->addPermissions([ 'read.dashboard' ]);
        $consignmentAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );

        /**
         * consignment label printer role
         */
        $namespace = 'nexopos.consignment.printer';
        $consignmentPrinter = Role::firstOrNew([ 'namespace' => $namespace ]);
        $consignmentPrinter->name = __( 'Consignment Printer' );
        $consignmentPrinter->namespace = $namespace;
        $consignmentPrinter->locked = true;
        $consignmentPrinter->description = __( 'Can print labels for any consignor.' );
        $consignmentPrinter->dashid = 'default';
        $consignmentPrinter->save();
        $consignmentPrinter->addPermissions([ 'read.dashboard' ]);
        $consignmentAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );

        /**
         * consignment payout admin role
         */
        $namespace = 'nexopos.consignment.payout';
        $consignmentPayout = Role::firstOrNew([ 'namespace' => $namespace ]);
        $consignmentPayout->name = __( 'Consignment Payout Admin' );
        $consignmentPayout->namespace = $namespace;
        $consignmentPayout->locked = true;
        $consignmentPayout->description = __( 'Can process payouts.' );
        $consignmentPayout->dashid = 'store';
        $consignmentPayout->save();
        $consignmentPayout->addPermissions([ 'read.dashboard' ]);
        $consignmentPayout->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );

        // -----------------------------
        // Add Permissions to Roles
        // -----------------------------

        // General consignment user permission
        $namespace = 'nexopos.consignment';
        $permission = Permission::namespace( $namespace );
        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.consignment.administrator' )->addPermissions( $permission );
        Role::namespace( 'user' )->addPermissions( $permission );

        // Consignment label printer permission
        $namespace = 'nexopos.consignment.print-labels';
        $permission = Permission::namespace( $namespace );
        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.consignment.administrator' )->addPermissions( $permission );
        Role::namespace( 'nexopos.consignment.printer' )->addPermissions( $permission );

        // Consignment payout permission
        $namespace = 'nexopos.consignment.manage-payouts';
        $permission = Permission::namespace( $namespace );
        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.consignment.payout' )->addPermissions( $permission );

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove Permissions
        if ( Schema::hasTable( 'nexopos_permissions' ) && Schema::hasTable( 'nexopos_roles' ) ) {
            collect([
                'nexopos.consignment',
                'nexopos.consignment.print-labels',
                'nexopos.consignment.payout',
            ])->each( function ( $identifier ) {
                $permission = Permission::where( 'namespace', $identifier
                )->first();
                if ($permission) {
                    $permission->removeFromRoles();
                    $permission->delete();
                }
            });
        }


        // Remove Roles
        $role = Role::where( 'namespace', 'nexopos.consignment.administrator' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

        $role = Role::where( 'namespace', 'nexopos.consignment.printer' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

        $role = Role::where( 'namespace', 'nexopos.consignment.payout' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

    }
}
