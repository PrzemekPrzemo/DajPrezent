@extends('layouts.public')

@section('title', 'Pomoc — baza wiedzy')
@section('meta_description', 'Odpowiedzi na najczęstsze pytania o listy prezentów i strony ślubne na DajPrezent.pl.')

@section('content')
    <header class="text-center mb-10">
        <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">Baza wiedzy</h1>
        <p class="text-dp-muted mt-2 m-0 max-w-xl mx-auto">
            Krótkie odpowiedzi na typowe pytania. Nie znajdujesz swojego? Napisz
            na <a href="mailto:kontakt@dajprezent.pl" class="text-dp-purple-700 hover:underline">kontakt@dajprezent.pl</a> —
            odpowiemy w 1 dzień roboczy.
        </p>
    </header>

    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($articles as $slug => $title)
            <a href="{{ route('public.help.show', $slug) }}" class="dp-card hover:-translate-y-1 transition-transform duration-300 ease-dp block !text-inherit no-underline">
                <h2 class="font-display font-semibold text-base m-0">{{ $title }}</h2>
                <p class="text-xs text-dp-muted mt-2 m-0">Czytaj artykuł →</p>
            </a>
        @endforeach
    </div>
@endsection
