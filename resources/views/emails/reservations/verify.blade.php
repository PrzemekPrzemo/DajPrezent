<x-mail::message>
# Potwierdź swoją rezerwację

Cześć{{ $reservation->guest_name ? ', '.$reservation->guest_name : '' }}!

Otrzymaliśmy zgłoszenie rezerwacji prezentu z Twojego adresu e-mail.
Aby ją aktywować, kliknij poniższy przycisk w ciągu **{{ $ttlMinutes }} minut**.

<x-mail::button :url="$verifyUrl">
Potwierdzam rezerwację
</x-mail::button>

Jeśli to nie Ty, **zignoruj tę wiadomość** — rezerwacja wygaśnie automatycznie i nikt nie zobaczy Twojego e-maila.

Jeśli zmienisz zdanie po potwierdzeniu, możesz w każdej chwili
[anulować rezerwację]({{ $cancelUrl }}).

Dzięki,<br>
Zespół DajPrezent.pl
</x-mail::message>
