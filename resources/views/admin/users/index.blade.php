@extends('layouts.admin')

@section('title', 'Members')

@section('content')
    <div id="users-sync-notice" aria-live="polite">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show autoHideAlert" role="alert">
                <span class="alert-icon"><i class="fas fa-check" aria-hidden="true"></i></span>
                <div>
                    <strong>Profile synchronized</strong>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="fas fa-exclamation" aria-hidden="true"></i></span>
                <div>
                    <strong>Profile synchronization failed</strong>
                    <span>{{ session('error') }}</span>
                </div>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>

    <div id="member-sync-overlay" class="member-sync-overlay" aria-hidden="true"
        aria-labelledby="member-sync-title" aria-describedby="member-sync-description">
        <div class="member-sync-dialog" role="status" aria-live="assertive">
            <div class="member-sync-loader" aria-hidden="true">
                <span></span>
                <i class="fas fa-user" aria-hidden="true"></i>
            </div>
            <span class="member-sync-eyebrow">Clubman synchronization</span>
            <h2 id="member-sync-title">Member data updating</h2>
            <p id="member-sync-description">
                Importing the latest member profile and securely saving it to the database.
            </p>
            <div class="member-sync-progress" aria-hidden="true"><span></span></div>
            <small>Please keep this page open until the update completes.</small>
        </div>
    </div>

    <div class="users-page-actions">
        <div>
            @can('user_create')
                <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
                    <i class="fas fa-user-plus mr-1" aria-hidden="true"></i>
                    {{ trans('global.add') }} {{ trans('cruds.user.title_singular') }}
                </a>
            @endcan
            @can('user_delete')
                <button id="bulk-delete-users" class="btn btn-default" type="button" disabled>
                    <i class="fas fa-trash-alt mr-1" aria-hidden="true"></i>
                    Delete selected
                    <span id="selected-user-count" class="users-selected-count">0</span>
                </button>
            @endcan
        </div>

        <a class="btn btn-default" href="{{ route('admin.users.exporttocsv') }}">
            <i class="fas fa-file-export mr-1" aria-hidden="true"></i>
            Export CSV
        </a>
    </div>

    <div class="card users-card">
        <div class="card-header users-card-header">
            <div>
                <span class="users-card-kicker">Member directory</span>
                <h2>{{ number_format($users->total()) }} member records</h2>
            </div>
            <span class="users-result-summary">
                @if($users->total())
                    Showing {{ number_format($users->firstItem()) }}–{{ number_format($users->lastItem()) }}
                @else
                    No results
                @endif
            </span>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="users-filter-form">
                <div class="users-search-field">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="search" value="{{ $search }}"
                        placeholder="Search name, email, member code, phone or status"
                        aria-label="Search members">
                </div>

                <div class="users-filter-select">
                    <label for="verification">Verification</label>
                    <select id="verification" name="verification">
                        <option value="">All accounts</option>
                        <option value="verified" {{ $verification === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="pending" {{ $verification === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <div class="users-filter-select users-page-size">
                    <label for="per-page">Rows</label>
                    <select id="per-page" name="per_page">
                        @foreach([25, 50, 100] as $pageSize)
                            <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>
                                {{ $pageSize }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button class="btn btn-primary users-filter-submit" type="submit">
                    Apply filters
                </button>

                @if($search !== '' || $verification || $perPage !== 50)
                    <a class="users-clear-filter" href="{{ route('admin.users.index') }}">
                        <i class="fas fa-times" aria-hidden="true"></i>
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive users-table-wrap">
            <table class="table table-hover users-table">
                <thead>
                    <tr>
                        @can('user_delete')
                            <th class="users-check-column">
                                <label class="users-checkbox" title="Select all users on this page">
                                    <input id="select-all-users" type="checkbox">
                                    <span></span>
                                </label>
                            </th>
                        @endcan
                        <th>Member</th>
                        <th>Member code</th>
                        <th>Contact</th>
                        <th>Access</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr data-entry-id="{{ $user->id }}">
                            @can('user_delete')
                                <td class="users-check-column">
                                    <label class="users-checkbox">
                                        <input class="user-row-checkbox" type="checkbox" value="{{ $user->id }}"
                                            aria-label="Select {{ $user->name ?: 'member ' . $user->id }}">
                                        <span></span>
                                    </label>
                                </td>
                            @endcan
                            <td>
                                <div class="users-member-cell">
                                    <span class="users-member-avatar">
                                        {{ strtoupper(substr(trim($user->name ?: 'M'), 0, 1)) }}
                                    </span>
                                    <div>
                                        <strong class="users-member-name">{{ $user->name ?: 'Unnamed member' }}</strong>
                                        <span>#{{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="users-member-code">{{ $user->user_code ?: '—' }}</span>
                            </td>
                            <td>
                                <div class="users-contact-cell">
                                    <span class="users-member-email" title="{{ $user->email }}">
                                        {{ $user->email ?: 'No email' }}
                                    </span>
                                    <small class="users-member-phone">
                                        {{ $user->phone_number_1 ?: 'No phone number' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="users-access-cell">
                                    <div>
                                        @forelse($user->roles as $role)
                                            <span class="badge badge-info">{{ $role->title }}</span>
                                        @empty
                                            <span class="users-muted">No role</span>
                                        @endforelse
                                    </div>
                                    <small>
                                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                        2FA {{ $user->two_factor ? 'enabled' : 'off' }}
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="users-status-stack">
                                    @if($user->email_verified_at)
                                        <span class="users-status verified">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            Verified
                                        </span>
                                    @else
                                        <span class="users-status pending">
                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                            Pending
                                        </span>
                                    @endif
                                    <small class="users-club-status">{{ $user->status ?: 'No Clubman status' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="users-updated-at user-updated-value">
                                    {{ optional($user->updated_at)->format('d M Y') ?: '—' }}
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="users-row-actions">
                                    @can('user_show')
                                        <a class="users-icon-action" href="{{ route('admin.users.show', $user->id) }}"
                                            title="{{ trans('global.view') }}" aria-label="View {{ $user->name ?: 'member' }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    @endcan

                                    @can('user_edit')
                                        <button type="button"
                                            class="users-icon-action user-profile-sync {{ $user->user_code_user_details_count > 0 ? 'profile-ready' : 'profile-pending' }}"
                                            data-url="{{ route('admin.saveUserJson', $user->user_code) }}"
                                            data-member-code="{{ $user->user_code }}"
                                            title="{{ $user->user_code_user_details_count > 0 ? 'Refresh Clubman details' : 'Import Clubman details' }}"
                                            aria-label="Update Clubman details for {{ $user->name ?: 'member' }}">
                                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                        </button>
                                    @endcan

                                    @can('user_delete')
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST"
                                            onsubmit='return confirm({!! json_encode(trans('global.areYouSure')) !!});'>
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <button class="users-icon-action delete" type="submit"
                                                title="{{ trans('global.delete') }}"
                                                aria-label="Delete {{ $user->name ?: 'member' }}">
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Gate::allows('user_delete') ? 8 : 7 }}">
                                <div class="users-empty-state">
                                    <i class="fas fa-user-slash" aria-hidden="true"></i>
                                    <strong>No members found</strong>
                                    <span>Try changing or clearing the current filters.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="users-pagination">
                <span>
                    Page {{ number_format($users->currentPage()) }} of {{ number_format($users->lastPage()) }}
                </span>
                {{ $users->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    @parent
    @can('user_edit')
        <script>
            $(function () {
                function showSyncNotice(type, heading, message) {
                    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                    var iconClass = type === 'success' ? 'fa-check' : 'fa-exclamation';
                    var $alert = $('<div>', {
                        class: 'alert ' + alertClass + ' alert-dismissible fade show',
                        role: 'alert'
                    });
                    var $icon = $('<span>', { class: 'alert-icon' })
                        .append($('<i>', { class: 'fas ' + iconClass, 'aria-hidden': 'true' }));
                    var $copy = $('<div>')
                        .append($('<strong>').text(heading))
                        .append($('<span>').text(message));
                    var $close = $('<button>', {
                        type: 'button',
                        class: 'close',
                        'data-dismiss': 'alert',
                        'aria-label': 'Close'
                    }).append($('<span>', { 'aria-hidden': 'true' }).html('&times;'));

                    $alert.append($icon, $copy, $close);
                    $('#users-sync-notice').empty().append($alert);

                    window.setTimeout(function () {
                        $alert.alert('close');
                    }, 7000);
                }

                function showSyncOverlay(memberName) {
                    var $overlay = $('#member-sync-overlay');
                    var description = memberName
                        ? 'Importing the latest Clubman profile for ' + memberName + ' and securely saving it to the database.'
                        : 'Importing the latest member profile and securely saving it to the database.';

                    $overlay.find('#member-sync-description').text(description);
                    $overlay.addClass('is-visible').attr('aria-hidden', 'false');
                    $('body').addClass('member-sync-active');
                }

                function hideSyncOverlay() {
                    $('#member-sync-overlay').removeClass('is-visible').attr('aria-hidden', 'true');
                    $('body').removeClass('member-sync-active');
                }

                $('.user-profile-sync').on('click', function () {
                    var $button = $(this);
                    var $row = $button.closest('tr');
                    var $icon = $button.find('i');
                    var memberName = $.trim($row.find('.users-member-name').text());

                    if ($button.prop('disabled')) {
                        return;
                    }

                    $button.prop('disabled', true).addClass('syncing');
                    $icon.addClass('fa-spin');
                    showSyncOverlay(memberName);

                    $.ajax({
                        method: 'POST',
                        url: $button.data('url'),
                        dataType: 'json',
                        headers: {
                            Accept: 'application/json',
                            'x-csrf-token': $('meta[name="csrf-token"]').attr('content')
                        }
                    }).done(function (response) {
                        var user = response.user || {};

                        $button
                            .removeClass('profile-pending')
                            .addClass('profile-ready')
                            .attr('title', 'Refresh Clubman details');

                        if (user.name) {
                            $row.find('.users-member-name').text(user.name);
                        }

                        $row.find('.users-member-email')
                            .text(user.email || 'No email')
                            .attr('title', user.email || '');
                        $row.find('.users-member-phone').text(user.phone_number || 'No phone number');
                        $row.find('.users-club-status').text(user.status || 'No Clubman status');
                        $row.find('.user-updated-value').text(user.updated_at || '—');

                        showSyncNotice(
                            'success',
                            'Profile synchronized',
                            response.message || 'The Clubman profile was saved successfully.'
                        );
                    }).fail(function (xhr) {
                        var message = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : 'The Clubman profile could not be updated. Please try again.';

                        showSyncNotice('error', 'Synchronization failed', message);
                    }).always(function () {
                        $button.prop('disabled', false).removeClass('syncing');
                        $icon.removeClass('fa-spin');
                        hideSyncOverlay();
                    });
                });
            });
        </script>
    @endcan

    @can('user_delete')
        <script>
            $(function () {
                var $selectAll = $('#select-all-users');
                var $rowCheckboxes = $('.user-row-checkbox');
                var $bulkDelete = $('#bulk-delete-users');
                var $selectedCount = $('#selected-user-count');

                function selectedIds() {
                    return $rowCheckboxes.filter(':checked').map(function () {
                        return this.value;
                    }).get();
                }

                function updateSelectionState() {
                    var selected = selectedIds();
                    var allSelected = $rowCheckboxes.length > 0 && selected.length === $rowCheckboxes.length;

                    $selectedCount.text(selected.length);
                    $bulkDelete.prop('disabled', selected.length === 0);
                    $selectAll.prop('checked', allSelected);
                    $selectAll.prop('indeterminate', selected.length > 0 && !allSelected);
                }

                $selectAll.on('change', function () {
                    $rowCheckboxes.prop('checked', this.checked);
                    updateSelectionState();
                });

                $rowCheckboxes.on('change', updateSelectionState);

                $bulkDelete.on('click', function () {
                    var ids = selectedIds();

                    if (!ids.length || !window.confirm({!! json_encode(trans('global.areYouSure')) !!})) {
                        return;
                    }

                    $bulkDelete.prop('disabled', true).addClass('is-loading');

                    $.ajax({
                        headers: {
                            'x-csrf-token': $('meta[name="csrf-token"]').attr('content')
                        },
                        method: 'POST',
                        url: {!! json_encode(route('admin.users.massDestroy')) !!},
                        data: {
                            ids: ids,
                            _method: 'DELETE'
                        }
                    }).done(function () {
                        window.location.reload();
                    }).fail(function () {
                        $bulkDelete.prop('disabled', false).removeClass('is-loading');
                        window.alert('The selected members could not be deleted. Please try again.');
                    });
                });
            });
        </script>
    @endcan
@endsection
