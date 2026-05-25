<x-filament-panels::page>
    <div class="space-y-6">
        {{-- PayU --}}
        <form wire:submit="savePayu">
            {{ $this->payuForm }}
            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Zapisz sekcję PayU
                </x-filament::button>
                <span class="text-xs text-gray-500">Sekrety (Client Secret, MD5) szyfrowane.</span>
            </div>
        </form>

        {{-- KSeF --}}
        <form wire:submit="saveKsef">
            {{ $this->ksefForm }}
            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Zapisz sekcję KSeF
                </x-filament::button>
                <span class="text-xs text-gray-500">Token, hasło certyfikatu i klucza prywatnego szyfrowane.</span>
            </div>
        </form>

        {{-- SMTP --}}
        <form wire:submit="saveSmtp">
            {{ $this->smtpForm }}
            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Zapisz sekcję SMTP
                </x-filament::button>
                <span class="text-xs text-gray-500">Hasło SMTP szyfrowane. Dotyczy najbliższego wysłanego maila.</span>
            </div>
        </form>

        {{-- Numeracja FV --}}
        <form wire:submit="saveInvoice">
            {{ $this->invoiceForm }}
            <div class="mt-4 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Zapisz sekcję Numeracja FV
                </x-filament::button>
                <span class="text-xs text-gray-500">Zmiana dotyczy tylko nowych faktur.</span>
            </div>
        </form>
    </div>
</x-filament-panels::page>
