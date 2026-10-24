@extends('layouts.org')

@section('org-content')
@include('layouts.partials.page-kit')
@include('layouts.partials.ticket-detail', [
    'backUrl' => route('org.maintenance-tickets.index'),
])
@endsection
