@extends('layouts.public')

@section('title', __('messages.help.h1'))
@section('meta_description', __('messages.help.meta_description'))

@section('content')
    <section class="max-w-5xl mx-auto px-4 pt-16 pb-12">
        <header class="text-center mb-10">
            <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">{{ __('messages.help.h1') }}</h1>
            <p class="text-dp-muted mt-3 m-0 max-w-xl mx-auto">
                {!! __('messages.help.lead', ['email' => '<a href="mailto:kontakt@dajprezent.pl" class="text-dp-purple-700 hover:underline">kontakt@dajprezent.pl</a>']) !!}
            </p>
        </header>

        <div class="grid sm:grid-cols-2 gap-4">
            @foreach ($articles as $slug => $title)
                <a href="{{ route('public.help.show', $slug) }}"
                   class="dp-card hover:-translate-y-1 transition-transform duration-300 ease-dp block !text-inherit no-underline">
                    <h2 class="font-display font-semibold text-base m-0">{{ $title }}</h2>
                    <p class="text-xs text-dp-purple-700 mt-2 m-0">{{ __('messages.help.read_article') }} →</p>
                </a>
            @endforeach
        </div>
    </section>
@endsection
