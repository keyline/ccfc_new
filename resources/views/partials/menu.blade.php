@php
    $billingOpen = request()->is('admin/payments*');
    $staticContentOpen = request()->is('admin/content-categories*')
        || request()->is('admin/content-tags*')
        || request()->is('admin/content-pages*')
        || request()->is('admin/content-blocks*')
        || request()->is('admin/create/circulars*')
        || request()->is('admin/create/event*');
    $contentOpen = request()->is('admin/reciprocal-clubs*')
        || request()->is('admin/sportsmen*')
        || request()->is('admin/past-presidents*')
        || request()->is('admin/trophies*')
        || request()->is('admin/amenities-services*')
        || $staticContentOpen;
    $committeeOpen = request()->is('admin/committee-names*')
        || request()->is('admin/committee-member-mappings*')
        || request()->is('admin/sub-committee-members*');
    $sportsOpen = request()->is('admin/sportstypes*')
        || request()->is('admin/titles*')
        || request()->is('admin/members*');
    $usersOpen = request()->is('admin/permissions*')
        || request()->is('admin/roles*')
        || request()->is('admin/users*')
        || request()->is('admin/user-details*');
    $duesOpen = request()->is('admin/dues*');
    $tendersOpen = request()->is('admin/tenderuploads*');
    $mobileOpen = request()->is('admin/create/dayspeciallist*')
        || request()->is('admin/create/otherfooditemlist*')
        || request()->is('admin/create/deleteaccountrequests*');
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

