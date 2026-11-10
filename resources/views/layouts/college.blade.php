@extends('layouts.app')

{{-- Full-width portal; same shell as the admin portal (CLAUDE.md: navigation is identical across portals) --}}
@section('main-content-class', 'w-full')

@section('content')
@include('layouts.partials.portal-shell', [
    'roleLabel'      => 'College Staff · ' . (optional(auth()->user())->college_name ?? 'College not set'),
    'portalHeading'  => 'College Staff Portal',
    'dashboardRoute' => 'college.dashboard',
    'contentSection' => 'college-content',
    'sections' => [
        [
            'heading' => 'Overview',
            'always_open' => true,
            'links' => [
                ['label' => 'Dashboard', 'route' => 'college.dashboard', 'icon' => 'dashboard'],
            ],
        ],
        [
            'heading' => 'Bookings',
            'links' => [
                ['label' => 'Reservation calendar', 'route' => 'college.calendar', 'icon' => 'calendar'],
                ['label' => 'Reservations', 'route' => 'college.bookings.index', 'routes' => 'college.bookings.*', 'icon' => 'bookmark'],
            ],
        ],
        [
            'heading' => 'Requests',
            'links' => [
                ['label' => 'Facility requests', 'route' => 'college.requests.index', 'routes' => ['college.requests.index', 'college.requests.facilities.create', 'college.requests.facilities.store', 'college.requests.facilities.show', 'college.requests.facilities.cancel'], 'icon' => 'inbox'],
                ['label' => 'Repair requests', 'route' => 'college.maintenance-tickets.index', 'routes' => ['college.maintenance-tickets.index', 'college.maintenance-tickets.completed', 'college.maintenance-tickets.create', 'college.maintenance-tickets.store', 'college.maintenance-tickets.show', 'college.maintenance-tickets.messages', 'college.maintenance-tickets.messages.store'], 'icon' => 'wrench'],
            ],
        ],
        [
            'heading' => 'Team',
            'links' => [
                ['label' => 'Users', 'route' => 'college.users.index', 'routes' => 'college.users.*', 'icon' => 'users'],
            ],
        ],
    ],
])
@endsection
