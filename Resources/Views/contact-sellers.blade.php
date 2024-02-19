@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <ns-contact-sellers inline-template>
        <div id="report-section">
            <div class="flex -mx-2">
                {{--<div class="px-2">--}}
                    {{--<ns-date-time-picker :date="startDate" @change="setStartDate( $event )"></ns-date-time-picker>--}}
                {{--</div>--}}
                {{--<div class="px-2">--}}
                    {{--<ns-date-time-picker :date="endDate" @change="setEndDate( $event )"></ns-date-time-picker>--}}
                {{--</div>--}}
                <div class="px-2" v-if="selectedProduct">
                    <div class="ns-button">
                        <button @click="handleselectedProduct( selectedProduct )" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">
                            <i class="las la-sync-alt text-xl"></i>
                            <span class="pl-2">{{ __( 'Load' ) }}</span>
                        </button>
                    </div>
                </div>
            </div>
            <div>
                <ns-search
                        placeholder="{{ __( 'Start Typing Barcode...' ) }}"
                        label="name"
                        value="id"
                        @select="handleselectedProduct( $event )"
                        url="{{ ns()->route( 'ns.consignor.barcode.search' ) }}"
                ></ns-search>
            </div>
            {{--<div class="px-2 p-2">--}}
                {{--<div class="ns-button">--}}
                    {{--<button @click="printSaleReport()" class="rounded flex justify-between text-primary shadow py-1 items-center  px-2">--}}
                        {{--<i class="las la-print text-xl"></i>--}}
                        {{--<span class="pl-2">{{ __( 'Print' ) }}</span>--}}
                    {{--</button>--}}
                {{--</div>--}}
            {{--</div>--}}
            <div id="report" class="anim-duration-500 fade-in-entrance">
                <div class="flex w-full">
                    <div class="my-4 flex justify-between w-full">
                        <div class="text-primary">
                            <ul>
                                <li class="pb-1 border-b border-dashed border-box-edge" v-if="selectedProductBarcode !== 'N/A'">{{ sprintf( __( 'Barcode : %s' ), '{' . '{selectedProductBarcode}' . '}' ) }}</li>
                                <li class="pb-1 border-b border-dashed border-box-edge" v-if="selectedProductName !== 'N/A'">{{ sprintf( __( 'Product : %s' ), '{' . '{selectedProductName}' . '}' ) }}</li>
                                <li class="pb-1 border-b border-dashed border-box-edge" v-if="selectedProductDescription !== 'N/A'">{{ sprintf( __( 'Description : %s' ), '{' . '{selectedProductDescription}' . '}' ) }}</li>
                            </ul>
                        </div>
                        <div>
                            <img class="w-72" src="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}" alt="{{ ns()->option->get( 'ns_store_name' ) }}">
                        </div>
                    </div>
                </div>
                {{--<div class="shadow rounded">--}}
                <div class="shadow rounded overflow-hidden" v-if="report !== null">
                    <div class="ns-box">
                        <div class="text-center ns-box-header p-2">
                            <h3 class="font-bold">{{ __( 'Seller Contact Info' ) }}</h3>
                        </div>
                        <div class="border-b ns-box-body">
                            <table class="table ns-table w-full">
                                <tbody class="text-primary">
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-info-primary text-white print:text-black">{{ __( 'Email' ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">@{{ report.email }}</td>
                                </tr>
                                <tr class="">
                                    <td width="200" class="font-semibold p-2 border text-left bg-success-secondary border-info-primary text-white print:text-black">{{ __( 'Phone' ) }}</td>
                                    <td class="p-2 border text-right border-info-primary">@{{ report.phone }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <br><br>
            </div>
        </div>
    </ns-contact-sellers>
@endsection

@section( 'layout.dashboard.footer.inject' )
    <script>
        Vue.component( 'ns-contact-sellers', {
            data() {
                return {
                    selectedProduct: null,
                    report: null,
                }
            },
            mounted() {
                // ...
            },
            computed: {
                selectedProductName() {
                    if (this.selectedProduct === null ) {
                        return __( 'N/A' );
                    }

                    return this.selectedProduct.name;
                },
                selectedProductBarcode() {
                    if (this.selectedProduct === null ) {
                        return __( 'N/A' );
                    }

                    return this.selectedProduct.barcode;
                },
                selectedProductDescription() {
                    if (this.selectedProduct === null ) {
                        return __( 'N/A' );
                    }
                    if (this.selectedProduct.description) {
                        return this.selectedProduct.description;
                    } else {
                        return __( 'N/A' );
                    }

                },
            },
            methods: {

                printSaleReport() {
                    this.$htmlToPaper( 'report' );
                },
                setStartDate( date ) {
                    this.startDate  =   date;
                },
                setEndDate( date ) {
                    this.endDate    =   date;
                },
                handleselectedProduct( product ) {
                    this.selectedProduct   =   product;

                    nsHttpClient.post( `/dashboard/consignment/consignor-contact-info/`, {
                        author: product.author
                    }).subscribe( result => {
                        this.report     =   result;
                    }, error => {
                        nsSnackBar.error( error.message ).subscribe();
                    })
                }
            }
        })
    </script>
@endsection
