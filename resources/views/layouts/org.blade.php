@extends('layouts.app')

{{-- Full-width portal; same shell as the admin portal (CLAUDE.md: navigation is identical across portals) --}}
@section('main-content-class', 'w-full')

@section('content')
@include('layouts.partials.portal-shell', [
    'roleLabel'      => 'Organization · ' . (optional(auth()->user())->organization_name ?? 'Not set'),
    'portalHeading'  => 'Organization Staff Portal',
    'dashboardRoute' => 'org.dashboard',
    'contentSection' => 'org-content',
    'sections' => [
        [
            'heading' => 'Overview',
            'always_open' => true,
            'links' => [
                ['label' => 'Dashboard', 'route' => 'org.dashboard', 'icon' => 'dashboard'],
            ],
        ],
        [
            'heading' => 'Bookings',
            'links' => [
                ['label' => 'Reservation calendar', 'route' => 'org.bookings.calendar', 'icon' => 'calendar'],
                ['label' => 'Reservations', 'route' => 'org.bookings.index', 'routes' => 'org.bookings.index', 'icon' => 'bookmark'],
            ],
        ],
        [
            'heading' => 'Requests & support',
            'links' => [
                ['label' => 'My requests', 'route' => 'org.requests.facilities.index', 'routes' => 'org.requests.facilities.*', 'icon' => 'inbox'],
                ['label' => 'New request', 'route' => 'org.requests.facilities.create', 'routes' => 'org.requests.facilities.create', 'icon' => 'file-plus'],
            ],
        ],
        [
            'heading' => 'Team',
            'links' => [
                ['label' => 'Users', 'route' => 'org.users.index', 'routes' => 'org.users.*', 'icon' => 'users'],
            ],
        ],
    ],
])
@endsection
