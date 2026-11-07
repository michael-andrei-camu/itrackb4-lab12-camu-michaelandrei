@extends('layouts.app')

@section('title', 'Server Error')

@section('content')
    <div class="container text-center py-5">
        <h1 class="display-4 text-warning">500 - Server Error</h1>
        <p class="lead">Something went wrong on our end. We are working to fix it.</p>
        <a href="{{ route('books.catalog') }}" class="btn btn-secondary">Return to Home</a>
    </div>
@endsection
