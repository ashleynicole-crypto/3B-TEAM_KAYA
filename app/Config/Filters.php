<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf' => \CodeIgniter\Filters\CSRF::class,
        'toolbar' => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot' => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars' => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'forcehttps' => \CodeIgniter\Filters\ForceHTTPS::class,
    ];
    public array $required = ['before' => ['forcehttps'], 'after' => ['toolbar']];
    public array $globals = ['before' => ['csrf'], 'after' => []];
    public array $methods = [];
    public array $filters = [];
}
