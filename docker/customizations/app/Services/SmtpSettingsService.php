<?php

namespace App\Services;

use App\Models\AppConfig;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmtpSettingsService
{
    private const string PREFIX = 'notification.smtp.';

    /**
     * @var array<string, array{type: string, is_sensitive: bool, default: mixed}>
     */
    private const array CONFIG = [
        'enabled' => ['type' => 'boolean', 'is_sensitive' => false, 'default' => false],
        'host' => ['type' => 'string', 'is_sensitive' => false, 'default' => ''],
        'port' => ['type' => 'integer', 'is_sensitive' => false, 'default' => 587],
        'scheme' => ['type' => 'string', 'is_sensitive' => false, 'default' => 'tls'],
        'username' => ['type' => 'string', 'is_sensitive' => true, 'default' => ''],
        'password' => ['type' => 'string', 'is_sensitive' => true, 'default' => ''],
        'from_address' => ['type' => 'string', 'is_sensitive' => false, 'default' => 'backups@example.com'],
        'from_name' => ['type' => 'string', 'is_sensitive' => false, 'default' => 'Databasement'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return [
            'enabled' => (bool) $this->get('enabled'),
            'host' => (string) $this->get('host'),
            'port' => (int) $this->get('port'),
            'scheme' => (string) $this->get('scheme'),
            'username' => (string) $this->get('username'),
            'password' => '',
            'has_password' => $this->hasPassword(),
            'from_address' => (string) $this->get('from_address'),
            'from_name' => (string) $this->get('from_name'),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        $validated = Validator::make($values, $this->rules())->validate();

        foreach (array_keys(self::CONFIG) as $key) {
            if ($key === 'password' && ($validated[$key] ?? '') === '') {
                continue;
            }

            $this->set($key, $validated[$key] ?? null);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'host' => ['required_if:enabled,true', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:enabled,true', 'integer', 'min:1', 'max:65535'],
            'scheme' => ['nullable', 'string', Rule::in(['', 'tls', 'smtps'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'from_address' => ['required_if:enabled,true', 'nullable', 'email', 'max:255'],
            'from_name' => ['required_if:enabled,true', 'nullable', 'string', 'max:255'],
        ];
    }

    public function apply(): void
    {
        if (! (bool) $this->get('enabled')) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.from.address' => $this->get('from_address'),
            'mail.from.name' => $this->get('from_name'),
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $this->get('host'),
            'mail.mailers.smtp.port' => (int) $this->get('port'),
            'mail.mailers.smtp.scheme' => $this->nullableString($this->get('scheme')),
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.username' => $this->nullableString($this->get('username')),
            'mail.mailers.smtp.password' => $this->nullableString($this->get('password')),
        ]);

        app('mail.manager')->forgetMailers();
    }

    public function sendTest(string $recipient): void
    {
        if (! (bool) $this->get('enabled')) {
            throw ValidationException::withMessages([
                'smtp_enabled' => __('Enable SMTP delivery before sending a test email.'),
            ]);
        }

        Validator::make(['recipient' => $recipient], [
            'recipient' => ['required', 'email', 'max:255'],
        ])->validate();

        $this->apply();

        Mail::raw('This is a Databasement SMTP test email from the DOT deployment.', function ($message) use ($recipient): void {
            $message
                ->to($recipient)
                ->subject('Databasement SMTP test');
        });
    }

    private function get(string $key): mixed
    {
        $row = AppConfig::find(self::PREFIX.$key);

        if ($row) {
            return $row->getCastedValue();
        }

        return self::CONFIG[$key]['default'] ?? null;
    }

    private function set(string $key, mixed $value): void
    {
        $schema = self::CONFIG[$key];

        AppConfig::updateOrCreate(
            ['id' => self::PREFIX.$key],
            [
                'value' => AppConfig::prepareValue($value, $schema['is_sensitive']),
                'type' => $schema['type'],
                'is_sensitive' => $schema['is_sensitive'],
            ],
        );
    }

    private function hasPassword(): bool
    {
        $value = $this->get('password');

        return is_string($value) && $value !== '';
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
