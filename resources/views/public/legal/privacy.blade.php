@extends('layouts.public')

@section('title', 'Polityka prywatności')

@section('content')
    <article class="card" style="text-align:left;line-height:1.6;">
        <h1>Polityka prywatności DajPrezent.pl</h1>
        <p style="color:#6b7280;font-size:.85rem;">Wersja 1.0 (draft do weryfikacji prawnej).</p>

        <p style="background:#fff7ed;color:#92400e;padding:.75rem 1rem;border-radius:.5rem;font-size:.9rem;">
            ⚠️ Wzór polityki przygotowany wewnętrznie. Przed publikacją produkcyjną wymaga weryfikacji przez radcę prawnego / IOD.
        </p>

        <h2>1. Administrator danych</h2>
        <p>
            Administratorem Twoich danych osobowych jest
            <strong>Sendormeco Holding sp. z o.o.</strong> z siedzibą w Warszawie
            (ul. Złota 75A/7, 00-819 Warszawa), wpisana do KRS pod numerem
            0000906110, NIP 5252866457, REGON 389194801. Serwis dajprezent.pl
            jest prowadzony przez tę spółkę. W sprawach ochrony danych
            skontaktuj się z nami pod adresem
            <a href="mailto:rodo@dajprezent.pl">rodo@dajprezent.pl</a>.
        </p>

        <h2>2. Jakie dane zbieramy</h2>
        <ul>
            <li><strong>Konto właściciela listy:</strong> imię, adres e-mail, hash hasła, język interfejsu.</li>
            <li><strong>Lista i prezenty:</strong> nazwa listy, slug, zdjęcia prezentów, linki do sklepów, opisy.</li>
            <li><strong>Rezerwacja prezentu (gość):</strong> adres e-mail (przechowywany wewnętrznie, NIE udostępniany właścicielowi listy), opcjonalnie imię, znacznik weryfikacji, adres IP (na potrzeby anty-spam, retencja 30 dni).</li>
            <li><strong>Dane do faktury:</strong> imię i nazwisko / firma, NIP, adres.</li>
            <li><strong>Dane techniczne:</strong> logi serwera, identyfikator sesji (cookie techniczne), wybór języka.</li>
        </ul>

        <h2>3. Cele i podstawy prawne</h2>
        <ul>
            <li>Świadczenie usługi (art. 6 ust. 1 lit. b RODO) — utrzymanie konta, listy, obsługa rezerwacji.</li>
            <li>Wystawianie faktur i rozliczenia podatkowe (art. 6 ust. 1 lit. c RODO).</li>
            <li>Bezpieczeństwo serwisu i przeciwdziałanie nadużyciom (art. 6 ust. 1 lit. f RODO — uzasadniony interes).</li>
            <li>Marketing własny (np. newsletter) — wyłącznie po dobrowolnej zgodzie (art. 6 ust. 1 lit. a RODO).</li>
        </ul>

        <h2>4. Twoje prawa</h2>
        <p>Przysługuje Ci prawo dostępu, sprostowania, usunięcia, ograniczenia, przenoszenia danych oraz sprzeciwu wobec przetwarzania. W każdej chwili możesz wycofać udzielone zgody.</p>
        <p>Masz również prawo wniesienia skargi do Prezesa UODO (ul. Stawki 2, 00-193 Warszawa).</p>

        <h2>5. Okres przechowywania</h2>
        <p>Dane konta przechowujemy przez okres jego aktywności i 30 dni po jego usunięciu. Faktury archiwizujemy przez okres wymagany przepisami podatkowymi (5 lat). Logi bezpieczeństwa przechowujemy przez 12 miesięcy.</p>

        <h2>6. Odbiorcy danych (subprocesorzy)</h2>
        <p>Dane mogą być przekazywane:</p>
        <ul>
            <li>operatorowi płatności PayU S.A. (osobny administrator) — wyłącznie w zakresie niezbędnym do realizacji płatności;</li>
            <li>dostawcy infrastruktury (VPS Plesk) — przechowywanie danych w UE;</li>
            <li>dostawcy poczty transakcyjnej (Postmark / Mailgun — finalny wybór zostanie tu wpisany przed publikacją);</li>
            <li>dostawcy storage zdjęć (S3-zgodny w UE);</li>
            <li>KSeF (Ministerstwo Finansów) — w zakresie wymaganym do wystawienia e-faktury.</li>
        </ul>

        <h2>7. Pliki cookie</h2>
        <p>Używamy wyłącznie ciasteczek niezbędnych do działania serwisu (sesja, ochrona CSRF, język). Nie używamy ciasteczek śledzących ani reklamowych firm trzecich. Statystyki ruchu zbieramy w sposób anonimowy (Plausible — bez identyfikatorów osobowych).</p>

        <h2>8. Anonimowość rezerwacji</h2>
        <p>Adres e-mail osoby rezerwującej prezent NIE jest udostępniany właścicielowi listy. Element ten stanowi techniczne i organizacyjne zabezpieczenie wymagane przez art. 32 RODO.</p>

        <h2>9. Zmiany polityki</h2>
        <p>O wszelkich zmianach informujemy z 14-dniowym wyprzedzeniem na adres e-mail powiązany z kontem.</p>
    </article>
@endsection
