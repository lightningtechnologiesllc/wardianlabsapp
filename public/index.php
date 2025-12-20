<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

Doctrine\Deprecations\Deprecation::ignoreDeprecations(
    'https://github.com/doctrine/orm/pull/12005', // Ignore proxy related deprecations from upstream packages
);

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
