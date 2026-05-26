@extends('layouts.public')

@section('title', $title)
@section('meta_description', $title.' — DajPrezent.pl, '.__('messages.help.h1').'.')

@section('content')
    <section class="max-w-3xl mx-auto px-4 pt-10 pb-12">
        <article class="dp-card prose prose-slate max-w-none">
            <p class="text-xs text-dp-muted m-0">
                <a href="{{ route('public.help.index') }}" class="text-dp-purple-700 hover:underline">
                    {{ __('messages.help.back_to_index') }}
                </a>
            </p>
            <h1 class="font-display text-2xl sm:text-3xl font-bold m-0 mt-2">{{ $title }}</h1>

            @include($partial ?? 'public.help.articles.'.$slug)

            <hr class="my-6 border-slate-100">
            <p class="text-sm text-dp-muted m-0">
                {!! __('messages.help.didnt_find', ['email' => '<a href="mailto:kontakt@dajprezent.pl" class="text-dp-purple-700 hover:underline">kontakt@dajprezent.pl</a>']) !!}
            </p>
        </article>
    </section>
@endsection
