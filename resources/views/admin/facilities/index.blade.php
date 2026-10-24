@extends('layouts.admin')

@section('admin-content')
@include('layouts.partials.page-kit')
@php
    // Photo per known venue (matched on the facility name); the rest get an icon placeholder.
    $facilityPhotos = [
        'busalian'  => 'busalian-hall.jpg',
        'paghiusa'  => 'paghiusa-hall.jpg',
        'tiripunan' => 'tiripunan-hall.jpg',
        'grandstand'=> 'grand-stand.jpg',
        'trackoval' => 'grand-stand.jpg',
        'newavr'    => 'new-avr.jpg',
        'avr'       => 'avr.jpg',
    ];
    $photoFor = function ($name) use ($facilityPhotos) {
        $key = preg_replace('/[^a-z]/', '', strtolower((string) $name));
        foreach ($facilityPhotos as $needle => $file) {
            if (str_contains($key, $needle)) {
                return asset('img/facilities/' . $file);
            }
        }
        return null;
    };
@endphp

<div class="pk-page">
    <section class="pk-hero" style="--pk-img: url('{{ asset('img/facilities/busalian-hall.jpg') }}')">
        <div>
            <p class="pk-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg>
                Facilities
            </p>
            <h1>Facilities Management</h1>
            <p class="pk-hero-sub">Every venue in the system: who owns it, where it is, and whether it can be booked right now.</p>
        </div>
        <div class="pk-hero-actions">
            <a href="{{ route('admin.facilities.create') }}" class="pk-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Add New Facility
            </a>
        </div>
    </section>

    @if($facilities->count() > 0)
        <div class="pk-section-head">
            <h2 class="pk-section-title">All facilities</h2>
            <span class="pk-section-meta">Showing {{ $facilities->firstItem() }}–{{ $facilities->lastItem() }} of {{ $facilities->total() }}</span>
        </div>

        <div class="fac-grid">
            @foreach($facilities as $facility)
                @php
                    $ownerLabel = match ($facility->owner_type) {
                        'gsu'     => 'GSU',
                        'college' => 'College',
                        'org'     => 'Organization',
                        default   => ucfirst($facility->owner_type ?? 'Unknown'),
                    };
                    $status = $facility->availability_status ?? 'available';
                    $statusTone = match ($status) {
                        'available'   => 'is-good',
                        'maintenance' => 'is-warn',
                        'unavailable' => 'is-bad',
                        default       => 'is-neutral',
                    };
                    $photo = $photoFor($facility->name);
                @endphp
                <article class="fac-card {{ $facility->is_active === false ? 'fac-inactive' : '' }}">
                    <div class="fac-media">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="" loading="lazy">
                        @else
                            <div class="fac-placeholder" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 10h.01M15 10h.01"/></svg>
                            </div>
                        @endif
                        <span class="pk-tag">{{ $ownerLabel }}{{ $facility->owner_college ? ' · ' . $facility->owner_college : '' }}</span>
                        <span class="pk-pill {{ $statusTone }} fac-avail">{{ ucfirst($status) }}</span>
                    </div>
                    <div class="fac-body">
                        <h3 class="fac-name">{{ $facility->name }}</h3>
                        <p class="fac-loc">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg>
                            {{ $facility->location ?: 'No location set' }}
                        </p>
                        @if ($facility->description)
                            <p class="fac-desc">{{ Str::limit($facility->description, 90) }}</p>
                        @else
                            <p class="fac-desc is-empty">No description yet.</p>
                        @endif
                    </div>
                    <div class="fac-actions">
                        <a href="{{ route('admin.facilities.edit', $facility) }}" class="pk-row-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
                            Edit
                        </a>
                        <form action="{{ route('admin.facilities.destroy', $facility) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this facility?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="fac-delete" aria-label="Delete {{ $facility->name }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>
                                Delete
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pk-pagination">{{ $facilities->links() }}</div>
    @else
        <div class="pk-empty">
            <span class="pk-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/></svg></span>
            <h3>No facilities found</h3>
            <p>Add a venue so colleges and organizations can start booking it.</p>
            <a href="{{ route('admin.facilities.create') }}" class="pk-btn u-mt-6">Add Your First Facility</a>
        </div>
    @endif
</div>
@endsection
