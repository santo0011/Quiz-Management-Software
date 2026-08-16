@extends('layouts.admin')

@section('title', $exam->title)
@section('page-title', 'Exam Details')

@section('content')
    @include('exams.partials.show', ['prefix' => 'admin'])
@endsection
