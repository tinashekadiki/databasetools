<?php

namespace App\Livewire\Configuration;

use App\Enums\NotificationChannelType;
use App\Livewire\Forms\NotificationChannelForm;
use App\Models\NotificationChannel;
use App\Services\NotificationService;
use App\Services\SmtpSettingsService;
use App\Traits\Toast;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

#[Title('Configuration')]
class Notification extends Component
{
    use Toast;

    public NotificationChannelForm $channelForm;

    // Notification channel modal state
    public bool $showChannelModal = false;

    public ?string $editingChannelId = null;

    public ?string $deleteChannelId = null;

    public bool $showDeleteChannelModal = false;

    public bool $smtp_enabled = false;

    public string $smtp_host = '';

    public string $smtp_port = '587';

    public string $smtp_scheme = 'tls';

    public string $smtp_username = '';

    public string $smtp_password = '';

    public bool $smtp_has_password = false;

    public string $smtp_from_address = 'backups@example.com';

    public string $smtp_from_name = 'Databasement';

    public string $smtp_test_recipient = '';

    public ?string $smtp_test_error = null;

    public ?string $smtp_test_error_details = null;

    public function mount(SmtpSettingsService $smtpSettings): void
    {
        $this->loadSmtpSettings($smtpSettings);
    }

    #[Computed]
    public function canManage(): bool
    {
        return auth()->user()->can('manage', NotificationChannel::class);
    }

    public function openChannelModal(?string $channelId = null): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        $this->channelForm->resetFields();
        $this->editingChannelId = $channelId;

        if ($channelId) {
            $channel = NotificationChannel::findOrFail($channelId);
            $this->channelForm->setChannel($channel);
        }

