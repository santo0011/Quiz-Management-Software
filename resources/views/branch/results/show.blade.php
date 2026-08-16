@extends('layouts.branch')

@section('title', 'Result Details')
@section('page-title', 'Result Details')

@section('content')
    @include('results.partials.show', ['prefix' => 'branch'])
@endsection
