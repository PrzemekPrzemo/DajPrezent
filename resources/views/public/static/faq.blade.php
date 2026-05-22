@extends('layouts.public')

@section('title', 'FAQ — najczęstsze pytania')

@section('content')
    <article class="card" style="text-align:left;line-height:1.6;">
        <h1>Najczęstsze pytania</h1>

        <h2>Czy gość, który zarezerwuje prezent, widoczny jest dla mnie?</h2>
        <p>Nie — to centralna obietnica DajPrezent.pl. Widzisz wyłącznie status („zarezerwowany", „otrzymany"). Tożsamość darczyńcy poznasz dopiero, gdy faktycznie wręczy Ci prezent. Adres e-mail osoby rezerwującej wymagamy wyłącznie po to, żeby zweryfikować, że to nie spam — wysyłamy do niej link aktywacyjny, ale ten adres nigdy do Ciebie nie trafia.</p>

        <h2>Czy mogę założyć kilka list?</h2>
        <p>Tak — pakiety <strong>Plus</strong> i wyżej pozwalają na wiele list. Każda ma osobny slug i osobne prezenty.</p>

        <h2>Co się stanie po wygaśnięciu pakietu?</h2>
        <p>Lista przechodzi w tryb prywatny — adres `dajprezent.pl/&lt;slug&gt;` zwraca informację o wygaśnięciu, ale Twoje dane i prezenty nie są kasowane przez 30 dni. W tym czasie możesz przedłużyć pakiet bez utraty zawartości.</p>

        <h2>Jak ochronić listę hasłem?</h2>
        <p>W pakietach <strong>Plus</strong> i wyższych: zaloguj się do panelu, otwórz Ustawienia listy i wpisz hasło. Goście będą musieli je podać przy wejściu na publiczną stronę.</p>

        <h2>Czy dostanę fakturę VAT?</h2>
        <p>Tak — faktura wystawiana jest automatycznie po opłaceniu pakietu i trafia do KSeF. Dostępna jest w panelu w sekcji „Faktury". Potrzebujesz faktury z innymi danymi nabywcy? Napisz na <a href="mailto:faktury@dajprezent.pl">faktury@dajprezent.pl</a>.</p>

        <h2>Czy mogę dodać prezent z dowolnego sklepu?</h2>
        <p>Tak — w panelu dostępny jest „Bookmarklet" (mały przycisk do paska zakładek). Kliknij go na stronie sklepu, a otworzy się okienko z gotowym do dodania prezentem (tytuł, link, cena pobrane z meta-tagów strony).</p>

        <h2>Pakiet ślubny — co dostaję dodatkowo?</h2>
        <p>Stronę ślubną z informacjami o uroczystości, formularzem RSVP (z preferencjami dietetycznymi w wersji Premium), galerią zdjęć po ślubie, mapą dojazdu, ochroną hasłem oraz generatorem zaproszeń PDF z QR. Funkcjonalność dostępna od fazy 2 systemu.</p>

        <h2>Czy DajPrezent.pl jest serwisem polskim?</h2>
        <p>Tak — operatorem jest Sendormeco Holding (NIP 525-28-66-457). Płatności obsługuje PayU, faktury wystawiamy przez KSeF, dane przechowujemy w Unii Europejskiej.</p>

        <h2>Jak skontaktować się z supportem?</h2>
        <p>E-mail: <a href="mailto:kontakt@dajprezent.pl">kontakt@dajprezent.pl</a>. Faktury: <a href="mailto:faktury@dajprezent.pl">faktury@dajprezent.pl</a>. RODO: <a href="mailto:rodo@dajprezent.pl">rodo@dajprezent.pl</a>.</p>
    </article>
@endsection