        $this->showChannelModal = true;
    }

    public function saveChannel(): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        if ($this->editingChannelId) {
            $this->channelForm->channel = NotificationChannel::findOrFail($this->editingChannelId);
            $this->channelForm->update();
        } else {
            $this->channelForm->store();
        }

        $this->showChannelModal = false;
        $this->editingChannelId = null;
        $this->channelForm->resetFields();

        $this->success(__('Notification channel saved.'));
    }

    public function confirmDeleteChannel(string $channelId): void
    {
        $this->deleteChannelId = $channelId;
        $this->showDeleteChannelModal = true;
    }

    public function deleteChannel(): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        if (! $this->deleteChannelId) {
            return;
        }

        NotificationChannel::findOrFail($this->deleteChannelId)->delete();
        $this->showDeleteChannelModal = false;
        $this->deleteChannelId = null;

        $this->success(__('Notification channel deleted.'));
    }

    public function sendTestNotification(string $channelId): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        $channel = NotificationChannel::findOrFail($channelId);

        try {
            app(NotificationService::class)->sendTestNotification($channel);

            $this->success(__('Test notification sent to: :channel', ['channel' => $channel->name]));
        } catch (\Throwable $e) {
            $this->error(
                title: __('Failed to send test notification: :message', ['message' => $e->getMessage()]),
                timeout: 0
            );
        }
    }

    public function saveSmtpSettings(SmtpSettingsService $smtpSettings): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        $this->validate($this->smtpValidationRules());
        $smtpSettings->save($this->smtpPayload());
        $this->loadSmtpSettings($smtpSettings);
        $this->clearSmtpTestError();

        $this->success(__('SMTP settings saved.'));
    }

    public function sendSmtpTest(SmtpSettingsService $smtpSettings): void
    {
        abort_unless(auth()->user()->can('manage', NotificationChannel::class), Response::HTTP_FORBIDDEN);

        $this->clearSmtpTestError();

        try {
            $this->validate($this->smtpValidationRules(includeTestRecipient: true));
            $smtpSettings->save($this->smtpPayload());
            $smtpSettings->sendTest($this->smtp_test_recipient);
            $this->loadSmtpSettings($smtpSettings);
            $this->clearSmtpTestError();

            $this->success(__('SMTP test email sent to: :recipient', ['recipient' => $this->smtp_test_recipient]));
        } catch (ValidationException $e) {
            $this->addValidationErrors($e);
            $this->recordSmtpTestFailure($e, __('SMTP test validation failed.'));
        } catch (\Throwable $e) {
            $this->recordSmtpTestFailure($e);
        }
    }

    public function clearSmtpTestError(): void
    {
        $this->smtp_test_error = null;
        $this->smtp_test_error_details = null;
    }

    // --- Computed Properties ---

    /**
     * @return Collection<int, NotificationChannel>
     */
    #[Computed]
    public function notificationChannels(): Collection
    {
        return NotificationChannel::orderBy('name')->get();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getChannelTypeOptions(): array
    {
        return array_map(
            fn (NotificationChannelType $type) => ['id' => $type->value, 'name' => $type->label()],
            NotificationChannelType::cases(),
        );
    }

    public function render(): View
    {
        return view('livewire.configuration.notification', [
            'channelTypeOptions' => $this->getChannelTypeOptions(),
            'notificationChannels' => $this->notificationChannels(),
        ]);
    }

    private function loadSmtpSettings(SmtpSettingsService $smtpSettings): void
    {
        $values = $smtpSettings->values();

        $this->smtp_enabled = (bool) $values['enabled'];
        $this->smtp_host = (string) $values['host'];
        $this->smtp_port = (string) $values['port'];
        $this->smtp_scheme = (string) $values['scheme'];
        $this->smtp_username = (string) $values['username'];
        $this->smtp_password = '';
        $this->smtp_has_password = (bool) $values['has_password'];
        $this->smtp_from_address = (string) $values['from_address'];
        $this->smtp_from_name = (string) $values['from_name'];
    }

    /**
     * @return array<string, mixed>
     */
    private function smtpPayload(): array
    {
        return [
            'enabled' => $this->smtp_enabled,
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'scheme' => $this->smtp_scheme,
            'username' => $this->smtp_username,
            'password' => $this->smtp_password,
            'from_address' => $this->smtp_from_address,
            'from_name' => $this->smtp_from_name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function smtpValidationRules(bool $includeTestRecipient = false): array
    {
        $rules = [
            'smtp_enabled' => ['boolean'],
            'smtp_host' => ['required_if:smtp_enabled,true', 'nullable', 'string', 'max:255'],
            'smtp_port' => ['required_if:smtp_enabled,true', 'integer', 'min:1', 'max:65535'],
            'smtp_scheme' => ['nullable', 'string', Rule::in(['', 'tls', 'smtps'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'smtp_from_address' => ['required_if:smtp_enabled,true', 'nullable', 'email', 'max:255'],
            'smtp_from_name' => ['required_if:smtp_enabled,true', 'nullable', 'string', 'max:255'],
        ];

        if ($includeTestRecipient) {
            $rules['smtp_test_recipient'] = ['required', 'email', 'max:255'];
        }

        return $rules;
    }

    private function recordSmtpTestFailure(\Throwable $exception, ?string $title = null): void
    {
        $message = $this->smtpFailureMessage($exception);
        $this->smtp_test_error = $message;
        $this->smtp_test_error_details = $this->smtpFailureDetails($exception, $message);

        Log::error('SMTP test email failed', [
            'exception_class' => $exception::class,
            'message' => $message,
            'smtp_enabled' => $this->smtp_enabled,
            'smtp_host' => $this->smtp_host,
            'smtp_port' => $this->smtp_port,
            'smtp_scheme' => $this->smtp_scheme,
            'smtp_from_address' => $this->smtp_from_address,
            'smtp_test_recipient' => $this->smtp_test_recipient,
            'exception' => $exception,
        ]);

        $this->error(
            title: $title ?? __('Failed to send SMTP test email.'),
            description: $message,
            timeout: 60000
        );
    }

    private function smtpFailureMessage(\Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())
                ->flatten()
                ->filter()
                ->implode(' ');
        }

        $messages = [];
        $currentException = $exception;

        while ($currentException) {
            $message = trim($currentException->getMessage());

            if ($message !== '') {
                $messages[] = $message;
            }

            $currentException = $currentException->getPrevious();
        }

        return implode(' Caused by: ', array_unique($messages)) ?: $exception::class;
    }

    private function smtpFailureDetails(\Throwable $exception, string $message): string
    {
        return implode(PHP_EOL, [
            'Time: '.now()->toDateTimeString(),
            'Exception: '.$exception::class,
            'Message: '.$message,
            'Host: '.$this->smtp_host,
            'Port: '.$this->smtp_port,
            'Security: '.($this->smtp_scheme === '' ? 'none' : $this->smtp_scheme),
            'From: '.$this->smtp_from_address,
            'Recipient: '.$this->smtp_test_recipient,
        ]);
    }

    private function addValidationErrors(ValidationException $exception): void
    {
        foreach ($exception->errors() as $field => $messages) {
            $field = $field === 'recipient' ? 'smtp_test_recipient' : $field;

            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }
}
