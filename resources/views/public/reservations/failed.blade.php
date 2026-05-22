@extends('layouts.public')

@section('title', 'Coś poszło nie tak')

@section('content')
    <div class="card">
        <h1>Coś poszło nie tak</h1>
        <p>{{ $message }}</p>
    </div>
@endsection
