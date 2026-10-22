<?php

use App\Http\Controllers\Web\AdminerController;
use App\Http\Controllers\Web\HealthCheckController;
use App\Http\Controllers\Web\OAuthController;
use App\Http\Controllers\Web\SnapshotDownloadController;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Changelog;
use App\Livewire\Configuration\Application;
use App\Livewire\Configuration\Authentication;
use App\Livewire\Configuration\Backup;
use App\Livewire\Configuration\Notification;
use App\Livewire\Configuration\Organization;
use App\Livewire\Configuration\Roles;
use App\Livewire\Dashboard;
use App\Livewire\DatabaseServer\Create;
use App\Livewire\DatabaseServer\Edit;
use App\Livewire\DatabaseServer\Index;
use App\Livewire\DatabaseServer\Show;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Preferences;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

// Public liveness probe; detailed diagnostics require authentication.
Route::get('/health', [HealthCheckController::class, 'up'])
    ->name('health.up');
Route::get('/health/debug', [HealthCheckController::class, 'debug'])
    ->middleware('auth')
    ->name('health.debug');

// Home - redirect based on auth status and user count
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return User::count() === 0
        ? redirect()->route('register')
        : redirect()->route('login');
})->name('home');

Route::view('/licenses', 'pages.licenses')
    ->name('licenses');

// Invitation routes (public)
Route::get('/invitation/{token}', AcceptInvitation::class)
    ->name('invitation.accept');

// OAuth routes (public - handle their own auth state)
Route::prefix('oauth')->name('oauth.')->group(function () {
    Route::get('{provider}/redirect', [OAuthController::class, 'redirect'])
        ->name('redirect');
    Route::get('{provider}/callback', [OAuthController::class, 'callback'])
        ->name('callback');
});

// Main resources - all authenticated users can view index pages
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::livewire('dashboard', Dashboard::class)
        ->name('dashboard');
    Route::livewire('changelog', Changelog::class)
        ->name('changelog');

    // Index pages - viewable by all roles
    Route::livewire('database-servers', Index::class)
        ->name('database-servers.index');
    Route::livewire('database-browser', App\Livewire\DatabaseBrowser\Index::class)
        ->name('database-browser.index');
    Route::livewire('volumes', App\Livewire\Volume\Index::class)
        ->name('volumes.index');
    Route::livewire('snapshots', App\Livewire\Snapshot\Index::class)
        ->name('snapshots.index');
    Route::livewire('restores', App\Livewire\Restore\Index::class)
        ->name('restores.index');
    Route::livewire('scheduled-restores', App\Livewire\ScheduledRestore\Index::class)
        ->name('scheduled-restores.index');
    Route::redirect('jobs', 'snapshots');

    // Users index - viewable by all (actions restricted in component)
    Route::livewire('users', App\Livewire\User\Index::class)
        ->name('users.index');

    // Configuration
    Route::get('configuration', fn () => redirect()->route('configuration.application'));
    Route::livewire('configuration/application', Application::class)
        ->name('configuration.application');
    Route::livewire('configuration/backup', Backup::class)
        ->name('configuration.backup');
    Route::livewire('configuration/notification', Notification::class)
        ->name('configuration.notification');
    Route::livewire('configuration/authentication', Authentication::class)
        ->name('configuration.authentication');
    // Roles - management gated by the manage-roles ability in the component
    Route::livewire('configuration/roles', Roles::class)
        ->name('configuration.roles');
    Route::livewire('configuration/organizations', Organization::class)
        ->name('configuration.organizations');

    // Agents
    Route::livewire('agents', App\Livewire\Agent\Index::class)
        ->name('agents.index');

    // API Tokens
    Route::livewire('tokens', App\Livewire\ApiToken\Index::class)
        ->name('api-tokens.index');
});

// Snapshot download - dedicated route to avoid Livewire OOM on large files
Route::middleware(['auth'])->group(function () {
    Route::get('snapshots/{snapshot}/download', SnapshotDownloadController::class)
        ->name('snapshots.download');
});

// Adminer database browser (excluded from CSRF in bootstrap/app.php)
Route::any('/adminer', AdminerController::class)
    ->middleware('auth')
    ->name('adminer');

// Action routes - authorization handled by Policies in components
Route::middleware(['auth'])->group(function () {
    // Database Servers
    Route::livewire('database-servers/create', Create::class)
        ->name('database-servers.create');
    Route::livewire('database-servers/{server}', Show::class)
        ->name('database-servers.show');
    Route::livewire('database-servers/{server}/edit', Edit::class)
        ->name('database-servers.edit');

    // Volumes
    Route::livewire('volumes/create', App\Livewire\Volume\Create::class)
        ->name('volumes.create');
    Route::livewire('volumes/{volume}/edit', App\Livewire\Volume\Edit::class)
        ->name('volumes.edit');

    // Agents
    Route::livewire('agents/create', App\Livewire\Agent\Create::class)
        ->name('agents.create');
    Route::livewire('agents/{agent}/edit', App\Livewire\Agent\Edit::class)
        ->name('agents.edit');

    // User management
    Route::livewire('users/create', App\Livewire\User\Create::class)
        ->name('users.create');
    Route::livewire('users/{user}/edit', App\Livewire\User\Edit::class)
        ->name('users.edit');
});

// Settings - all authenticated users
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)
        ->name('profile.edit');
    Route::livewire('settings/password', Password::class)
        ->name('user-password.edit');
    Route::livewire('settings/preferences', Preferences::class)
        ->name('preferences.edit');

    Route::livewire('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
