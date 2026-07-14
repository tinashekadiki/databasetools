<?php

namespace App\Services;

class AdminerService
{
    /**
     * Serve Adminer with auto-login credentials and custom CSS.
     *
     * @param  array<string, string>|null  $credentials
     */
    public function render(?array $credentials)
    {
        $vendorAdminer = base_path('vendor/dg/adminer');

        $GLOBALS['_adminer_credentials'] = $credentials;
        $GLOBALS['_adminer_css_paths'] = array_values(array_filter([
            $this->resolveViteCssPath(),
            $this->resolveDotCssPath(),
        ]));

        $this->useNonLockingSessionHandler();
        $this->defineAdminerObject();

        $currentDirectory = getcwd();
        $bufferLevel = ob_get_level();

        ob_start();

        try {
            chdir($vendorAdminer);
            include_once $vendorAdminer.'/adminer.php';

            return (string) ob_get_clean();
        } finally {
            if (is_string($currentDirectory)) {
                chdir($currentDirectory);
            }

            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }

    private function resolveViteCssPath(): string
    {
        $manifestPath = public_path('build/manifest.json');

        if (file_exists($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);

            if (isset($manifest['resources/css/adminer.css']['file'])) {
                return '/build/'.$manifest['resources/css/adminer.css']['file'];
            }
        }

        return '/css/adminer.css';
    }

    private function resolveDotCssPath(): ?string
    {
        $path = public_path('dot-adminer.css');

        if (! file_exists($path)) {
            return null;
        }

        return '/dot-adminer.css?v='.filemtime($path);
    }

    private function useNonLockingSessionHandler(): void
    {
        $cache = cache()->store();
        $ttl = (int) config('session.lifetime', 120) * 60;

        session_set_save_handler(new class($cache, $ttl) implements \SessionHandlerInterface
        {
            public function __construct(
                private \Illuminate\Contracts\Cache\Repository $cache,
                private int $ttl,
            ) {}

            public function open(string $path, string $name): bool
            {
                return true;
            }

            public function close(): bool
            {
                return true;
            }

            public function read(string $id): string
            {
                $value = $this->cache->get('adminer_session:'.$id);

                return $value ? base64_decode($value) : '';
            }

            public function write(string $id, string $data): bool
            {
                return $this->cache->put('adminer_session:'.$id, base64_encode($data), $this->ttl);
            }

            public function destroy(string $id): bool
            {
                return $this->cache->forget('adminer_session:'.$id);
            }

            public function gc(int $max_lifetime): int
            {
                return 0;
            }
        });
    }

    private function defineAdminerObject(): void
    {
        require_once __DIR__.'/adminer_object.php';
    }
}
