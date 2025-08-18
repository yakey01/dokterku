<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Enable / disable Google2FA.
    |
    */

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Alias
    |--------------------------------------------------------------------------
    |
    | The alias for the Google2FA Facade.
    |
    */

    'alias' => 'Google2FA',

    /*
    |--------------------------------------------------------------------------
    | View
    |--------------------------------------------------------------------------
    |
    | The view used to ask for a one time password.
    |
    */

    'view' => 'google2fa.index',

    /*
    |--------------------------------------------------------------------------
    | One Time Password request input name
    |--------------------------------------------------------------------------
    */
    'otp_input' => 'one_time_password',

    /*
    |--------------------------------------------------------------------------
    | One Time Password Window
    |--------------------------------------------------------------------------
    |
    | How many minutes around the current time should we check for a valid
    | OTP? This is used to allow for small clock drift between the
    | authenticator and the server.
    |
    */

    'window' => 4,

    /*
    |--------------------------------------------------------------------------
    | Forbid registration if the user is already registered
    |--------------------------------------------------------------------------
    |
    | When users are trying to register again, forbid or allow them to do so.
    |
    */

    'forbid_old_passwords' => true,

    /*
    |--------------------------------------------------------------------------
    | User's database column for google2fa secret
    |--------------------------------------------------------------------------
    |
    | Users table column used to store the secret.
    |
    */

    'otp_secret_column' => 'google2fa_secret',

    /*
    |--------------------------------------------------------------------------
    | Recovery
    |--------------------------------------------------------------------------
    |
    | When a user loses access to their phone, they can use a recovery code
    | to login. Every time a recovery code is used, a new one is generated.
    |
    */

    'recovery' => [
        'enabled' => true,
        'codes' => 8,
        'column' => 'recovery_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safe devices
    |--------------------------------------------------------------------------
    |
    | When a user adds a device to the safe list, OTP won't be required for
    | that device anymore.
    |
    */

    'safe_devices' => [
        'enabled' => false,
        'cookie' => 'google2fa_safe_device',
        'days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Keep OTP secret in case of an invalid OTP
    |--------------------------------------------------------------------------
    |
    | Instead of clearing the OTP secret on every invalid OTP, keep it around
    | and require the user to retry.
    |
    */

    'keep_otp_secret' => true,

];