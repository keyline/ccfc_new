@php
    $routeName = request()->route() ? request()->route()->getName() : '';
    $routeParts = explode('.', (string) $routeName);
    if (!empty($routeName) && isset($routeParts[0]) && $routeParts[0] === 'admin') {
        $routeKey = $routeParts[1] ?? 'home';
    } elseif (!empty($routeName)) {
        $routeKey = $routeParts[0] ?? 'home';
    } else {
        $routeKey = request()->segment(3) ?: request()->segment(2) ?: 'home';
    }
    $adminPageTitles = [
        'home' => trans('global.dashboard'),
        'payments' => trans('cruds.payment.title'),
        'dues' => 'Monthly dues',
        'committee-names' => trans('cruds.committeeName.title'),
        'committee-member-mappings' => trans('cruds.committeeMemberMapping.title'),
        'sub-committee-members' => trans('cruds.subCommitteeMember.title'),
        'sportstypes' => trans('cruds.sportstype.title'),
        'titles' => trans('cruds.title.title'),
        'members' => trans('cruds.member.title'),
        'tenderuploads' => trans('cruds.tenderupload.title'),
        'reciprocal-clubs' => trans('cruds.reciprocalClub.title'),
        'sportsmen' => trans('cruds.sportsman.title'),
        'past-presidents' => trans('cruds.pastPresident.title'),
        'trophies' => trans('cruds.trophy.title'),
        'amenities-services' => trans('cruds.amenitiesService.title'),
        'content-categories' => trans('cruds.contentCategory.title'),
        'content-tags' => trans('cruds.contentTag.title'),
        'content-pages' => trans('cruds.contentPage.title'),
        'content-blocks' => trans('cruds.contentBlock.title'),
        'circulars' => trans('global.circular'),
        'event' => trans('global.event'),
        'galleries' => trans('cruds.gallery.title'),
        'event-details' => trans('cruds.eventDetail.title'),
        'newss' => trans('cruds.news.title'),
        'list-campaign' => trans('global.email'),
        'contactus' => trans('global.contact-us'),
        'contactlist' => 'Contact list',
        'cookingcategorylist' => 'Cooking categories',
        'cookingitemlist' => 'Cooking items',
        'cookingitemreportlist' => 'Cooking reports',
        'dayspeciallist' => 'Day specials',
        'otherfooditemlist' => 'Outside food items',
        'deleteaccountrequests' => 'Account deletion requests',
        'spabookingtrackinglist' => 'Spa booking tracking',
        'profileupdaterequests' => 'Profile update requests',
        'mustreadlist' => 'Must-read notices',
        'permissions' => trans('cruds.permission.title'),
        'roles' => trans('cruds.role.title'),
        'users' => trans('cruds.user.title'),
        'user-details' => trans('cruds.userDetail.title'),
        'settinglist' => 'Settings',
        'profile' => 'Account security',
    ];
    $customPageTitle = trim($__env->yieldContent('title'));
    $pageTitle = $customPageTitle ?: ($adminPageTitles[$routeKey] ?? ucwords(str_replace(['-', '_'], ' ', $routeKey)));
    $adminName = auth()->check() ? auth()->user()->name : 'Administrator';
    $adminRole = auth()->check() && auth()->user()->roles->count()
        ? auth()->user()->roles->first()->title
        : 'Administrator';
    $adminInitials = '';
    foreach (array_slice(preg_split('/\s+/', trim($adminName)), 0, 2) as $namePart) {
        $adminInitials .= strtoupper(substr($namePart, 0, 1));
    }
    $adminInitials = $adminInitials ?: 'A';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111827">

    <title>{{ $pageTitle }} | {{ trans('panel.site_title') }}</title>

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css"
        rel="stylesheet">
    <link href="{{ asset('css/adminltev3.css') }}" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v5.15.4/css/all.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/select/1.4.0/css/select.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" rel="stylesheet">
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin-shell.css') }}" rel="stylesheet">
    @yield('styles')
</head>

