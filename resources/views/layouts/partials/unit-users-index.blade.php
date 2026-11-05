{{-- Team list for the College Staff and Organization portals.
     Expects: $users, $prefix ('college' | 'org'), $unitName, $unitNoun ('college' | 'organization'),
              $canManage (signed-in user is the head), $headIds, $primaryId, $headTitle ('Dean' | 'Governor') --}}
@include('layouts.partials.page-kit')

<div class="pk-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/new-avr.jpg') }}')">
        <div>
            <h1>Users</h1>
            <p class="pk-hero-sub">People who handle the system for {{ $unitName ?: 'your ' . $unitNoun }}. Accounts here only see your {{ $unitNoun }}'s side.</p>
        </div>
        <div class="pk-hero-actions">
            <div class="pk-stat is-dark pk-stat--glass pk-stat--hero u-minw-150">
                <div>
                    <p class="pk-stat-label u-mb-6">Accounts</p>
                    <p class="pk-stat-value">{{ $users->count() }}</p>
                </div>
            </div>
            @if ($canManage && $seatsLeft > 0)
                <a href="{{ route($prefix . '.users.create') }}" class="pk-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0114 0M19 8v6M16 11h6"/></svg>
                    Add user
                </a>
            @elseif ($canManage)
                <span class="pk-btn uu-btn-off" aria-disabled="true" title="Deactivate an account to add someone else">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                    Limit reached
                </span>
            @endif
        </div>
    </section>

    <div class="pk-section-head">
        <h2 class="pk-section-title">{{ $unitName ?: 'Your ' . $unitNoun }}</h2>
        <span class="pk-section-meta">{{ $users->count() === 1 ? '1 account' : $users->count() . ' accounts' }} · {{ $maxAccounts - $seatsLeft }} of {{ $maxAccounts }} added accounts used</span>
    </div>

    @if ($users->isNotEmpty())
        <div class="pk-table-wrap">
            <table class="pk-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Position</th>
                        <th>Added</th>
                        <th class="uu-actions-col"><span class="u-sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        @php
                            $initials = collect(preg_split('/\s+/', trim((string) $user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                            $isMe     = $user->id === auth()->id();
                            $isHead   = in_array($user->id, $headIds, true);
                            $canEdit  = $canManage || $isMe;
                            $canDel   = $canManage && !$isMe && $user->id !== $primaryId;
                        @endphp
                        <tr>
                            <td>
                                <span class="pk-person">
                                    <span class="pk-avatar usr-avatar {{ $isHead ? 'is-admin' : '' }}" aria-hidden="true">{{ $initials ?: '?' }}</span>
                                    <span class="u-minw-0">
                                        <span class="pk-strong">{{ $user->name }}@if ($isMe) <span class="uu-you">You</span>@endif</span>
                                        <span class="usr-email">{{ $user->email }}</span>
                                        @unless ($user->is_active)<span class="uu-inactive">Deactivated</span>@endunless
                                    </span>
                                </span>
                            </td>
                            <td>
                                <span class="{{ $user->position ? 'usr-unit' : 'uu-empty' }}">{{ $user->position ?: 'Not set' }}</span>
                                @if ($isHead)<span class="uu-head" title="Can add, edit, deactivate and activate accounts">{{ $prefix === 'college' ? 'College Admin' : 'Head' }}</span>@endif
                            </td>
                            <td class="pk-nowrap">{{ $user->created_at?->format('M d, Y') ?? '-' }}</td>
                            <td class="uu-actions-col">
                                <div class="uu-actions">
                                    @if ($canEdit)
                                        <a href="{{ route($prefix . '.users.edit', $user) }}" class="uu-act" aria-label="Edit {{ $user->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
                                            Edit
                                        </a>
                                    @endif
                                    @if ($canDel && $user->is_active)
                                        <form method="POST" action="{{ route($prefix . '.users.deactivate', $user) }}" data-confirm="Deactivate {{ $user->name }}'s account? Future facility requests and upcoming bookings will be cancelled; past and completed activity will remain unchanged." data-confirm-title="Deactivate account?" data-confirm-action="Deactivate account" data-confirm-tone="danger">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="uu-act is-danger" aria-label="Deactivate {{ $user->name }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></svg>
                                                Deactivate
                                            </button>
                                        </form>
                                    @elseif ($canDel)
                                        <form method="POST" action="{{ route($prefix . '.users.activate', $user) }}" data-confirm="Activate {{ $user->name }}'s account? They can sign in again. Requests cancelled when the account was deactivated stay cancelled." data-confirm-title="Activate account?" data-confirm-action="Activate account">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="uu-act is-good" aria-label="Activate {{ $user->name }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
                                                Activate
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
        <p class="pk-help uu-note">
            @if ($canManage)
                Besides your own account, you can have up to {{ $maxAccounts }} active accounts; deactivating one frees a slot.
                @if ($prefix === 'college')
                    As College Admin, you can add, edit, deactivate and activate subaccounts for your college. Your own account cannot deactivate itself; GSU access is not needed to manage college subaccounts.
                @else
                    As the head of your {{ $unitNoun }}, you can add, edit, deactivate and activate accounts. Associated records are preserved. The original {{ $headTitle }} account can only be deactivated by the GSU admin.
                @endif
            @else
                @if ($prefix === 'college')
                    Only the designated College Admin can add, edit, deactivate or activate college subaccounts. You can edit your own account.
                @else
                    Only the {{ $headTitle }} can add, edit, deactivate or activate accounts. You can edit your own account.
                @endif
            @endif
        </p>
    @else
        <div class="pk-empty">
            <span class="pk-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0116 0"/></svg></span>
            <h3>No users yet</h3>
            <p>Add the people who will handle the system for your {{ $unitNoun }}.</p>
        </div>
    @endif
    <style>
        .uu-inactive { display: inline-block; margin-top: 4px; padding: 2px 7px; border-radius: 999px; background: #F1F5F9; color: #475569; font-size: 11px; font-weight: 700; }
        .uu-btn-off { opacity: .6; cursor: not-allowed; }
        .uu-act.is-good { color: #047857 !important; border-color: #A7F3D0; }
        .uu-act.is-good:hover { border-color: #047857; background: #ECFDF5; }
    </style>
</div>
