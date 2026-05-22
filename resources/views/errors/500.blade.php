@extends('layouts.public')

@section('title', 'Coś poszło nie tak')
@section('robots')<meta name="robots" content="noindex,nofollow">@endsection

@section('content')
    <div class="card">
        <h1>500 — coś poszło nie tak</h1>
        <p>Coś u nas pękło. Otrzymaliśmy już informację i sprawdzamy.</p>
        <p>Spróbuj ponownie za kilka chwil albo wróć do <a href="{{ route('home') }}">strony głównej</a>.</p>
    </div>
@endsection
