@extends('layouts.student')

@section('title', $attempt->exam->title)
@section('page-title', 'Online Exam')

@section('content')
    <div
        id="student-exam-root"
        data-state-url="{{ route('student.attempts.state', $attempt) }}"
        data-answer-url="{{ route('student.attempts.answer', $attempt) }}"
        data-submit-url="{{ route('student.attempts.submit', $attempt) }}"
    ></div>
@endsection
