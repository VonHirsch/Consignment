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
use App\Models\Product;

class ProductCrud extends CrudService
{
    /**
     * define the base table
     * @param string
     */
    protected $table      =   'nexopos_products';

    /**
     * default slug
     * @param string
     */
    protected $slug   =   'consignment/products';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace  =   'consignment.products';

    /**
     * Model Used
     * @param string
     */
    protected $model      =   Product::class;

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
     * These determine which fields will be in the default insert statement
     */
    public $fillable = [
        'name',
        //'tax_value',
        //'unit_group',
        'description',
        'barcode',
        'barcode_type',
        'sku',
        'unit_group',
        'author',
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
            'list_title'            =>  __( 'Products List' ),
            'list_description'      =>  __( 'Display all products.' ),
            'no_entry'              =>  __( 'No products has been registered' ),
            'create_new'            =>  __( 'Add a new product' ),
            'create_title'          =>  __( 'Create a new product' ),
            'create_description'    =>  __( 'Register a new product and save it.' ),
            'edit_title'            =>  __( 'Edit product' ),
            'edit_description'      =>  __( 'Modify  Product.' ),
            'back_to_list'          =>  __( 'Return to Products' ),
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
//                        [
//                            'type'  =>  'text',
//                            'name'  =>  'id',
//                            'label' =>  __( 'Id' ),
//                            'value' =>  $entry->id ?? '',
//                        ],
                        [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_type',
//                            'label' =>  __( 'Tax_type' ),
//                            'value' =>  $entry->tax_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_group_id',
//                            'label' =>  __( 'Tax_group_id' ),
//                            'value' =>  $entry->tax_group_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'tax_value',
//                            'label' =>  __( 'Tax_value' ),
//                            'value' =>  $entry->tax_value ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'product_type',
//                            'label' =>  __( 'Product_type' ),
//                            'value' =>  $entry->product_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'type',
//                            'label' =>  __( 'Type' ),
//                            'value' =>  $entry->type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'accurate_tracking',
//                            'label' =>  __( 'Accurate_tracking' ),
//                            'value' =>  $entry->accurate_tracking ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'status',
//                            'label' =>  __( 'Status' ),
//                            'value' =>  $entry->status ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'stock_management',
//                            'label' =>  __( 'Stock_management' ),
//                            'value' =>  $entry->stock_management ?? '',
//                        ], [
                            'type'  =>  'hidden',
                            'name'  =>  'barcode',
                            'label' =>  __( 'Barcode' ),
                            'value' =>  $entry->barcode ?? '',
                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'barcode_type',
//                            'label' =>  __( 'Barcode_type' ),
//                            'value' =>  $entry->barcode_type ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'sku',
//                            'label' =>  __( 'Sku' ),
//                            'value' =>  $entry->sku ?? '',
//                        ], [
                            'type'  =>  'text',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'thumbnail_id',
//                            'label' =>  __( 'Thumbnail_id' ),
//                            'value' =>  $entry->thumbnail_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'category_id',
//                            'label' =>  __( 'Category_id' ),
//                            'value' =>  $entry->category_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'parent_id',
//                            'label' =>  __( 'Parent_id' ),
//                            'value' =>  $entry->parent_id ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'unit_group',
//                            'label' =>  __( 'Unit_group' ),
//                            'value' =>  $entry->unit_group ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'on_expiration',
//                            'label' =>  __( 'On_expiration' ),
//                            'value' =>  $entry->on_expiration ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'expires',
//                            'label' =>  __( 'Expires' ),
//                            'value' =>  $entry->expires ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'searchable',
//                            'label' =>  __( 'Searchable' ),
//                            'value' =>  $entry->searchable ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'author',
//                            'label' =>  __( 'Author' ),
//                            'value' =>  $entry->author ?? '',
//                        ], [
//                            'type'  =>  'text',
//                            'name'  =>  'uuid',
//                            'label' =>  __( 'Uuid' ),
//                            'value' =>  $entry->uuid ?? '',
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
        # TODO - Calculate / hardcode
        $inputs[ 'barcode' ] = '123';
        $inputs[ 'sku' ] = 'abc';
        $inputs[ 'unit_group' ] = 1;    // hardcode to consignment


        $inputs[ 'author' ] = Auth::id();
        $inputs[ 'barcode_type' ] = 'code128';

        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Product $entry )
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
     * @param Product $entry
     * @return void
     */
    public function afterPost( $request, Product $entry )
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
        if ( $namespace == 'consignment.products' ) {
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
//            'tax_type'  =>  [
//                'label'  =>  __( 'Tax_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'tax_group_id'  =>  [
//                'label'  =>  __( 'Tax_group_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'tax_value'  =>  [
//                'label'  =>  __( 'Tax_value' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'product_type'  =>  [
//                'label'  =>  __( 'Product_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'type'  =>  [
//                'label'  =>  __( 'Type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'accurate_tracking'  =>  [
//                'label'  =>  __( 'Accurate_tracking' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'status'  =>  [
//                'label'  =>  __( 'Status' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'stock_management'  =>  [
//                'label'  =>  __( 'Stock_management' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'barcode'  =>  [
//                'label'  =>  __( 'Barcode' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'barcode_type'  =>  [
//                'label'  =>  __( 'Barcode_type' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'sku'  =>  [
//                'label'  =>  __( 'Sku' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
            'description'  =>  [
                'label'  =>  __( 'Description' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
//            'thumbnail_id'  =>  [
//                'label'  =>  __( 'Thumbnail_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'category_id'  =>  [
//                'label'  =>  __( 'Category_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'parent_id'  =>  [
//                'label'  =>  __( 'Parent_id' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'unit_group'  =>  [
//                'label'  =>  __( 'Unit_group' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'on_expiration'  =>  [
//                'label'  =>  __( 'On_expiration' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'expires'  =>  [
//                'label'  =>  __( 'Expires' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'searchable'  =>  [
//                'label'  =>  __( 'Searchable' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'author'  =>  [
//                'label'  =>  __( 'Author' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'uuid'  =>  [
//                'label'  =>  __( 'Uuid' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'created_at'  =>  [
//                'label'  =>  __( 'Created_at' ),
//                '$direction'    =>  '',
//                '$sort'         =>  false
//            ],
//            'updated_at'  =>  [
//                'label'  =>  __( 'Updated_at' ),
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
            'url'       =>  ns()->url( '/api/nexopos/v4/crud/consignment.products/' . $entry->id ),
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
                if ( $entity instanceof Product ) {
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
            'list'      =>  ns()->url( 'dashboard/' . 'consignment/products' ),
            'create'    =>  ns()->url( 'dashboard/' . 'consignment/products/create' ),
            'edit'      =>  ns()->url( 'dashboard/' . 'consignment/products/edit/' ),
            'post'      =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.products' ),
            'put'       =>  ns()->url( 'api/nexopos/v4/crud/' . 'consignment.products/{id}' . '' ),
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
