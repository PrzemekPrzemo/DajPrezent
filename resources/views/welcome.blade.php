@extends('layouts.public')

@section('title', 'Prezenty od serca, bez stresu')
@section('meta_description', 'Stwórz wymarzoną listę prezentów lub stronę ślubną z RSVP. Bliscy zarezerwują anonimowo, Ty zobaczysz tylko status — kto, dowiesz się dopiero przy rozpakowywaniu.')

@section('content')
    <section class="dp-hero-bg relative overflow-hidden">
        <div class="absolute -top-32 -right-32 w-[480px] h-[480px] bg-dp-purple-200/40 rounded-full blur-3xl dp-bloom"></div>
        <div class="absolute -bottom-40 -left-32 w-[420px] h-[420px] bg-blue-200/30 rounded-full blur-3xl dp-bloom" style="animation-delay: -7s;"></div>

        <div class="relative max-w-6xl mx-auto px-4 py-20 sm:py-28 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7">
                <span class="dp-chip dp-chip-pink mb-4">⚡ Nowość — KSeF FV w cenie</span>
                <h1 class="font-display text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    Prezenty
                    <span class="bg-dp-gradient bg-clip-text text-transparent">od serca</span>,
                    bez stresu.
                </h1>
                <p class="mt-5 text-lg text-dp-muted max-w-xl">
                    Stwórz wymarzoną listę prezentów lub stronę ślubną z RSVP w 3 minuty.
                    Bliscy zarezerwują anonimowo — dowiesz się dopiero przy rozpakowywaniu.
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('public.pricing') }}" class="dp-btn-primary px-7 py-3 text-base">
                        Wybierz pakiet →
                    </a>
                    <a href="{{ route('login') }}" class="dp-btn-ghost px-4 py-3">Mam już konto</a>
                </div>
                <div class="mt-6 flex items-center gap-6 text-sm text-dp-muted">
                    <span class="flex items-center gap-1.5">✓ Bez reklam</span>
                    <span class="flex items-center gap-1.5">✓ Faktura VAT</span>
                    <span class="flex items-center gap-1.5">✓ Gość bez konta</span>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="dp-card shadow-dp-card-lg rotate-1 hover:rotate-0 transition-transform duration-700 ease-dp">
                    <div class="flex items-center gap-2 pb-3 border-b border-dp-purple-50">
                        <x-brand.logo size="sm"/>
                    </div>
                    <div class="pt-4 space-y-3">
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">🎁</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Aparat instax mini</div>
                                <div class="text-xs text-dp-muted">299,00 zł</div>
                            </div>
                            <span class="dp-chip dp-chip-reserved">zarezerwowany</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">📚</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Książka „Atomic Habits"</div>
                                <div class="text-xs text-dp-muted">49,90 zł</div>
                            </div>
                            <button class="dp-btn-primary px-3 py-1.5 text-xs">Zarezerwuj</button>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-dp bg-dp-purple-50/50">
                            <div class="w-10 h-10 rounded-dp bg-dp-gradient flex items-center justify-center text-white">🌿</div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm">Monstera Deliciosa</div>
                                <div class="text-xs text-dp-muted">89,00 zł</div>
                            </div>
                            <span class="dp-chip dp-chip-received">otrzymany</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="max-w-6xl mx-auto px-4 py-16">
        <div class="grid sm:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold mb-3">1</div>
                <h3 class="font-display font-semibold text-lg">Stwórz listę</h3>
                <p class="text-sm text-dp-muted mt-1">Wybierz pakiet, własny adres dajprezent.pl/{ty}.</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold mb-3">2</div>
                <h3 class="font-display font-semibold text-lg">Udostępnij</h3>
                <p class="text-sm text-dp-muted mt-1">Wyślij link lub QR. Bliscy widzą prezenty, ale nie siebie nawzajem.</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 mx-auto rounded-dp bg-dp-gradient text-white flex items-center justify-center font-bold mb-3">3</div>
                <h3 class="font-display font-semibold text-lg">Świętuj</h3>
                <p class="text-sm text-dp-muted mt-1">Każdy prezent trafia do Ciebie raz — bez duplikatów, bez stresu.</p>
            </div>
        </div>
    </section>
@endsection
