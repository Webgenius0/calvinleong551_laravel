@extends('backend.app', ['title' => 'Menu'])

@push('styles')
<link href="{{ asset('default/datatable.css') }}" rel="stylesheet" />
@endpush


@section('content')
<!--app-content open-->
<div class="app-content main-content mt-0">
    <div class="side-app">

        <!-- CONTAINER -->
        <div class="main-container container-fluid">


            <!-- PAGE-HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Menu</h1>
                </div>
                <div class="ms-auto pageheader-btn">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Menu</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Index</li>
                    </ol>
                </div>
            </div>
            <!-- PAGE-HEADER END -->

            <!-- ROW-4 -->
            <div class="row">
                <div class="col-md-7 col-sm-7">
                    <div class="card product-sales-main">
                        <div class="card-header border-bottom">
                            <h3 class="card-title mb-0">List</h3>
                        </div>
                        <div class="card-body">
                            
                            @php
                            function renderMenus($menus, $groupedMenus) {
                            echo '<ul class="list-group">';
                                foreach ($menus as $menu) {
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center mb-3">';
                                    echo '<div>';
                                        echo '<h5 class="mb-0">' . $menu->name . '</h5>';
                                        echo '<small class="text-muted">' . ($menu->url ?? '') . '</small>';
                                        echo '<p class="text-muted mb-0">' . ($menu->description ?? '') . '</p>';
                                        echo '</div>';
                                    echo '<div>';
                                        echo '<div class="btn-group" role="group">';
                                            echo '<button type="button" class="btn btn-success"><i class="fa-solid fa-pen-to-square"></i></button>';
                                            echo '<div class="btn-group" role="group">';
                                                echo '<button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>';
                                                echo '<ul class="dropdown-menu">';
                                                    echo '<li><a class="dropdown-item" href="#">Delete</a></li>';
                                                    echo '</ul>
                                            </div>
                                        </div>
                                    </div>';
                                    echo '</li>';

                                // Render children
                                if (isset($groupedMenus[$menu->id])) {
                                echo '<li class="ms-4">';
                                    renderMenus($groupedMenus[$menu->id], $groupedMenus);
                                    echo '</li>';
                                }
                                }
                                echo '</ul>';
                            }
                            @endphp

                            @if($menus->count() > 0)
                            @php
                            renderMenus($groupedMenus[null] ?? $groupedMenus[0] ?? [], $groupedMenus);
                            @endphp
                            @else
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-0">No data found</h5>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                </div><!-- COL END -->

            </div>
            <!-- ROW-4 END -->

        </div>
    </div>
</div>
<!-- CONTAINER CLOSED -->
@endsection

@push('scripts')

@endpush