<aside class="main-sidebar sidebar-dark-primary elevation-4" aria-label="Administration sidebar">
    <a href="{{ route('admin.home') }}" class="brand-link" aria-label="CCFC admin dashboard">
        <span class="brand-mark">
            <img src="{{ asset('img/black-lineCCFC-Logo.png') }}" alt="">
        </span>
        <span class="brand-copy">
            <span class="brand-name">CCFC</span>
            <span class="brand-caption">Administration</span>
        </span>
    </a>

    <div class="sidebar">
        <div class="sidebar-profile">
            <div class="sidebar-avatar" aria-hidden="true">{{ $adminInitials }}</div>
            <div class="sidebar-profile-copy">
                <strong>{{ $adminName }}</strong>
                <span>{{ $adminRole }}</span>
            </div>
            <span class="status-indicator" title="Signed in"></span>
        </div>

        <div class="sidebar-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input id="admin-menu-search" type="search" placeholder="Find a menu item" autocomplete="off"
                aria-label="Search the administration menu">
            <button class="sidebar-search-clear" type="button" aria-label="Clear menu search">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="sidebar-navigation" aria-label="Primary administration">
            <ul id="admin-sidebar-menu" class="nav nav-pills nav-sidebar flex-column" role="menu"
                data-accordion="false">
                <li class="nav-header">Overview</li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}"
                        href="{{ route('admin.home') }}"
                        @if(request()->routeIs('admin.home')) aria-current="page" @endif>
                        <i class="fas fa-chart-pie nav-icon" aria-hidden="true"></i>
                        <p>{{ trans('global.dashboard') }}</p>
                    </a>
                </li>

                <li class="nav-header">Club operations</li>
                @can('billing_access')
                    <li class="nav-item has-treeview {{ $billingOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $billingOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-wallet nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.billing.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('payment_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.payments.index') }}"
                                        class="nav-link {{ request()->is('admin/payments') || request()->is('admin/payments/*') ? 'active' : '' }}">
                                        <i class="fas fa-receipt nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.payment.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('monthly_dues_management_access')
                    <li class="nav-item has-treeview {{ $duesOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $duesOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-file-invoice-dollar nav-icon" aria-hidden="true"></i>
                            <p>
                                Monthly dues
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('monthly_dues_upload')
                                <li class="nav-item">
                                    <a href="{{ route('admin.dues.upload.form') }}"
                                        class="nav-link {{ request()->is('admin/dues/upload') ? 'active' : '' }}">
                                        <i class="fas fa-cloud-upload-alt nav-icon" aria-hidden="true"></i>
                                        <p>Upload dues data</p>
                                    </a>
                                </li>
                            @endcan
                            @can('monthly_dues_list')
                                <li class="nav-item">
                                    <a href="{{ route('admin.dues.list') }}"
                                        class="nav-link {{ request()->is('admin/dues/list') ? 'active' : '' }}">
                                        <i class="fas fa-list-ul nav-icon" aria-hidden="true"></i>
                                        <p>Dues list</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('committee_management_access')
                    <li class="nav-item has-treeview {{ $committeeOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $committeeOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-user-friends nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.committeeManagement.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('committee_name_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.committee-names.index') }}"
                                        class="nav-link {{ request()->is('admin/committee-names') || request()->is('admin/committee-names/*') ? 'active' : '' }}">
                                        <i class="fas fa-layer-group nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.committeeName.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('committee_member_mapping_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.committee-member-mappings.index') }}"
                                        class="nav-link {{ request()->is('admin/committee-member-mappings') || request()->is('admin/committee-member-mappings/*') ? 'active' : '' }}">
                                        <i class="fas fa-user-cog nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.committeeMemberMapping.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('sub_committee_member_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.sub-committee-members.index') }}"
                                        class="nav-link {{ request()->is('admin/sub-committee-members') || request()->is('admin/sub-committee-members/*') ? 'active' : '' }}">
                                        <i class="fas fa-people-arrows nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.subCommitteeMember.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('sports_management_access')
                    <li class="nav-item has-treeview {{ $sportsOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $sportsOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-running nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.sportsManagement.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('sportstype_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.sportstypes.index') }}"
                                        class="nav-link {{ request()->is('admin/sportstypes') || request()->is('admin/sportstypes/*') ? 'active' : '' }}">
                                        <i class="fas fa-football-ball nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.sportstype.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('title_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.titles.index') }}"
                                        class="nav-link {{ request()->is('admin/titles') || request()->is('admin/titles/*') ? 'active' : '' }}">
                                        <i class="fas fa-id-badge nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.title.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('member_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.members.index') }}"
                                        class="nav-link {{ request()->is('admin/members') || request()->is('admin/members/*') ? 'active' : '' }}">
                                        <i class="fas fa-user-check nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.member.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('tender_management_access')
                    <li class="nav-item has-treeview {{ $tendersOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $tendersOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-folder-open nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.tenderManagement.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('tenderupload_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.tenderuploads.index') }}"
                                        class="nav-link {{ request()->is('admin/tenderuploads') || request()->is('admin/tenderuploads/*') ? 'active' : '' }}">
                                        <i class="fas fa-file-upload nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.tenderupload.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <li class="nav-header">Content &amp; experience</li>
                @can('content_management_access')
                    <li class="nav-item has-treeview {{ $contentOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $contentOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-feather-alt nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.contentManagement.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('reciprocal_club_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.reciprocal-clubs.index') }}"
                                        class="nav-link {{ request()->is('admin/reciprocal-clubs') || request()->is('admin/reciprocal-clubs/*') ? 'active' : '' }}">
                                        <i class="fas fa-handshake nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.reciprocalClub.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('sportsman_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.sportsmen.index') }}"
                                        class="nav-link {{ request()->is('admin/sportsmen') || request()->is('admin/sportsmen/*') ? 'active' : '' }}">
                                        <i class="fas fa-medal nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.sportsman.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('past_president_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.past-presidents.index') }}"
                                        class="nav-link {{ request()->is('admin/past-presidents') || request()->is('admin/past-presidents/*') ? 'active' : '' }}">
                                        <i class="fas fa-user-tie nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.pastPresident.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('trophy_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.trophies.index') }}"
                                        class="nav-link {{ request()->is('admin/trophies') || request()->is('admin/trophies/*') ? 'active' : '' }}">
                                        <i class="fas fa-trophy nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.trophy.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('amenities_service_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.amenities-services.index') }}"
                                        class="nav-link {{ request()->is('admin/amenities-services') || request()->is('admin/amenities-services/*') ? 'active' : '' }}">
                                        <i class="fas fa-concierge-bell nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.amenitiesService.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('static_page_management_access')
                                <li class="nav-item has-treeview {{ $staticContentOpen ? 'menu-open' : '' }}">
                                    <a class="nav-link nav-dropdown-toggle {{ $staticContentOpen ? 'active-parent' : '' }}"
                                        href="#">
                                        <i class="fas fa-swatchbook nav-icon" aria-hidden="true"></i>
                                        <p>
                                            {{ trans('cruds.staticPageManagement.title') }}
                                            <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                                        </p>
                                    </a>
                                    <ul class="nav nav-treeview">
                                        @can('content_category_access')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.content-categories.index') }}"
                                                    class="nav-link {{ request()->is('admin/content-categories') || request()->is('admin/content-categories/*') ? 'active' : '' }}">
                                                    <i class="fas fa-folder nav-icon" aria-hidden="true"></i>
                                                    <p>{{ trans('cruds.contentCategory.title') }}</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('content_tag_access')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.content-tags.index') }}"
                                                    class="nav-link {{ request()->is('admin/content-tags') || request()->is('admin/content-tags/*') ? 'active' : '' }}">
                                                    <i class="fas fa-tags nav-icon" aria-hidden="true"></i>
                                                    <p>{{ trans('cruds.contentTag.title') }}</p>
                                                </a>
                                            </li>
                                        @endcan
                                        @can('content_page_access')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.content-pages.index') }}"
                                                    class="nav-link {{ request()->is('admin/content-pages') || request()->is('admin/content-pages/*') ? 'active' : '' }}">
                                                    <i class="fas fa-file-alt nav-icon" aria-hidden="true"></i>
                                                    <p>{{ trans('cruds.contentPage.title') }}</p>
                                                </a>
                                            </li>
                                        @endcan
                                        <li class="nav-item">
                                            <a href="{{ route('admin.circulars') }}"
                                                class="nav-link {{ request()->is('admin/create/circulars*') ? 'active' : '' }}">
                                                <i class="fas fa-bullhorn nav-icon" aria-hidden="true"></i>
                                                <p>{{ trans('global.circular') }}</p>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="{{ route('admin.event') }}"
                                                class="nav-link {{ request()->is('admin/create/event*') ? 'active' : '' }}">
                                                <i class="fas fa-calendar-day nav-icon" aria-hidden="true"></i>
                                                <p>{{ trans('global.event') }}</p>
                                            </a>
                                        </li>
                                        @can('content_block_access')
                                            <li class="nav-item">
                                                <a href="{{ route('admin.content-blocks.index') }}"
                                                    class="nav-link {{ request()->is('admin/content-blocks') || request()->is('admin/content-blocks/*') ? 'active' : '' }}">
                                                    <i class="fas fa-th-large nav-icon" aria-hidden="true"></i>
                                                    <p>{{ trans('cruds.contentBlock.title') }}</p>
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                @can('gallery_access')
                    <li class="nav-item">
                        <a href="{{ route('admin.galleries.index') }}"
                            class="nav-link {{ request()->is('admin/galleries') || request()->is('admin/galleries/*') ? 'active' : '' }}">
                            <i class="fas fa-images nav-icon" aria-hidden="true"></i>
                            <p>{{ trans('cruds.gallery.title') }}</p>
                        </a>
                    </li>
                @endcan

                <li class="nav-item has-treeview {{ $mobileOpen ? 'menu-open' : '' }}">
                    <a class="nav-link nav-dropdown-toggle {{ $mobileOpen ? 'active-parent' : '' }}" href="#">
                        <i class="fas fa-mobile-alt nav-icon" aria-hidden="true"></i>
                        <p>
                            Mobile app
                            <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ url('admin/create/dayspeciallist') }}"
                                class="nav-link {{ request()->is('admin/create/dayspeciallist*') ? 'active' : '' }}">
                                <i class="fas fa-star nav-icon" aria-hidden="true"></i>
                                <p>Day specials</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('admin/create/otherfooditemlist') }}"
                                class="nav-link {{ request()->is('admin/create/otherfooditemlist*') ? 'active' : '' }}">
                                <i class="fas fa-utensils nav-icon" aria-hidden="true"></i>
                                <p>Outside items</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ url('admin/create/deleteaccountrequests') }}"
                                class="nav-link {{ request()->is('admin/create/deleteaccountrequests*') ? 'active' : '' }}">
                                <i class="fas fa-user-minus nav-icon" aria-hidden="true"></i>
                                <p>Account requests</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">Communication</li>
                <li class="nav-item">
                    <a href="{{ route('admin.list-campaign') }}"
                        class="nav-link {{ request()->is('admin/campaigns*') || request()->is('admin/campaingns*') ? 'active' : '' }}">
                        <i class="fas fa-paper-plane nav-icon" aria-hidden="true"></i>
                        <p>{{ trans('global.email') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.contactus') }}"
                        class="nav-link {{ request()->is('admin/contactus') ? 'active' : '' }}">
                        <i class="fas fa-inbox nav-icon" aria-hidden="true"></i>
                        <p>{{ trans('global.contact-us') }}</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.contactlist') }}"
                        class="nav-link {{ request()->is('admin/create/contactlist*') ? 'active' : '' }}">
                        <i class="fas fa-address-book nav-icon" aria-hidden="true"></i>
                        <p>Contact list</p>
                    </a>
                </li>

                <li class="nav-header">Administration</li>
                @can('user_management_access')
                    <li class="nav-item has-treeview {{ $usersOpen ? 'menu-open' : '' }}">
                        <a class="nav-link nav-dropdown-toggle {{ $usersOpen ? 'active-parent' : '' }}" href="#">
                            <i class="fas fa-users-cog nav-icon" aria-hidden="true"></i>
                            <p>
                                {{ trans('cruds.userManagement.title') }}
                                <i class="right fas fa-chevron-left" aria-hidden="true"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('permission_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.permissions.index') }}"
                                        class="nav-link {{ request()->is('admin/permissions') || request()->is('admin/permissions/*') ? 'active' : '' }}">
                                        <i class="fas fa-shield-alt nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.permission.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('role_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.roles.index') }}"
                                        class="nav-link {{ request()->is('admin/roles') || request()->is('admin/roles/*') ? 'active' : '' }}">
                                        <i class="fas fa-briefcase nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.role.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                            @can('user_access')
                                <li class="nav-item">
                                    <a href="{{ route('admin.users.index') }}"
                                        class="nav-link {{ request()->is('admin/users') || request()->is('admin/users/*') ? 'active' : '' }}">
                                        <i class="fas fa-user nav-icon" aria-hidden="true"></i>
                                        <p>{{ trans('cruds.user.title') }}</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan

                <li class="nav-item">
                    <a href="{{ route('admin.settinglist') }}"
                        class="nav-link {{ request()->is('admin/create/settinglist*') ? 'active' : '' }}">
                        <i class="fas fa-sliders-h nav-icon" aria-hidden="true"></i>
                        <p>Settings</p>
                    </a>
                </li>

                @if(file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php')))
                    @can('profile_password_edit')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('profile/password') || request()->is('profile/password/*') ? 'active' : '' }}"
                                href="{{ route('profile.password.edit') }}">
                                <i class="fas fa-key nav-icon" aria-hidden="true"></i>
                                <p>{{ trans('global.change_password') }}</p>
                            </a>
                        </li>
                    @endcan
                @endif
            </ul>

            <div class="sidebar-empty-state" aria-live="polite">
                <i class="fas fa-search" aria-hidden="true"></i>
                <span>No menu items found</span>
            </div>
        </nav>

        <div class="sidebar-footer">
            <a href="#" class="sidebar-logout"
                onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span>{{ trans('global.logout') }}</span>
            </a>
        </div>
    </div>
</aside>
