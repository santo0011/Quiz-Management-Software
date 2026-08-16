@extends('layouts.admin')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    @include('results.partials.show', ['prefix' => 'admin'])
@endsection
