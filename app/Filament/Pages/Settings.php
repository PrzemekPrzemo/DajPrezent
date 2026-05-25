<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Settings\SettingsRepository;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Master-admin config — PayU, KSeF and invoice numbering.
 *
 * Each section is its own form with its own submit button so an
 * admin who's mid-edit on KSeF doesn't risk overwriting PayU
 * settings (or seeing 3 fields invalidate when only 1 was touched).
 *
 * Sensitive fields (client_secret, md5_key, ksef token, cert
 * password) render as a "•••• set" mask once stored. Submitting
 * the mask string is a no-op — the existing DB value is preserved.
 * Submitting an empty string clears the value; anything else
 * overwrites.
 *
 * Implementation: Filament v3 doesn't natively support multiple
 * forms on a Page, so we wire three separate forms via
 * `getForms()` and bind each to its own state path
 * (`payuData` / `ksefData` / `invoiceData`).
 */
final class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Konfiguracja';

    protected static ?string $title = 'Konfiguracja systemu';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.settings';

    public ?array $payuData = [];

    public ?array $ksefData = [];

    public ?array $invoiceData = [];

    private const MASK = '•••• set';

    public function mount(): void
    {
        $this->loadAll();
        $this->payuForm->fill($this->payuData);    // @phpstan-ignore-line
        $this->ksefForm->fill($this->ksefData);    // @phpstan-ignore-line
        $this->invoiceForm->fill($this->invoiceData); // @phpstan-ignore-line
    }

    protected function getForms(): array
    {
        return [
            'payuForm',
            'ksefForm',
            'invoiceForm',
        ];
    }

    public function payuForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Bramka płatności — PayU')
                    ->description('Dane konta PayU z punktu „Moja firma → Punkty płatności". Sekrety szyfrowane w bazie.')
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        Select::make('payu_env')
                            ->label('Środowisko')
                            ->options(['sandbox' => 'Sandbox (testowe)', 'prod' => 'Produkcja'])
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
                        TextInput::make('payu_pos_id')->label('POS ID')->required()->placeholder('np. 300746'),
                        TextInput::make('payu_client_id')->label('OAuth Client ID')->required(),
                        TextInput::make('payu_client_secret')
                            ->label('OAuth Client Secret')
                            ->password()
                            ->revealable()
                            ->helperText('Zostaw puste żeby nie zmieniać — „'.self::MASK.'" oznacza że sekret jest zapisany.'),
                        TextInput::make('payu_md5_key')
                            ->label('Drugi klucz MD5 (IPN signature)')
                            ->password()
                            ->revealable()
                            ->helperText('Używany do weryfikacji podpisu webhooka. Zostaw puste żeby nie zmieniać.'),
                    ]),
            ])
            ->statePath('payuData');
    }

    public function ksefForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Krajowy System e-Faktur (KSeF)')
                    ->description('Można użyć tokena (prościej) ALBO certyfikatu .pfx (zalecane na produkcję).')
                    ->icon('heroicon-o-document-text')
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
                        TextInput::make('ksef_token')
                            ->label('Token autoryzacyjny (opcja 1)')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->helperText('Najprostsza metoda. Wygenerujesz w panelu KSeF: Mój profil → Tokeny autoryzacyjne. Zostaw puste jeśli używasz certyfikatu.'),
                        FileUpload::make('ksef_cert_path')
                            ->label('Certyfikat .pfx (opcja 2)')
                            ->disk('local')
                            ->directory('ksef')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/x-pkcs12', 'application/pkcs12', 'application/octet-stream'])
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->helperText('Plik PKCS#12 (.pfx / .p12) podpisany przez KIR / Sigillum. Trzymany prywatnie, nigdy nie wystawiany publicznie.'),
                        TextInput::make('ksef_cert_password')
                            ->label('Hasło do certyfikatu')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->helperText('Passphrase do pliku .pfx — szyfrowane w bazie.'),
                    ]),
            ])
            ->statePath('ksefData');
    }

    public function invoiceForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Numeracja faktur')
                    ->description('Format numeru FV i kiedy licznik się resetuje. Po zmianie nowy schemat dotyczy tylko nowych faktur.')
                    ->icon('heroicon-o-hashtag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('invoice_number_format')
                            ->label('Format numeru')
                            ->required()
                            ->placeholder('FV/{YYYY}/{MM}/{NNNN}')
                            ->helperText('Placeholdery: {YYYY} {YY} {MM} {DD} {NNNN}'),
                        Select::make('invoice_sequence_reset')
                            ->label('Licznik resetuje się')
                            ->options([
                                'monthly' => 'Co miesiąc',
                                'yearly' => 'Co rok',
                                'never' => 'Nigdy (ciągły licznik)',
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
            ->statePath('invoiceData');
    }

    public function savePayu(): void
    {
        $state = $this->payuForm->getState(); // @phpstan-ignore-line
        $s = app(SettingsRepository::class);

        $s->set('payu.env', $state['payu_env']);
        $s->set('payu.base_url', $state['payu_base_url']);
        $s->set('payu.pos_id', $state['payu_pos_id']);
        $s->set('payu.client_id', $state['payu_client_id']);
        $this->saveSecret($s, 'payu.client_secret', $state['payu_client_secret'] ?? null);
        $this->saveSecret($s, 'payu.md5_key', $state['payu_md5_key'] ?? null);

        $this->notifySuccess('Sekcja PayU zapisana.');
        $this->loadAll();
    }

    public function saveKsef(): void
    {
        $state = $this->ksefForm->getState(); // @phpstan-ignore-line
        $s = app(SettingsRepository::class);

        $s->set('ksef.env', $state['ksef_env']);
        $s->set('ksef.nip', $state['ksef_nip']);
        $this->saveSecret($s, 'ksef.token', $state['ksef_token'] ?? null);

        $certPath = $state['ksef_cert_path'] ?? null;
        if (is_array($certPath)) {
            $certPath = $certPath[array_key_first($certPath)] ?? null;
        }
        if (is_string($certPath) && $certPath !== '') {
            $s->set('ksef.cert_path', basename($certPath));
        }
        $this->saveSecret($s, 'ksef.cert_password', $state['ksef_cert_password'] ?? null);

        $this->notifySuccess('Sekcja KSeF zapisana.');
        $this->loadAll();
    }

    public function saveInvoice(): void
    {
        $state = $this->invoiceForm->getState(); // @phpstan-ignore-line
        $s = app(SettingsRepository::class);

        $s->set('invoice.number_format', $state['invoice_number_format']);
        $s->set('invoice.sequence_reset', $state['invoice_sequence_reset']);
        $s->set('invoice.start_number', (int) $state['invoice_start_number']);

        $this->notifySuccess('Sekcja Numeracja FV zapisana.');
        $this->loadAll();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (bool) ($user->is_master_admin ?? false);
    }

    private function loadAll(): void
    {
        $s = app(SettingsRepository::class);

        $this->payuData = [
            'payu_env' => $s->get('payu.env', 'sandbox'),
            'payu_base_url' => $s->get('payu.base_url', 'https://secure.snd.payu.com'),
            'payu_pos_id' => $s->get('payu.pos_id', ''),
            'payu_client_id' => $s->get('payu.client_id', ''),
            'payu_client_secret' => $this->maskSecret($s->get('payu.client_secret', '')),
            'payu_md5_key' => $this->maskSecret($s->get('payu.md5_key', '')),
        ];

        $this->ksefData = [
            'ksef_env' => $s->get('ksef.env', 'test'),
            'ksef_nip' => $s->get('ksef.nip', ''),
            'ksef_token' => $this->maskSecret($s->get('ksef.token', '')),
            'ksef_cert_path' => $s->get('ksef.cert_path', ''),
            'ksef_cert_password' => $this->maskSecret($s->get('ksef.cert_password', '')),
        ];

        $this->invoiceData = [
            'invoice_number_format' => $s->get('invoice.number_format', 'FV/{YYYY}/{MM}/{NNNN}'),
            'invoice_sequence_reset' => $s->get('invoice.sequence_reset', 'monthly'),
            'invoice_start_number' => $s->get('invoice.start_number', 1),
        ];
    }

    /**
     * If the submitted value matches the masked placeholder we rendered,
     * the admin did not touch the field — keep the DB value. Empty string
     * clears it; anything else overwrites.
     */
    private function saveSecret(SettingsRepository $s, string $key, ?string $submitted): void
    {
        if ($submitted === self::MASK) {
            return;
        }
        $s->set($key, $submitted ?? '');
    }

    private function maskSecret(mixed $current): string
    {
        return ($current !== null && $current !== '') ? self::MASK : '';
    }

    private function notifySuccess(string $title): void
    {
        Notification::make()
            ->title($title)
            ->success()
            ->send();
    }
}
