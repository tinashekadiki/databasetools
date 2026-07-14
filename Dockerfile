# Author: Tinashe K
FROM davidcrty/databasement:1

COPY --chown=application:application docker/customizations/resources/views/layouts/app.blade.php /app/resources/views/layouts/app.blade.php
COPY --chown=application:application docker/customizations/resources/views/pages/licenses.blade.php /app/resources/views/pages/licenses.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/configuration/notification.blade.php /app/resources/views/livewire/configuration/notification.blade.php
COPY --chown=application:application docker/customizations/app/Livewire/Configuration/Notification.php /app/app/Livewire/Configuration/Notification.php
COPY --chown=application:application docker/customizations/app/Services/NotificationService.php /app/app/Services/NotificationService.php
COPY --chown=application:application docker/customizations/app/Services/SmtpSettingsService.php /app/app/Services/SmtpSettingsService.php
COPY --chown=application:application docker/customizations/routes/web.php /app/routes/web.php
