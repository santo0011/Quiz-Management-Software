@extends('layouts.teacher')

@section('title', $exam->title)
@section('page-title', 'Exam Details')

@section('content')
    @include('exams.partials.show', ['prefix' => 'teacher'])
@endsection
