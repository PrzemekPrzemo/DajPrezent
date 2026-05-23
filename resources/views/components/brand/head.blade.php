@props([
    'title' => null,
    'description' => null,
    'ogTitle' => null,
    'ogDescription' => null,
    'ogImage' => null,
    'robots' => 'index,follow',
])

@php
    $title = $title ?: null;
    $displayTitle = $title ? $title.' — DajPrezent.pl' : 'DajPrezent.pl — prezenty od serca, bez stresu';
    $meta = $description ?? 'Stwórz listę wymarzonych prezentów lub stronę ślubną z RSVP. Bliscy zarezerwują anonimowo, Ty zobaczysz tylko status — kto, dowiesz się dopiero przy rozpakowywaniu.';
    $resolvedOgTitle = $ogTitle ?? $title ?? 'DajPrezent.pl';
    $resolvedOgDescription = $ogDescription ?? $description ?? 'Stwórz wymarzoną listę prezentów i podziel się nią z bliskimi.';
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $displayTitle }}</title>
<meta name="description" content="{{ $meta }}">
<meta name="theme-color" content="#4F46E5">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ url()->current() }}">

<meta property="og:site_name" content="DajPrezent.pl">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $resolvedOgTitle }}">
<meta property="og:description" content="{{ $resolvedOgDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
@endif

<link rel="icon" type="image/svg+xml" href="{{ asset('brand/favicon.svg') }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
