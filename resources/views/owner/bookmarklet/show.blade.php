@extends('layouts.panel')

@section('title', 'Dodawanie prezentów z dowolnego sklepu')

@section('content')
    <h1>Bookmarklet „Dodaj do listy"</h1>
    <p style="color:#6b7280;">Przeciągnij poniższy przycisk na pasek zakładek przeglądarki. Gdy znajdziesz prezent w sklepie internetowym, kliknij zakładkę — otworzy się okienko z gotowym formularzem do dodania prezentu do Twojej listy.</p>

    <div class="card">
        <div style="text-align:center;padding:1.5rem 0;">
            <a class="btn" href='{!! $bookmarkletJs !!}' style="font-size:1.05rem;padding:.75rem 1.5rem;">
                ❤ Dodaj do DajPrezent.pl
            </a>
            <p style="color:#6b7280;font-size:.85rem;margin-top:1rem;">Przeciągnij przycisk powyżej do paska zakładek (nie klikaj na tej stronie).</p>
        </div>
    </div>

    <div class="card">
        <h2>Jak to działa?</h2>
        <ol style="line-height:1.7;color:#4b5563;">
            <li>Otwórz dowolny sklep internetowy z prezentem, który chcesz dodać.</li>
            <li>Kliknij zakładkę „Dodaj do DajPrezent.pl" na pasku przeglądarki.</li>
            <li>Otworzy się małe okienko z prefillowanym formularzem (tytuł, link, cena).</li>
            <li>Wybierz listę, popraw co trzeba i kliknij „Dodaj prezent".</li>
        </ol>
        <p style="color:#6b7280;font-size:.85rem;">Bookmarklet odczytuje meta tagi OpenGraph z bieżącej strony — nie przekazuje żadnych Twoich danych logowania ani historii. Skrypt jest zwykłym linkiem JavaScript, możesz go obejrzeć klikając prawym przyciskiem na zakładce.</p>
    </div>

    @if ($tenants->isEmpty())
        <div class="card flash flash-err" style="margin-top:1rem;">
            Nie masz jeszcze żadnej listy. Załóż listę i wróć tutaj.
        </div>
    @endif
@endsection
