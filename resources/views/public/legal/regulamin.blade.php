@extends('layouts.public')

@section('title', 'Regulamin')
@section('meta_description', 'Regulamin serwisu DajPrezent.pl — zasady korzystania, pakiety, anonimowe rezerwacje, RODO i odstąpienia.')

@section('content')
    <section class="max-w-3xl mx-auto px-4 pt-12 pb-16">
        <header class="text-center mb-8">
            <span class="dp-chip dp-chip-pink mb-3 inline-block">⚖️ Dokument prawny</span>
            <h1 class="font-display text-3xl sm:text-4xl font-bold m-0">Regulamin serwisu DajPrezent.pl</h1>
            <p class="text-sm text-dp-muted mt-2 m-0">Wersja 1.0 — obowiązuje od dnia publikacji</p>
        </header>

        <article class="dp-card prose prose-slate max-w-none prose-h2:font-display prose-h2:text-lg prose-h2:mt-6 prose-h2:mb-2 prose-h2:text-dp-navy prose-p:text-dp-navy/85 prose-p:leading-relaxed prose-a:text-dp-purple-700 prose-strong:text-dp-navy">

            <h2>§ 1. Definicje</h2>
            <p>
                <strong>Usługodawca</strong> — <strong>Sendormeco Holding sp. z o.o.</strong> z siedzibą w Warszawie przy ul. Złotej 75A/7, 00-819 Warszawa, wpisana do rejestru przedsiębiorców prowadzonego przez Sąd Rejonowy dla m.st. Warszawy w Warszawie, XII Wydział Gospodarczy KRS pod numerem KRS <strong>0000906110</strong>, NIP <strong>5252866457</strong>, REGON <strong>389194801</strong>, kapitał zakładowy 5&nbsp;000&nbsp;zł, prowadząca serwis DajPrezent.pl.<br>
                <strong>Użytkownik</strong> — pełnoletnia osoba fizyczna lub przedsiębiorca korzystający z serwisu.<br>
                <strong>Pakiet</strong> — wykupiona przez Użytkownika opcja korzystania z funkcjonalności serwisu w określonym zakresie i czasie.<br>
                <strong>Lista</strong> — utworzona przez Użytkownika lista prezentów / strona ślubna dostępna pod indywidualnym adresem <code>dajprezent.pl/&lt;slug&gt;</code>.
            </p>

            <h2>§ 2. Zakres usługi</h2>
            <p>Serwis DajPrezent.pl umożliwia Użytkownikom tworzenie i udostępnianie list prezentów z możliwością rezerwacji przez osoby trzecie bez konieczności zakładania konta. Funkcjonalności rozszerzone (m.in. pakiet ślubny, eksport CSV, ochrona hasłem) są dostępne w zależności od wybranego Pakietu.</p>

            <h2>§ 3. Zawarcie umowy</h2>
            <p>Umowa zostaje zawarta z chwilą skutecznego zaksięgowania płatności za Pakiet. Dla pakietu Free umowa zawierana jest z chwilą rejestracji konta i akceptacji regulaminu.</p>

            <h2>§ 4. Płatności i faktury</h2>
            <p>Płatności realizowane są za pośrednictwem operatora PayU S.A. Faktura VAT wystawiana jest automatycznie po opłaceniu Pakietu, w systemie KSeF, na dane podane przy zakupie. W przypadku potrzeby zmiany danych nabywcy prosimy o kontakt na <a href="mailto:faktury@dajprezent.pl">faktury@dajprezent.pl</a>.</p>

            <h2>§ 5. Czas trwania Pakietu</h2>
            <ul>
                <li>Pakiety standardowe — ważne 9 miesięcy od dnia zakupu;</li>
                <li>Pakiet Free — ważny 30 dni;</li>
                <li>Pakiet ślubny — ważny 12 miesięcy z możliwością przedłużenia.</li>
            </ul>

            <h2>§ 6. Prawo odstąpienia</h2>
            <p>Konsumentowi przysługuje prawo odstąpienia od umowy w terminie 14 dni od jej zawarcia — pod warunkiem, że Lista nie została udostępniona publicznie (nie ma na niej żadnej rezerwacji). Po pierwszej publicznej rezerwacji prezentu prawo odstąpienia wygasa zgodnie z art. 38 pkt 1 ustawy o prawach konsumenta.</p>

            <h2>§ 7. Zasady korzystania</h2>
            <p>Użytkownik zobowiązuje się do korzystania z serwisu zgodnie z prawem i dobrymi obyczajami. Zabronione jest umieszczanie treści naruszających prawa osób trzecich, treści obraźliwych, pornograficznych lub propagujących przemoc.</p>

            <h2>§ 8. Wybór adresu (slug)</h2>
            <p>Wybór adresu Listy odbywa się na zasadzie „kto pierwszy ten lepszy". Adresy zawierające zarezerwowane słowa kluczowe (np. admin, login, api) oraz wulgaryzmy nie są dostępne. Usługodawca zastrzega sobie prawo zmiany adresu, jeżeli narusza on prawa osób trzecich lub zasady regulaminu.</p>

            <h2>§ 9. Anonimowość osób rezerwujących</h2>
            <p>Adres e-mail osoby rezerwującej prezent <strong>nie</strong> jest udostępniany właścicielowi Listy. Właściciel widzi wyłącznie status („zarezerwowany", „otrzymany"). Stanowi to istotny element usługi.</p>

            <h2>§ 10. Reklamacje</h2>
            <p>Reklamacje należy zgłaszać na adres <a href="mailto:kontakt@dajprezent.pl">kontakt@dajprezent.pl</a>. Reklamacje rozpatrywane są w terminie 14 dni roboczych.</p>

            <h2>§ 11. Dane osobowe</h2>
            <p>Zasady przetwarzania danych osobowych opisuje <a href="{{ route('public.legal.privacy') }}">Polityka prywatności</a>. Dla pakietów weselnych obowiązuje dodatkowo umowa powierzenia przetwarzania danych (DPA) zawierana indywidualnie po zakupie.</p>

            <h2>§ 12. Zmiany regulaminu</h2>
            <p>Usługodawca może wprowadzać zmiany w regulaminie. O zmianach Użytkownik zostanie poinformowany pocztą e-mail z 14-dniowym wyprzedzeniem. Dalsze korzystanie z serwisu po wejściu zmian w życie oznacza ich akceptację.</p>

            <h2>§ 13. Postanowienia końcowe</h2>
            <p>W sprawach nieuregulowanych stosuje się przepisy prawa polskiego. Spory rozstrzyga sąd właściwy dla siedziby Usługodawcy.</p>
        </article>

        <div class="mt-6 text-center">
            <a href="{{ route('public.legal.privacy') }}" class="text-sm text-dp-purple-700 font-semibold hover:underline">
                Polityka prywatności →
            </a>
        </div>
    </section>
@endsection
