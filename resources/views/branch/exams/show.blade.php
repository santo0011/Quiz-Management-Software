@extends('layouts.branch')

@section('title', $exam->title)
@section('page-title', 'Exam Details')

@section('content')
    @include('exams.partials.show', ['prefix' => 'branch'])
@endsection
