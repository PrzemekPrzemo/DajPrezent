<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check">
                Zapisz konfigurację
            </x-filament::button>
            <span class="text-xs text-gray-500">
                Sekrety (Client Secret, MD5, token KSeF, hasło certyfikatu) są szyfrowane przed zapisem do bazy.
            </span>
        </div>
    </form>
</x-filament-panels::page>
