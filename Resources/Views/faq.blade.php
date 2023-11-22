<?php

use App\Classes\Hook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
    <div class="h-full flex-auto flex flex-col">
        @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
        <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        {{--@include( 'common.dashboard.title' )--}}

            <body style="margin:0px;padding:0px;overflow:hidden">
                <iframe src="https://docs.google.com/document/d/e/2PACX-1vRToeyRyDjUm8aU_xpVajSXZMgZ6Ku7tkB1DFQOlIH0QImyUuuYB_HboqplhAZPQuoWzzPZTBIMakjF/pub?embedded=true" frameborder="0" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%"></iframe>
            </body>

        </div>
    </div>
@endsection

