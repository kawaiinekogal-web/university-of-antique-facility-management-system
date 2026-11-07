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
            'heading' => 'Requests & support',
            'links' => [
                ['label' => 'My requests', 'route' => 'college.requests.index', 'routes' => 'college.requests.*', 'icon' => 'inbox'],
                ['label' => 'New request', 'route' => 'college.requests.facilities.create', 'routes' => 'college.requests.facilities.create', 'icon' => 'file-plus'],
                ['label' => 'Repair & maintenance', 'route' => 'college.maintenance-tickets.index', 'routes' => 'college.maintenance-tickets.*', 'icon' => 'wrench'],
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