<body class="ccfc-admin sidebar-mini layout-fixed">
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-light" aria-label="Administration toolbar">
            <div class="navbar-left">
                <button class="nav-control menu-toggle" type="button" data-widget="pushmenu"
                    aria-label="Toggle navigation" aria-controls="admin-sidebar-menu" aria-expanded="true">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
                <div class="navbar-page-context">
                    <span>Admin workspace</span>
                    <strong>{{ $pageTitle }}</strong>
                </div>
            </div>

            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item d-none d-md-block">
                    <a class="nav-link site-preview-link" href="{{ url('/') }}" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                        <span>View website</span>
                    </a>
                </li>

                @if(count(config('panel.available_languages', [])) > 1)
                    <li class="nav-item dropdown d-none d-sm-block">
                        <a class="nav-link language-switcher" data-toggle="dropdown" href="#" role="button"
                            aria-haspopup="true" aria-expanded="false">
                            {{ strtoupper(app()->getLocale()) }}
                            <i class="fas fa-chevron-down" aria-hidden="true"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            @foreach(config('panel.available_languages') as $langLocale => $langName)
                                <a class="dropdown-item" href="{{ url()->current() }}?change_language={{ $langLocale }}">
                                    {{ strtoupper($langLocale) }} <span>{{ $langName }}</span>
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endif

                <li class="nav-item dropdown user-menu">
                    <a class="nav-link user-menu-toggle" data-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="topbar-avatar">{{ $adminInitials }}</span>
                        <span class="topbar-user-copy d-none d-sm-flex">
                            <strong>{{ $adminName }}</strong>
                            <small>{{ $adminRole }}</small>
                        </span>
                        <i class="fas fa-chevron-down d-none d-sm-inline-block" aria-hidden="true"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right user-dropdown">
                        <div class="user-dropdown-header">
                            <span class="topbar-avatar large">{{ $adminInitials }}</span>
                            <div>
                                <strong>{{ $adminName }}</strong>
                                <span>{{ $adminRole }}</span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('/') }}" target="_blank" rel="noopener">
                            <i class="fas fa-globe" aria-hidden="true"></i>
                            View public website
                        </a>
                        @if(file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php')))
                            @can('profile_password_edit')
                                <a class="dropdown-item" href="{{ route('profile.password.edit') }}">
                                    <i class="fas fa-key" aria-hidden="true"></i>
                                    Change password
                                </a>
                            @endcan
                        @endif
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#"
                            onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            {{ trans('global.logout') }}
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        @include('partials.menu')

        <main class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="page-heading">
                        <div>
                            <span class="page-eyebrow">CCFC administration</span>
                            <h1>{{ $pageTitle }}</h1>
                            <p>Manage club operations, member services and digital content.</p>
                        </div>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.home') }}">
                                    <i class="fas fa-home" aria-hidden="true"></i>
                                    Home
                                </a>
                            </li>
                            @if(!request()->routeIs('admin.home'))
                                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>

            <section class="content">
                <div class="container-fluid">
                    @if(session('message'))
                        <div class="alert alert-success alert-dismissible fade show autoHideAlert" role="alert">
                            <span class="alert-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
                            <div>
                                <strong>Success</strong>
                                <span>{{ session('message') }}</span>
                            </div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->count() > 0)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <span class="alert-icon"><i class="fas fa-exclamation" aria-hidden="true"></i></span>
                            <div>
                                <strong>Please review the following</strong>
                                <ul class="mb-0 pl-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </section>
        </main>

        <footer class="main-footer">
            <div>
                <span>&copy; {{ now()->year }} Calcutta Cricket &amp; Football Club.</span>
                <span class="footer-divider" aria-hidden="true"></span>
                <span>{{ trans('global.allRightsReserved') }}</span>
            </div>
            <div class="footer-credit">
                Designed &amp; developed by
                <a href="https://keylines.net/" target="_blank" rel="noopener">KEYLINE</a>
            </div>
        </footer>

        <form id="logoutform" action="{{ route('logout') }}" method="POST" class="d-none">
            {{ csrf_field() }}
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.4.0/js/dataTables.select.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script src="{{ asset('js/main.js') }}"></script>

    <script>
        if ($.fn.dataTable) {
            var dataTableLanguages = {
                en: 'https://cdn.datatables.net/plug-ins/1.12.1/i18n/en-GB.json'
            };

            $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {
                className: 'btn'
            });

            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: dataTableLanguages['{{ app()->getLocale() }}'] || dataTableLanguages.en
                },
                columnDefs: [{
                    orderable: false,
                    className: 'select-checkbox',
                    targets: 0
                }, {
                    orderable: false,
                    searchable: false,
                    targets: -1
                }],
                select: {
                    style: 'multi+shift',
                    selector: 'td:first-child'
                },
                order: [],
                scrollX: true,
                pageLength: 100,
                dom: 'lBfrtip<"actions">',
                buttons: [{
                    extend: 'selectAll',
                    className: 'btn-primary',
                    text: {!! json_encode(trans('global.select_all')) !!},
                    exportOptions: { columns: ':visible' },
                    action: function(e, dt) {
                        e.preventDefault();
                        dt.rows().deselect();
                        dt.rows({ search: 'applied' }).select();
                    }
                }, {
                    extend: 'selectNone',
                    className: 'btn-primary',
                    text: {!! json_encode(trans('global.deselect_all')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'copy',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.copy')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'csv',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.csv')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'excel',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.excel')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'pdf',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.pdf')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'print',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.print')) !!},
                    exportOptions: { columns: ':visible' }
                }, {
                    extend: 'colvis',
                    className: 'btn-default',
                    text: {!! json_encode(trans('global.datatables.colvis')) !!},
                    exportOptions: { columns: ':visible' }
                }]
            });

            $.fn.dataTable.ext.classes.sPageButton = '';
        }
    </script>

    @yield('scripts')
    <script src="{{ asset('js/admin-shell.js') }}"></script>
</body>
</html>
