<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin seed PIN
    |--------------------------------------------------------------------------
    |
    | Used only by AdminUserSeeder to bootstrap the local/initial admin
    | account. Read via config() rather than a raw env() call, because
    | env() outside of config files silently falls back to its default
    | once `config:cache` is active in production (the .env file itself
    | is skipped at that point) - routing it through config() here keeps
    | it working correctly whether or not config is cached.
    |
    */

    'admin_seed_pin' => env('ADMIN_SEED_PIN', '123456'),

];
