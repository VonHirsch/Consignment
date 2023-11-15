<?php
namespace Modules\Consignment\Crud;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Services\CrudEntry;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use Modules\Consignment\Models\Flight;

class FlightCrud extends CrudService
{
    /**
     * define the base table
     * @param string
     */
    protected $table      =   'flights';

    /**
     * default slug
     * @param string
     */
    protected $slug   =   'Consignment/flights';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   'consignment.flights';

    /**
     * Model Used
     * @param string
     */
    protected $model      =   Flight::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  true,
        'read'      =>  true,
        'update'    =>  true,
        'delete'    =>  true,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     * @param array
     */
    public $relations   =  [
            ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Export Columns defines the columns that
     * should be included on the exported csv file.
     */
    protected $exportColumns    =   []; // @getColumns will be used by default.

    /**
     * Pick
     * Restrict columns you retrieve from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick        =   [];

    /**
     * Define where statement
     * @var array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var array
     */
    protected $whereIn      =   [];

    /**
     * Fields which will be filled during post/put
     */
    
    /**
     * If few fields should only be filled
     * those should be listed here.
     */
    public $fillable = [
        'name',
        'airline'
    ];

    /**
     * If fields should be ignored during saving
     * those fields should be listed here
     */
    public $skippable   =   [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions     =   false;

    /**
     * Define Constructor
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'addActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud
     * instance
     * @return array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Flights List' ),
            'list_description'      =>  __( 'Display all flights.' ),
            'no_entry'              =>  __( 'No flights has been registered' ),
            'create_new'            =>  __( 'Add a new flight' ),
            'create_title'          =>  __( 'Create a new flight' ),
            'create_description'    =>  __( 'Register a new flight and save it.' ),
            'edit_title'            =>  __( 'Edit flight' ),
            'edit_description'      =>  __( 'Modify  Flight.' ),
            'back_to_list'          =>  __( 'Return to Flights' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @return boolean
    **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     * @param object/null
     * @return array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
//                            'type'  =>  'text',
//                            'name'  =>  'id',
//                            'label' =>  __( 'Id' ),
//                            'value' =>  $entry->id ?? '',
//                        ], [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'airline',
                            'label' =>  __( 'Airline' ),
                            'value' =>  $entry->airline ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'created_at',
//                            'label' =>  __( 'Created_at' ),
//                            'value' =>  $entry->created_at ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'updated_at',
//                            'label' =>  __( 'Updated_at' ),
//                            'value' =>  $entry->updated_at ?? '',
                        ],                     ]
                ]
            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Flight $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     * @param Request $request
     * @return void
     */
    public function beforePost( $request )
    {
        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     * @param Request $request
     * @param Flight $entry
     * @return void
     */
    public function afterPost( $request, Flight $entry )
    {
        return $request;
    }


    /**
     * get
     * @param string
     * @return mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
        }
    }

    /**
     * Before updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == 'consignment.flights' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
            **/
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }
        }
    }

    /**
     * Define Columns
     * @return array of columns configuration
     */
    public function getColumns() {
        return [
            'id'  =>  [
                'label'  =>  __( 'Id' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'airline'  =>  [
                'label'  =>  __( 'Airline' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'created_at'  =>  [
                'label'  =>  __( 'Created_at' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'updated_at'  =>  [
                'label'  =>  __( 'Updated_at' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
                    ];
    }

    /**
     * Define actions
     */
    public function addActions( CrudEntry $entry, $namespace )
    {
        /**
         * Declaring entry actions
         */
        $entry->addAction( 'edit', [
            'label'         =>      __( 'Edit' ),
            'namespace'     =>      'edit',
            'type'          =>      'GOTO',
            'url'           =>      ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
        ]);

        $entry->addAction( 'delete', [
            'label'     =>  __( 'Delete' ),
            'namespace' =>  'delete',
            'type'      =>  'DELETE',
            'url'       =>  ns()->url( '/api/nexopos/v4/crud/consignment.flights/' . $entry->id ),
            'confirm'   =>  [
                'message'  =>  __( 'Would you like to delete this ?' ),
            ]
        ]);

        return $entry;
    }


    /**
     * Bulk Delete Action
     * @param  object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */

        if ( $request->input( 'action' ) == 'delete_selected' ) {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Flight ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     * @return array of links
     */
    public function getLinks(): array
    {
        return  [
            'list'      =>  ns()->url( 'dashboard/' . 'Consignment/flights' ),
            'create'    =>  ns()->url( 'dashboard/' . 'Consignment/flights/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'Consignment/flights/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.flights' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.flights/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     * @return array of actions
    **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Groups' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * get exports
     * @return array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}
