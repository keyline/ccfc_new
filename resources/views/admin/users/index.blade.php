@extends('layouts.admin')

@section('title', 'Members')

@section('content')
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
                                        <strong>{{ $user->name ?: 'Unnamed member' }}</strong>
                                        <span>#{{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="users-member-code">{{ $user->user_code ?: '—' }}</span>
                            </td>
                            <td>
                                <div class="users-contact-cell">
                                    <span title="{{ $user->email }}">{{ $user->email ?: 'No email' }}</span>
                                    <small>{{ $user->phone_number_1 ?: 'No phone number' }}</small>
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
                                    @if($user->status)
                                        <small>{{ $user->status }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="users-updated-at">
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
                                        <a class="users-icon-action {{ $user->user_code_user_details_count > 0 ? 'profile-ready' : 'profile-pending' }}"
                                            href="{{ route('admin.saveUserJson', $user->user_code) }}"
                                            title="{{ trans('global.updatedetails') }}"
                                            aria-label="Update details for {{ $user->name ?: 'member' }}">
                                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                        </a>
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
