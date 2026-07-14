<div>
    <x-header :title="__('Configuration')" separator>
        <x-slot:subtitle>
            {{ __('Manage notification channels for backup and restore events.') }}
        </x-slot:subtitle>
    </x-header>

    @include('livewire.configuration._tabs', ['active' => 'notification'])

    <x-card :title="__('SMTP Delivery')" :subtitle="__('Configure the mail server used by Email notification channels.')" shadow class="min-w-0 mb-6">
        <div class="space-y-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <x-toggle
                    wire:model.live="smtp_enabled"
                    :label="__('Enable SMTP delivery')"
                    :disabled="!$this->canManage"
                />
                <span class="text-sm text-base-content/60">
                    {{ __('Email channels define recipients. These settings define the SMTP server and sender.') }}
                </span>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <x-input
                    wire:model="smtp_host"
                    :label="__('SMTP Host')"
                    placeholder="smtp.example.com"
                    :disabled="!$this->canManage"
                />

                <x-input
                    wire:model="smtp_port"
                    type="number"
                    min="1"
                    max="65535"
                    :label="__('Port')"
                    :disabled="!$this->canManage"
                />

                <x-select
                    wire:model="smtp_scheme"
                    :label="__('Security')"
                    :options="[
                        ['id' => 'tls', 'name' => __('STARTTLS')],
                        ['id' => 'smtps', 'name' => __('SMTPS')],
                        ['id' => '', 'name' => __('None')],
                    ]"
                    :disabled="!$this->canManage"
                />

                <x-input
                    wire:model="smtp_username"
                    :label="__('Username')"
                    autocomplete="username"
                    :disabled="!$this->canManage"
                />

                <x-password
                    wire:model="smtp_password"
                    :label="__('Password')"
                    :placeholder="$smtp_has_password ? '********' : ''"
                    autocomplete="new-password"
                    :disabled="!$this->canManage"
                />

                <x-input
                    wire:model="smtp_from_address"
                    type="email"
                    :label="__('From Address')"
                    placeholder="backups@example.com"
                    :disabled="!$this->canManage"
                />

                <x-input
                    wire:model="smtp_from_name"
                    :label="__('From Name')"
                    placeholder="Databasement"
                    :disabled="!$this->canManage"
                />

                <x-input
                    wire:model="smtp_test_recipient"
                    type="email"
                    :label="__('Test Recipient')"
                    placeholder="dba@example.com"
                    :disabled="!$this->canManage"
                />
            </div>

            @if ($smtp_has_password)
                <p class="text-xs text-base-content/50">{{ __('Leave the password blank to keep the saved password.') }}</p>
            @endif

            @if ($this->canManage)
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-base-200/60 pt-4">
                    <x-button
                        :label="__('Save SMTP Settings')"
                        icon="o-check"
                        class="btn-primary btn-sm"
                        wire:click="saveSmtpSettings"
                        spinner="saveSmtpSettings"
                    />
                    <x-button
                        :label="__('Send Test Email')"
                        icon="o-paper-airplane"
                        class="btn-secondary btn-sm"
                        wire:click="sendSmtpTest"
                        spinner="sendSmtpTest"
                    />
                </div>
            @endif
        </div>
    </x-card>

    <x-card :title="__('Notification Channels')" :subtitle="__('Assign channels per database server to receive alerts on backup and restore events.')" shadow class="min-w-0">
        <x-slot:menu>
            <x-button
                :label="__('Documentation')"
                icon="o-book-open"
                link="https://david-crty.github.io/databasement/self-hosting/configuration/notification"
                external
                class="btn-ghost btn-sm"
            />
        </x-slot:menu>

        <div class="divide-y divide-base-200/80">
            @forelse ($notificationChannels as $channel)
                <x-config-row wire:key="channel-{{ $channel->id }}">
                    <x-slot:label>
                        <span class="inline-flex flex-wrap items-center gap-2">
                            <x-icon :name="$channel->type->icon()" class="w-4 h-4 text-base-content/60" />
                            {{ $channel->name }}
                            <span class="badge badge-outline badge-sm">{{ $channel->type->label() }}</span>
                        </span>
                    </x-slot:label>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-sm text-base-content/60 min-w-0">
                            @foreach($channel->getConfigSummary() as $label => $value)
                                {{ $label }}: {{ $value }}{{ !$loop->last ? ' · ' : '' }}
                            @endforeach
                        </span>
                        @if ($this->canManage)
                            <div class="flex items-center gap-0.5 shrink-0 ml-auto">
                                <x-button icon="o-paper-airplane" class="btn-ghost btn-sm" wire:click="sendTestNotification('{{ $channel->id }}')" spinner="sendTestNotification('{{ $channel->id }}')" :tooltip-left="__('Test')" />
                                <x-button icon="o-pencil-square" class="btn-ghost btn-sm" wire:click="openChannelModal('{{ $channel->id }}')" :tooltip-left="__('Edit')" />
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error hover:bg-error/10" wire:click="confirmDeleteChannel('{{ $channel->id }}')" :tooltip-left="__('Delete')" />
                            </div>
                        @endif
                    </div>
                </x-config-row>
            @empty
                <p class="text-sm text-base-content/50 py-4 text-center">{{ __('No notification channels configured.') }}</p>
            @endforelse
        </div>

        @if ($this->canManage)
            <div class="flex items-center justify-end border-t border-base-200/60 pt-4 mt-4">
                <x-button
                    :label="__('Add Channel')"
                    icon="o-plus"
                    class="btn-primary btn-sm"
                    wire:click="openChannelModal"
                />
            </div>
        @endif
    </x-card>

    <!-- Add/Edit Notification Channel Modal -->
    <x-modal wire:model="showChannelModal" :title="$editingChannelId ? __('Edit Channel') : __('Add Channel')">
        <div class="space-y-4">
            <x-input
                wire:model="channelForm.name"
                :label="__('Name')"
                :placeholder="__('e.g., DBA Team Alerts')"
                required
            />

            @if (!$editingChannelId)
                <x-select
                    wire:model.live="channelForm.type"
                    :label="__('Type')"
                    :options="$channelTypeOptions"
                />
            @else
                <x-input
                    :value="$channelForm->channel?->type->label()"
                    :label="__('Type')"
                    disabled
                />
            @endif

            {{-- Type-specific config fields --}}
            @if ($channelForm->type === 'email')
                <x-input wire:model="channelForm.config_to" :label="__('Recipient Emails')" :hint="__('Separate multiple addresses with a comma.')" required />
            @elseif ($channelForm->type === 'slack')
                <x-password wire:model="channelForm.config_webhook_url" :label="__('Webhook URL')" :placeholder="$channelForm->has_config_webhook_url ? '********' : ''" />
            @elseif ($channelForm->type === 'discord')
                <x-password wire:model="channelForm.config_token" :label="__('Bot Token')" :placeholder="$channelForm->has_config_token ? '********' : ''" />
                <x-input wire:model="channelForm.config_channel_id" :label="__('Channel ID')" required />
            @elseif ($channelForm->type === 'discord_webhook')
                <x-password wire:model="channelForm.config_url" :label="__('Webhook URL')" :placeholder="$channelForm->has_config_url ? '********' : ''" />
            @elseif ($channelForm->type === 'telegram')
                <x-password wire:model="channelForm.config_bot_token" :label="__('Bot Token')" :placeholder="$channelForm->has_config_bot_token ? '********' : ''" />
                <x-input wire:model="channelForm.config_chat_id" :label="__('Chat ID')" required />
                <x-input wire:model="channelForm.config_topic_id" :label="__('Topic ID')" :hint="__('Optional. The message thread ID for sending to a specific topic in a forum group.')" />
            @elseif ($channelForm->type === 'pushover')
                <x-password wire:model="channelForm.config_token" :label="__('App Token')" :placeholder="$channelForm->has_config_token ? '********' : ''" />
                <x-password wire:model="channelForm.config_user_key" :label="__('User Key')" :placeholder="$channelForm->has_config_user_key ? '********' : ''" />
            @elseif ($channelForm->type === 'gotify')
                <x-input wire:model="channelForm.config_url" :label="__('Server URL')" :placeholder="__('https://gotify.example.com')" required />
                <x-password wire:model="channelForm.config_token" :label="__('App Token')" :placeholder="$channelForm->has_config_token ? '********' : ''" />
            @elseif ($channelForm->type === 'webhook')
                <x-input wire:model="channelForm.config_url" :label="__('Webhook URL')" required />
                <x-password wire:model="channelForm.config_secret" :label="__('Secret (optional)')" :placeholder="$channelForm->has_config_secret ? '********' : ''" />
            @endif

            @if ($editingChannelId)
                <p class="text-xs text-base-content/50">{{ __('Leave password fields blank to keep existing values.') }}</p>
            @endif
        </div>

        <x-slot:actions>
            <x-button :label="__('Cancel')" @click="$wire.showChannelModal = false" />
            <x-button
                class="btn-primary"
                :label="__('Save')"
                wire:click="saveChannel"
                spinner="saveChannel"
            />
        </x-slot:actions>
    </x-modal>

    <!-- Delete Channel Confirmation Modal -->
    <x-modal wire:model="showDeleteChannelModal" :title="__('Delete Channel')">
        <p>{{ __('Are you sure you want to delete this notification channel? This action cannot be undone.') }}</p>

        <x-slot:actions>
            <x-button :label="__('Cancel')" @click="$wire.showDeleteChannelModal = false" />
            <x-button
                class="btn-error"
                :label="__('Delete')"
                wire:click="deleteChannel"
                spinner="deleteChannel"
            />
        </x-slot:actions>
    </x-modal>
</div>
