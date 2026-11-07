@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
    <div class="container text-center py-5">
        <h1 class="display-4 text-danger">404 - Page Not Found</h1>
        <p class="lead">The book or page you are looking for does not exist in our directory.</p>
        <a href="{{ route('books.catalog') }}" class="btn btn-primary">Back to Catalog</a>
    </div>
@endsection
