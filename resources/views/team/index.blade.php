@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Team & Privileges</h4>
            <p class="text-muted mb-0">{{ $currentCount }} / {{ $maxUsers == 999 ? '∞' : $maxUsers }} users · Named teams can unlock modules like Live Chat</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#createTeamModal">
                + New Team
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                + Invite Member
            </button>
        </div>
    </div>

    {{-- Named Teams --}}
    <div class="card stat-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0">Teams</h6>
                    <p class="text-muted small mb-0">Connect a team to modules so members can work as agents (e.g. Live Chat).</p>
                </div>
            </div>

            @forelse($teams as $team)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <div class="fw-semibold">{{ $team->name }}</div>
                            @if($team->description)
                                <div class="text-muted small">{{ $team->description }}</div>
                            @endif
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                @forelse($team->modules as $mod)
                                    <span class="badge bg-info text-dark">{{ $mod->module_key }}</span>
                                @empty
                                    <span class="text-muted small">No modules linked</span>
                                @endforelse
                            </div>
                            <div class="mt-2 small text-muted">
                                Members:
                                @forelse($team->users as $member)
                                    {{ $member->name }}{{ !$loop->last ? ',' : '' }}
                                @empty
                                    none
                                @endforelse
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                data-bs-target="#editTeamModal{{ $team->id }}">Edit</button>
                            <form method="POST" action="{{ route('team.groups.destroy', $team) }}"
                                onsubmit="return confirm('Delete this team?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Edit Team Modal --}}
                <div class="modal fade" id="editTeamModal{{ $team->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('team.groups.update', $team) }}">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Team</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Team name</label>
                                        <input type="text" class="form-control" name="name" required
                                            value="{{ $team->name }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Description</label>
                                        <input type="text" class="form-control" name="description"
                                            value="{{ $team->description }}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Connected modules</label>
                                        @foreach($modules as $module)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="module_keys[]"
                                                    value="{{ $module->key }}" id="edit-mod-{{ $team->id }}-{{ $module->key }}"
                                                    @checked($team->modules->contains('module_key', $module->key))>
                                                <label class="form-check-label"
                                                    for="edit-mod-{{ $team->id }}-{{ $module->key }}">
                                                    {{ $module->name }}
                                                    @if($module->key === 'chat')
                                                        <span class="text-muted small">(members can act as live chat agents)</span>
                                                    @endif
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-medium">Members</label>
                                        @foreach($users->where('status', 'active') as $member)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="user_ids[]"
                                                    value="{{ $member->id }}" id="edit-user-{{ $team->id }}-{{ $member->id }}"
                                                    @checked($team->users->contains('id', $member->id))>
                                                <label class="form-check-label"
                                                    for="edit-user-{{ $team->id }}-{{ $member->id }}">
                                                    {{ $member->name }}
                                                    <span class="text-muted small">({{ $member->email }})</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Team</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No teams yet. Create one and link it to Live Chat so members can take chats.</p>
            @endforelse
        </div>
    </div>

    {{-- Users Table --}}
    <div class="table-card mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Teams</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                    style="width:32px;height:32px;font-size:0.75rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                {{ $user->name }}
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->role === 'owner')
                                <span class="badge bg-primary">Owner</span>
                            @elseif($user->role === 'admin')
                                <span class="badge bg-info text-dark">Admin</span>
                            @else
                                <span class="badge bg-secondary">Member</span>
                            @endif
                        </td>
                        <td>
                            @forelse($user->teams as $ut)
                                <span class="badge bg-light text-dark border">{{ $ut->name }}</span>
                            @empty
                                <span class="text-muted small">—</span>
                            @endforelse
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if(!$user->isOwner())
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#privilegesModal{{ $user->id }}">Privileges</button>
                                @endif
                                @if(auth()->user()->isOwner() && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('team.changeRole', $user) }}" class="d-inline">
                                        @csrf @method('PUT')
                                        <select name="role" onchange="this.form.submit()" class="form-select form-select-sm"
                                            style="width:auto;">
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="member" {{ $user->role === 'member' ? 'selected' : '' }}>Member</option>
                                            <option value="owner" {{ $user->role === 'owner' ? 'selected' : '' }}>Owner</option>
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('team.toggleStatus', $user) }}" class="d-inline">
                                        @csrf @method('PUT')
                                        <button type="submit"
                                            class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @elseif(auth()->user()->isAdmin() && $user->role === 'member' && $user->id !== auth()->id())
                                    <form method="POST" action="{{ route('team.toggleStatus', $user) }}" class="d-inline">
                                        @csrf @method('PUT')
                                        <button type="submit"
                                            class="btn btn-sm {{ $user->status === 'active' ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach($users as $user)
        @if(!$user->isOwner())
            <div class="modal fade" id="privilegesModal{{ $user->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('team.privileges', $user) }}">
                            @csrf @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Privileges — {{ $user->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">
                                    Grant Live Chat Agent so this user can take chats, or add them to a team linked to the chat module.
                                </p>
                                @php $userPrivs = $user->privilegeList(); @endphp
                                @foreach($privilegeGroups as $group => $keys)
                                    <div class="mb-3">
                                        <div class="fw-semibold mb-2">{{ $group }}</div>
                                        @foreach($keys as $key)
                                            @php
                                                $locked = !auth()->user()->isOwner()
                                                    && in_array($key, [\App\Support\Privileges::BILLING_MANAGE, \App\Support\Privileges::TEAM_MANAGE], true);
                                            @endphp
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="privileges[]"
                                                    value="{{ $key }}" id="priv-{{ $user->id }}-{{ $key }}"
                                                    @checked(in_array($key, $userPrivs, true))
                                                    @disabled($locked)>
                                                <label class="form-check-label" for="priv-{{ $user->id }}-{{ $key }}">
                                                    {{ $privilegeLabels[$key] ?? $key }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Privileges</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Pending Invites --}}
    @if($invites->count() > 0)
        <div class="card stat-card">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3">Pending Invites</h6>
                @foreach($invites as $invite)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $invite->email }}</strong>
                            <span class="badge bg-secondary ms-2">{{ ucfirst($invite->role) }}</span>
                        </div>
                        <span class="text-muted small">Expires {{ $invite->expires_at->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Create Team Modal --}}
    <div class="modal fade" id="createTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('team.groups.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Team</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Team name</label>
                            <input type="text" class="form-control" name="name" required placeholder="Support Agents">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <input type="text" class="form-control" name="description" placeholder="Handles live chat">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Connected modules</label>
                            @foreach($modules as $module)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="module_keys[]"
                                        value="{{ $module->key }}" id="create-mod-{{ $module->key }}"
                                        @checked($module->key === 'chat')>
                                    <label class="form-check-label" for="create-mod-{{ $module->key }}">
                                        {{ $module->name }}
                                        @if($module->key === 'chat')
                                            <span class="text-muted small">(members can act as live chat agents)</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Members</label>
                            @foreach($users->where('status', 'active') as $member)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="user_ids[]"
                                        value="{{ $member->id }}" id="create-user-{{ $member->id }}">
                                    <label class="form-check-label" for="create-user-{{ $member->id }}">
                                        {{ $member->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Invite Modal --}}
    <div class="modal fade" id="inviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('team.invite') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Invite Team Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Email Address</label>
                            <input type="email" class="form-control" name="email" required
                                placeholder="colleague@company.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Role</label>
                            <select class="form-select" name="role">
                                <option value="member">Member</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Invite</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
