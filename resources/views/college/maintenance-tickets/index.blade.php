@extends('layouts.college')

@section('college-content')
@include('layouts.partials.page-kit')
@php
    $ticketTone = fn ($s) => match ($s) {
        'pending' => 'is-warn',
        'ongoing', 'in_progress' => 'is-info',
        'success', 'completed' => 'is-good',
        'replacement', 'failed', 'rejected' => 'is-bad',
        default => 'is-neutral',
    };
    $closedStatuses = ['success', 'replacement', 'failed', 'completed', 'rejected'];
@endphp

<div class="pk-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/new-avr.jpg') }}')">
        <div>
            <p class="pk-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z"/></svg>
                Repair &amp; maintenance
            </p>
            <h1>{{ $showCompleted ? 'Completed repair tasks' : 'Open repair requests' }}</h1>
            <p class="pk-hero-sub">{{ $showCompleted ? 'Review completed repair and maintenance work for your college.' : "Track your college's open repair requests to the administrators. Everyone in your college sees these tickets." }}</p>
        </div>
        <div class="pk-hero-actions">
            <div class="pk-stat is-dark pk-stat--glass pk-stat--hero u-minw-140">
                <div>
                    <p class="pk-stat-label u-mb-6">{{ $showCompleted ? 'Completed tasks' : 'Open requests' }}</p>
                    <p class="pk-stat-value">{{ $tickets->total() }}</p>
                </div>
            </div>
            @unless ($showCompleted)
            <a href="{{ route('college.maintenance-tickets.create') }}" class="pk-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                New request
            </a>
            @endunless
        </div>
    </section>

    <nav class="creq-tabs creq-tabs--views" aria-label="Repair request views">
        <a href="{{ route('college.maintenance-tickets.index') }}" class="creq-tab {{ !$showCompleted ? 'is-active' : '' }}" @if (!$showCompleted) aria-current="page" @endif>Open repair requests</a>
        <a href="{{ route('college.maintenance-tickets.completed') }}" class="creq-tab {{ $showCompleted ? 'is-active' : '' }}" @if ($showCompleted) aria-current="page" @endif>Completed repair tasks</a>
    </nav>

    @if ($tickets->isNotEmpty())
        <div class="pk-section-head">
            <h2 class="pk-section-title">{{ $showCompleted ? 'Completed tasks' : 'Open requests' }}</h2>
            <span class="pk-section-meta">Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</span>
        </div>
        <div class="ctk-grid">
            @foreach ($tickets as $ticket)
                @php $closed = in_array($ticket->status, $closedStatuses, true); @endphp
                <article class="ctk {{ $closed ? 'is-closed' : '' }}">
                    <div class="ctk-stub">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7a2 2 0 012-2h12a2 2 0 012 2v3a2 2 0 000 4v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3a2 2 0 000-4z"/></svg>
                        <span class="ctk-code">{{ $ticket->ticket_code }}</span>
                    </div>
                    <div class="ctk-body">
                        <div class="ctk-top">
                            <p class="ctk-subject">{{ $ticket->subject }}</p>
                            <span class="pk-pill {{ $ticketTone($ticket->status) }}">{{ ucfirst($ticket->status) }}</span>
                        </div>
                        <p class="ctk-meta">
                            <span class="{{ ($ticket->facility || $ticket->facility_other) ? '' : 'is-removed' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>{{ $ticket->facility?->name ?? $ticket->facility_other ?? 'Facility removed' }}</span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>Sent by {{ $ticket->requester_id === auth()->id() ? 'you' : ($ticket->requester?->name ?? 'a deleted account') }} · {{ $ticket->requested_at?->format('M d, Y') ?? $ticket->created_at->format('M d, Y') }}</span>
                        </p>
                        <div class="ctk-foot">
                            <a href="{{ route('college.maintenance-tickets.show', $ticket) }}" class="pk-row-link">
                                View
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="pk-pagination">{{ $tickets->links() }}</div>
    @else
        <div class="pk-empty">
            <span class="pk-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4 2.5-2.5z"/></svg></span>
            <h3>{{ $showCompleted ? 'No completed repair tasks yet' : 'No open repair requests' }}</h3>
            <p>{{ $showCompleted ? 'Completed repair work for your college will show up here.' : 'You have no open repair or maintenance requests.' }}</p>
            @unless ($showCompleted)
                <a href="{{ route('college.maintenance-tickets.create') }}" class="pk-btn u-mt-6">Report a repair</a>
            @endunless
        </div>
    @endif
</div>
@endsection
