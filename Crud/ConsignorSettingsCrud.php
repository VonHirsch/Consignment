<?php
namespace Modules\Consignment\Crud;

use App\Services\Helper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Services\CrudEntry;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use Modules\Consignment\Models\ConsignorSettings;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;

class ConsignorSettingsCrud extends CrudService
{
    /**
     * define the base table
     * @param string
     */
    protected $table      =   'consignor_settings';

    /**
     * default slug
     * @param string
     */
    protected $slug   =   'consignment/consignorsettings';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   'consignment.consignor.settings';

    /**
     * Model Used
     * @param string
     */
    protected $model      =   ConsignorSettings::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  'nexopos.consignment',
        'read'      =>  'nexopos.consignment',
        'update'    =>  'nexopos.consignment',
        'delete'    =>  'nexopos.consignment',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     * @param array
     */
    public $relations   =  [
        //[ 'nexopos_products_unit_quantities as unit', 'nexopos_products.id', '=', 'unit.product_id' ],
        [ 'nexopos_users as user', 'consignor_settings.author', '=', 'user.id' ]
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
    public $pick        =   [
        'user' => [ 'username', 'username' ],
    ];

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
        'email',
        'share_email',
        'phone',
        'share_phone',
        'paypal_email',
        'street',
        'city',
        'state',
        'zip',
        'payout_preference',
        'author',     // TODO: test that my fix in CrudService works for ConsignorSettingsCrud
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
    protected $prependOptions     =   true;

    /**
     * Define Constructor
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'addActions' ], 10, 2 );

        $user = app()->make( Users::class );
        if ( $user->is([ 'user' ]) ) {
            // Filter consignment item list by author (user.id) when the user is part of the default 'user' group
            $this->listWhere = [
                'author' => Auth::id()
            ];
        }
    }

    /**
     * Return the label used for the crud
     * instance
     * @return array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'ConsignorSettings List' ),
            'list_description'      =>  __( 'Display all consignorsettings.' ),
            'no_entry'              =>  __( 'No consignorsettings has been registered' ),
            'create_new'            =>  __( 'Add a new consignorsetting' ),
            'create_title'          =>  __( 'Create a new consignorsetting' ),
            'create_description'    =>  __( 'Register a new consignorsetting and save it.' ),
            'edit_title'            =>  __( 'Edit consignorsetting' ),
            'edit_description'      =>  __( 'Modify  Consignorsetting.' ),
            'back_to_list'          =>  __( 'Return to ConsignorSettings' ),
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
                            'name'  =>  'payout_preference',
                            'label' =>  __( 'Payout Preference' ),
                            'description' => __( 'Select a payout Preference' ),
                            'value' =>  $entry->payout_preference ?? '',
                            'type' => 'select',
                            'validation' => 'required',
                            'options' => [
                                [
                                    'label' => __( 'Cash' ),
                                    'value' => 'cash',
                                ], [
                                    'label' => __( 'Paypal' ),
                                    'value' => 'paypal',
                                ], [
                                    'label' => __( 'Check' ),
                                    'value' => 'check',
                                ], [
                                    'label' => __( 'Donate to VCF' ),
                                    'value' => 'donation',
                                ],
                            ],
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'paypal_email',
                            'label' =>  __( 'Paypal Email' ),
                            'value' =>  $entry->paypal_email ?? '',
                            'description' => __( '^ Provide this if your payout preference is Paypal' ),
//                            'validation' => $entry->email === 'Paypal' ? 'required' : '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'email',
                            'label' =>  __( 'Email Address' ),
                            'description' => __( 'Best email to contact you during the event' ),
                            'value' =>  $entry->email ?? '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'phone',
                            'label' =>  __( 'Phone Number' ),
                            'description' => __( 'Best phone # to contact you during the event' ),
                            'value' =>  $entry->phone ?? '',
                        ],
                        [
                            'type'  =>  'switch',
                            'name'  =>  'share_email',
                            'label' =>  __( 'Share email with buyers?' ),
                            'description' => __( 'Share email with potential buyers?' ),
                            'value' =>  $entry->share_email ?? '',
                            'options' => Helper::kvToJsOptions([
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ]),
                        ],
                        [
                            'type'  =>  'switch',
                            'name'  =>  'share_phone',
                            'label' =>  __( 'Share phone # with buyers?' ),
                            'description' => __( 'Share phone # with potential buyers?' ),
                            'value' =>  $entry->share_phone ?? '',
                            'options' => Helper::kvToJsOptions([
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ]),
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'street',
                            'label' =>  __( 'Street Address' ),
                            'description' => __( 'If Payout by check, provide mailing address' ),
                            'value' =>  $entry->street ?? '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'city',
                            'label' =>  __( 'City' ),
                            'description' => __( 'If Payout by check, provide mailing address' ),
                            'value' =>  $entry->city ?? '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'state',
                            'label' =>  __( 'State' ),
                            'description' => __( 'If Payout by check, provide mailing address' ),
                            'value' =>  $entry->state ?? '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'zip',
                            'description' => __( 'If Payout by check, provide mailing address' ),
                            'label' =>  __( 'Zip' ),
                            'value' =>  $entry->zip ?? '',
                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'notes',
                            'label' =>  __( 'Payment Notes' ),
                            'description' => __( 'Add any special instructions here' ),
                            'value' =>  $entry->notes ?? '',
                        ],
                    ]
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
    public function filterPutInputs( $inputs, ConsignorSettings $entry )
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
     * @param ConsignorSettings $entry
     * @return void
     */
    public function afterPost( $request, ConsignorSettings $entry )
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
        if ( $namespace == 'consignment.consignor.settings' ) {
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
//            'author'  =>  [
//                'label'  =>  __( 'Author' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'user_username' => [
                'label' => __( 'User' ),
                '$direction' => '',
                '$sort' => false,
            ],
//            'city'  =>  [
//                'label'  =>  __( 'City' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'created_at'  =>  [
//                'label'  =>  __( 'Created_at' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'id'  =>  [
//                'label'  =>  __( 'Id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'notes'  =>  [
//                'label'  =>  __( 'Notes' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'payout_preference'  =>  [
                'label'  =>  __( 'Payout Preference' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
//            'paypal_email'  =>  [
//                'label'  =>  __( 'Paypal_email' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'phone'  =>  [
                'label'  =>  __( 'Phone' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'share_phone'  =>  [
                'label'  =>  __( 'Share Phone?' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
//            'reference'  =>  [
//                'label'  =>  __( 'Reference' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'email'  =>  [
                'label'  =>  __( 'Email' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'share_email'  =>  [
                'label'  =>  __( 'Share Email?' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],

//            'state'  =>  [
//                'label'  =>  __( 'State' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'street'  =>  [
//                'label'  =>  __( 'Street' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'updated_at'  =>  [
//                'label'  =>  __( 'Updated_at' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'zip'  =>  [
//                'label'  =>  __( 'Zip' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
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
            'url'       =>  ns()->url( '/api/nexopos/v4/crud/consignment.consignor.settings/' . $entry->id ),
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
                if ( $entity instanceof ConsignorSettings ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'consignment/consignorsettings' ),
            'create'    =>  ns()->url( 'dashboard/' . 'consignment/consignorsettings/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'consignment/consignorsettings/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.consignor.settings' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.consignor.settings/{id}' . '' ),
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
