@extends('layouts.public')

@section('title', $title)
@section('meta_description', $title.' — DajPrezent.pl, baza wiedzy.')

@section('content')
    <article class="dp-card prose prose-slate max-w-none">
        <p class="text-xs text-dp-muted m-0">
            <a href="{{ route('public.help.index') }}" class="text-dp-purple-700 hover:underline">← Pomoc</a>
        </p>
        <h1 class="font-display text-2xl sm:text-3xl font-bold m-0 mt-2">{{ $title }}</h1>

        @include('public.help.articles.'.$slug)

        <hr class="my-6 border-slate-100">
        <p class="text-sm text-dp-muted m-0">
            Nie znalazłeś odpowiedzi? Napisz do nas na
            <a href="mailto:kontakt@dajprezent.pl" class="text-dp-purple-700 hover:underline">kontakt@dajprezent.pl</a>
            (odpowiedź w 1 dniu roboczym).
        </p>
    </article>
@endsection
