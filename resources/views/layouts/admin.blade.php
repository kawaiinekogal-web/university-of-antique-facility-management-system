@extends('layouts.app')

{{-- Full-width portal: app.blade.php reads 'main-content-class' (the old 'main-class' name was ignored) --}}
@section('main-content-class', 'w-full')

@section('content')
@php
    $adminSections = [
        [
            'heading' => 'Overview',
            'links' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
            ],
        ],
        [
            'heading' => 'Bookings',
            'links' => [
                ['label' => 'Reservation calendar', 'route' => 'admin.calendar', 'icon' => 'calendar'],
                ['label' => 'Reservations', 'route' => 'admin.bookings.index', 'routes' => ['admin.bookings.index', 'admin.bookings.edit', 'admin.bookings.update', 'admin.bookings.cancel', 'admin.bookings.create-direct'], 'icon' => 'bookmark'],
            ],
        ],
        [
            'heading' => 'Requests',
            'links' => [
                ['label' => 'Facility requests', 'route' => 'admin.forms.facilities.index', 'routes' => ['admin.forms.facilities.index', 'admin.forms.facilities.show', 'admin.forms.facilities.approve', 'admin.forms.facilities.disapprove', 'admin.forms.facilities.verify-payment', 'admin.forms.facilities.set-reservation', 'admin.forms.facilities.pdf'], 'icon' => 'approve'],
                ['label' => 'Repair requests', 'route' => 'admin.maintenance-tickets.index', 'routes' => ['admin.maintenance-tickets.index', 'admin.maintenance-tickets.completed', 'admin.maintenance-tickets.show', 'admin.maintenance-tickets.update', 'admin.maintenance-tickets.messages', 'admin.maintenance-tickets.messages.store'], 'icon' => 'wrench'],
            ],
        ],
        [
            'heading' => 'Facilities',
            'links' => [
                // Add/Edit facility open from this page (with a Back button), so they keep it highlighted
                ['label' => 'All facilities', 'route' => 'admin.facilities.index', 'routes' => 'admin.facilities.*', 'icon' => 'building'],
            ],
        ],
        [
            'heading' => 'Records',
            'links' => [
                ['label' => 'Record overview', 'route' => 'admin.monthly-records.index', 'icon' => 'records'],
                ['label' => 'Repair records', 'route' => 'admin.repair-records.index', 'icon' => 'chart'],
                ['label' => 'Audit trail', 'route' => 'admin.audit-logs.index', 'icon' => 'records'],
            ],
        ],
        [
            'heading' => 'Administration',
            'links' => [
                ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users'],
            ],
        ],
    ];
    // Facility and repair staff use the same shell with only the links their routes allow (see routes/web.php groups)
    $shellRole   = auth()->user()?->role;
    $repairLinks = ['admin.dashboard', 'admin.repair-records.index', 'admin.maintenance-tickets.index', 'admin.maintenance-tickets.completed'];
    $adminOnly   = ['admin.users.index', 'admin.users.create', 'admin.audit-logs.index'];
    $allowed = match ($shellRole) {
        'maintenance_staff' => fn ($route) => in_array($route, $repairLinks, true),
        'facility_staff'    => fn ($route) => $route === 'admin.dashboard' || ! in_array($route, [...$repairLinks, ...$adminOnly], true),
        default             => fn ($route) => true,
    };
    $adminSections = collect($adminSections)
        ->map(fn ($g) => ['heading' => $g['heading'], 'links' => array_values(array_filter($g['links'], fn ($l) => $allowed($l['route'])))])
        ->filter(fn ($g) => $g['links'])->values()->all();
@endphp
@include('layouts.partials.portal-shell', [
    'roleLabel'      => match ($shellRole) { 'facility_staff' => 'Facilities', 'maintenance_staff' => 'Repair & Maintenance', default => 'GSU Administrator' },
    'portalHeading'  => 'GSU Admin Portal',
    'dashboardRoute' => 'admin.dashboard',
    'contentSection' => 'admin-content',
    'sections'       => $adminSections,
])
@endsection
