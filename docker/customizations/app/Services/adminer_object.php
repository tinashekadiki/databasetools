<?php

/**
 * Global adminer_object() function. Adminer discovers this function from the
 * root namespace when the vendor adminer.php file is loaded.
 */
function adminer_object()
{
    $credentials = $GLOBALS['_adminer_credentials'] ?? null;
    $cssPaths = $GLOBALS['_adminer_css_paths'] ?? ['/css/adminer.css'];

    return new class($credentials, $cssPaths) extends \Adminer\Adminer
    {
        /**
         * @param  array<string, string>|null  $creds
         * @param  array<int, string>  $cssPaths
         */
        public function __construct(
            private ?array $creds,
            private array $cssPaths,
        ) {}

        /** @return array{string, string, string} */
        public function credentials(): array
        {
            if ($this->creds) {
                return [$this->creds['server'], $this->creds['username'], $this->creds['password']];
            }

            return parent::credentials();
        }

        public function login($login, $password)
        {
            return true;
        }

        public function headers()
        {
            header('X-Frame-Options: SAMEORIGIN');
        }

        public function head($dark = null)
        {
            echo <<<'HTML'
<script>
document.addEventListener('DOMContentLoaded', function () {
    function ensureAdminerLoader() {
        var loader = document.getElementById('dot-adminer-loader');

        if (loader) {
            return loader;
        }

        loader = document.createElement('div');
        loader.id = 'dot-adminer-loader';
        loader.setAttribute('role', 'status');
        loader.setAttribute('aria-live', 'polite');
        loader.innerHTML = '<span class="dot-adminer-spinner"></span><span>Running database operation</span>';
        document.body.appendChild(loader);

        return loader;
    }

    function showAdminerLoader(label) {
        var loader = ensureAdminerLoader();
        var text = loader.querySelector('span:last-child');

        if (text && label) {
            text.textContent = label;
        }

        loader.classList.add('is-visible');
        document.documentElement.classList.add('dot-adminer-busy');
    }

    function shouldShowForLink(link) {
        if (!link || !link.href) {
            return false;
        }

        if (link.target && link.target !== '_self') {
            return false;
        }

        if (link.hasAttribute('download')) {
            return false;
        }

        var href = link.getAttribute('href') || '';

        return href !== '' && href.charAt(0) !== '#' && !href.startsWith('javascript:');
    }

    function preferDataView(link) {
        if (!link || !link.href || !link.href.includes('table=')) {
            return;
        }

        link.href = link.href.replace('table=', 'select=');
        link.title = 'Select data';
        link.dataset.dotDefaultAction = 'select-data';
    }

    document.querySelectorAll('#menu li').forEach(function (item) {
        var links = Array.from(item.querySelectorAll('a'));

        links.forEach(function (link) {
            if (link.href && link.href.includes('table=')) {
                preferDataView(link);
            }

            if (!link.textContent.trim() && link.href && link.href.includes('select=')) {
                link.classList.add('dot-adminer-icon-link');
                link.setAttribute('aria-hidden', 'true');
            }
        });
    });

    document.querySelectorAll('#menu li a[href*="table="]').forEach(preferDataView);
    document.querySelectorAll('#content th a[id^="Table-"][href*="table="]').forEach(preferDataView);

    ensureAdminerLoader();

    document.addEventListener('submit', function (event) {
        var submitter = event.submitter;
        var label = submitter && submitter.value ? submitter.value : 'Running database operation';

        showAdminerLoader(label);
    }, true);

    document.addEventListener('click', function (event) {
        var button = event.target.closest('button, input[type="submit"]');

        if (button) {
            showAdminerLoader(button.value || button.textContent.trim() || 'Running database operation');
            return;
        }

        var link = event.target.closest('a');

        if (shouldShowForLink(link)) {
            showAdminerLoader(link.textContent.trim() || 'Loading database view');
        }
    }, true);

    window.addEventListener('beforeunload', function () {
        showAdminerLoader('Loading database view');
    });

    window.addEventListener('pageshow', function () {
        var loader = ensureAdminerLoader();

        loader.classList.remove('is-visible');
        document.documentElement.classList.remove('dot-adminer-busy');
    });
});
</script>
HTML;

            return true;
        }

        /**
         * @return array<int, string>
         */
        public function css()
        {
            return $this->cssPaths;
        }
    };
}
