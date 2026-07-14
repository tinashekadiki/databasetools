# Author: Tinashe K
FROM davidcrty/databasement:1

RUN mkdir -p /app/app/Http/Controllers/Web /app/app/Livewire/DatabaseBrowser /app/resources/views/livewire/database-browser

COPY --chown=application:application docker/customizations/public/dot-enterprise.css /app/public/dot-enterprise.css
COPY --chown=application:application docker/customizations/public/dot-adminer.css /app/public/dot-adminer.css
COPY --chown=application:application docker/customizations/resources/views/components/app-brand.blade.php /app/resources/views/components/app-brand.blade.php
COPY --chown=application:application docker/customizations/resources/views/components/logo-icon.blade.php /app/resources/views/components/logo-icon.blade.php
COPY --chown=application:application docker/customizations/resources/views/layouts/_theme-init.blade.php /app/resources/views/layouts/_theme-init.blade.php
COPY --chown=application:application docker/customizations/resources/views/layouts/app.blade.php /app/resources/views/layouts/app.blade.php
COPY --chown=application:application docker/customizations/resources/views/layouts/auth.blade.php /app/resources/views/layouts/auth.blade.php
COPY --chown=application:application docker/customizations/resources/views/pages/licenses.blade.php /app/resources/views/pages/licenses.blade.php
COPY --chown=application:application docker/customizations/resources/views/mail/notification.blade.php /app/resources/views/mail/notification.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/auth/login.blade.php /app/resources/views/livewire/auth/login.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/auth/register.blade.php /app/resources/views/livewire/auth/register.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/database-browser/index.blade.php /app/resources/views/livewire/database-browser/index.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/database-server/index.blade.php /app/resources/views/livewire/database-server/index.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/database-server/show.blade.php /app/resources/views/livewire/database-server/show.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/configuration/application.blade.php /app/resources/views/livewire/configuration/application.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/configuration/notification.blade.php /app/resources/views/livewire/configuration/notification.blade.php
COPY --chown=application:application docker/customizations/resources/views/livewire/settings/preferences.blade.php /app/resources/views/livewire/settings/preferences.blade.php
COPY --chown=application:application docker/customizations/app/Livewire/DatabaseBrowser/Index.php /app/app/Livewire/DatabaseBrowser/Index.php
COPY --chown=application:application docker/customizations/app/Http/Controllers/Web/AdminerController.php /app/app/Http/Controllers/Web/AdminerController.php
COPY --chown=application:application docker/customizations/app/Livewire/Configuration/Notification.php /app/app/Livewire/Configuration/Notification.php
COPY --chown=application:application docker/customizations/app/Services/NotificationService.php /app/app/Services/NotificationService.php
COPY --chown=application:application docker/customizations/app/Services/SmtpSettingsService.php /app/app/Services/SmtpSettingsService.php
COPY --chown=application:application docker/customizations/app/Services/AdminerService.php /app/app/Services/AdminerService.php
COPY --chown=application:application docker/customizations/app/Services/adminer_object.php /app/app/Services/adminer_object.php
COPY --chown=application:application docker/customizations/routes/web.php /app/routes/web.php
