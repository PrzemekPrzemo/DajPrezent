<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Settings\SettingsRepository;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

/**
 * Single-screen master-admin config: PayU, KSeF and invoice numbering.
 *
 * Values live in `app_settings` table; sensitive ones are encrypted
 * at rest by SettingsRepository. Certificate uploads land in
 * `storage/app/private/ksef/<filename>.pfx` — never publicly served.
 *
 * Wiping a sensitive field with an empty submission clears it in DB.
 * Saved values are masked on next render (we only show "•••• set"
 * rather than re-displaying the cleartext, so an over-the-shoulder
 * peek can't lift the credential).
 */
final class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Konfiguracja';

    protected static ?string $title = 'Konfiguracja systemu';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        /** @var SettingsRepository $s */
        $s = app(SettingsRepository::class);

        $this->data = [
            // PayU
            'payu_env' => $s->get('payu.env', 'sandbox'),
            'payu_base_url' => $s->get('payu.base_url', 'https://secure.snd.payu.com'),
            'payu_pos_id' => $s->get('payu.pos_id', ''),
            'payu_client_id' => $s->get('payu.client_id', ''),
            'payu_client_secret' => $this->maskSecret($s->get('payu.client_secret', '')),
            'payu_md5_key' => $this->maskSecret($s->get('payu.md5_key', '')),

            // KSeF
            'ksef_env' => $s->get('ksef.env', 'test'),
            'ksef_nip' => $s->get('ksef.nip', ''),
            'ksef_token' => $this->maskSecret($s->get('ksef.token', '')),
            'ksef_cert_path' => $s->get('ksef.cert_path', ''),
            'ksef_cert_password' => $this->maskSecret($s->get('ksef.cert_password', '')),

            // Numeracja FV
            'invoice_number_format' => $s->get('invoice.number_format', 'FV/{YYYY}/{MM}/{NNNN}'),
            'invoice_sequence_reset' => $s->get('invoice.sequence_reset', 'monthly'),
            'invoice_start_number' => $s->get('invoice.start_number', 1),
        ];

        $this->form->fill($this->data); // @phpstan-ignore-line — magic property from InteractsWithForms trait
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Bramka płatności — PayU')
                    ->description('Dane konta PayU z punktu „Moja firma → Punkty płatności" w panelu PayU. Sekrety są szyfrowane w bazie.')
                    ->icon('heroicon-o-credit-card')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('payu_env')
                            ->label('Środowisko')
                            ->options([
                                'sandbox' => 'Sandbox (testowe)',
                                'prod' => 'Produkcja',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('payu_base_url', $state === 'prod'
                                    ? 'https://secure.payu.com'
                                    : 'https://secure.snd.payu.com');
                            }),
                        TextInput::make('payu_base_url')
                            ->label('Base URL')
                            ->required()
                            ->placeholder('https://secure.snd.payu.com'),
                        TextInput::make('payu_pos_id')
                            ->label('POS ID')
                            ->required()
                            ->placeholder('np. 300746'),
                        TextInput::make('payu_client_id')
                            ->label('OAuth Client ID')
                            ->required(),
                        TextInput::make('payu_client_secret')
                            ->label('OAuth Client Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Zostaw puste żeby nie zmieniać — wpis „•••• set" oznacza że sekret jest zapisany.'),
                        TextInput::make('payu_md5_key')
                            ->label('Drugi klucz MD5 (IPN signature)')
                            ->password()
                            ->revealable()
                            ->helperText('Używany do weryfikacji podpisu webhooka. Zostaw puste żeby nie zmieniać.'),
                    ]),

                Section::make('Krajowy System e-Faktur (KSeF)')
                    ->description('Konfiguracja wystawcy + auth do MF. Można użyć tokena (prościej) ALBO certyfikatu .pfx (zalecane na produkcję).')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Select::make('ksef_env')
                            ->label('Środowisko KSeF')
                            ->options([
                                'test' => 'Test (KSeF-test)',
                                'demo' => 'Demo (KSeF-demo)',
                                'prod' => 'Produkcja',
                            ])
                            ->required()
                            ->helperText('Tryb prod wymaga prawdziwego tokena lub certyfikatu.'),
                        TextInput::make('ksef_nip')
                            ->label('NIP wystawcy')
                            ->required()
                            ->maxLength(13)
                            ->placeholder('5252866457')
                            ->helperText('10 cyfr (z separatorami lub bez).'),

                        Group::make()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('ksef_token')
                                    ->label('Token autoryzacyjny (opcja 1)')
                                    ->password()
                                    ->revealable()
                                    ->columnSpanFull()
                                    ->helperText('Najprostsza metoda. Wygenerujesz w panelu KSeF: Mój profil → Tokeny autoryzacyjne. Zostaw puste, jeśli używasz certyfikatu.'),

                                FileUpload::make('ksef_cert_path')
                                    ->label('Certyfikat .pfx (opcja 2)')
                                    ->disk('local')
                                    ->directory('ksef')
                                    ->visibility('private')
                                    ->acceptedFileTypes(['application/x-pkcs12', 'application/pkcs12', 'application/octet-stream'])
                                    ->maxSize(2048)
                                    ->columnSpanFull()
                                    ->helperText('Plik PKCS#12 (zwykle .pfx lub .p12) podpisany przez KIR/Sigillum. Trzymany prywatnie, nigdy nie wystawiany publicznie.'),

                                TextInput::make('ksef_cert_password')
                                    ->label('Hasło do certyfikatu')
                                    ->password()
                                    ->revealable()
                                    ->columnSpanFull()
                                    ->helperText('Passphrase do pliku .pfx — szyfrowane w bazie.'),
                            ]),
                    ]),

                Section::make('Numeracja faktur')
                    ->description('Format numeru FV i kiedy licznik się resetuje. Po zmianie nowy schemat dotyczy tylko nowych faktur.')
                    ->icon('heroicon-o-hashtag')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        TextInput::make('invoice_number_format')
                            ->label('Format numeru')
                            ->required()
                            ->placeholder('FV/{YYYY}/{MM}/{NNNN}')
                            ->helperText('Placeholdery: {YYYY} {YY} {MM} {DD} {NNNN} (liczba z paddingiem zer)'),
                        Select::make('invoice_sequence_reset')
                            ->label('Licznik resetuje się')
                            ->options([
                                'monthly' => 'Co miesiąc (osobny licznik dla każdego miesiąca)',
                                'yearly' => 'Co rok',
                                'never' => 'Nigdy (ciągły licznik systemowy)',
                            ])
                            ->required(),
                        TextInput::make('invoice_start_number')
                            ->label('Numer startowy')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Pierwszy numer po starcie / migracji.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState(); // @phpstan-ignore-line — magic property from InteractsWithForms trait

        /** @var SettingsRepository $s */
        $s = app(SettingsRepository::class);

        // PayU
        $s->set('payu.env', $state['payu_env']);
        $s->set('payu.base_url', $state['payu_base_url']);
        $s->set('payu.pos_id', $state['payu_pos_id']);
        $s->set('payu.client_id', $state['payu_client_id']);
        $this->saveSecret($s, 'payu.client_secret', $state['payu_client_secret'] ?? null);
        $this->saveSecret($s, 'payu.md5_key', $state['payu_md5_key'] ?? null);

        // KSeF
        $s->set('ksef.env', $state['ksef_env']);
        $s->set('ksef.nip', $state['ksef_nip']);
        $this->saveSecret($s, 'ksef.token', $state['ksef_token'] ?? null);

        // Certyfikat .pfx — Filament FileUpload zwraca relatywną ścieżkę
        // w obrębie disk+directory; my zapisujemy samą nazwę pliku.
        $certPath = $state['ksef_cert_path'] ?? null;
        if (is_array($certPath)) {
            $certPath = $certPath[array_key_first($certPath)] ?? null;
        }
        if (is_string($certPath) && $certPath !== '') {
            $basename = basename($certPath);
            $s->set('ksef.cert_path', $basename);
        }
        $this->saveSecret($s, 'ksef.cert_password', $state['ksef_cert_password'] ?? null);

        // Numeracja FV
        $s->set('invoice.number_format', $state['invoice_number_format']);
        $s->set('invoice.sequence_reset', $state['invoice_sequence_reset']);
        $s->set('invoice.start_number', (int) $state['invoice_start_number']);

        Notification::make()
            ->title('Konfiguracja zapisana')
            ->success()
            ->send();

        // Re-mount values (refreshes the masked placeholders).
        $this->mount();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (bool) ($user->is_master_admin ?? false);
    }

    /**
     * If the value is the masked placeholder we get back from
     * mount(), it means the admin didn't change it — preserve the
     * existing DB value. If it's empty string, the admin actively
     * cleared the field. If it's anything else, write it.
     */
    private function saveSecret(SettingsRepository $s, string $key, ?string $submitted): void
    {
        if ($submitted === self::MASK) {
            return; // unchanged — keep DB value
        }
        $s->set($key, $submitted ?? '');
    }

    private const MASK = '•••• set';

    private function maskSecret(mixed $current): string
    {
        return ($current !== null && $current !== '') ? self::MASK : '';
    }
}
