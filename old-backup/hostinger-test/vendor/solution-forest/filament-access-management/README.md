
# filament-access-management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/run-tests?label=tests)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/solution-forest/filament-access-management/Check%20&%20fix%20styling?label=code%20style)](https://github.com/solution-forest/filament-access-management/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solution-forest/filament-access-management.svg?style=flat-square)](https://packagist.org/packages/solution-forest/filament-access-management)


This is an authentication plugin for Filament Admin with Laravel-permission

## Installation

1. Ensure you have already installed the Filament panel.
2. You can install the package via composer:
    ```bash
    composer require solution-forest/filament-access-management
    ```
    
3. Add the necessary trait to your User model:

    ```php

    use SolutionForest\FilamentAccessManagement\Concerns\FilamentUserHelpers;

    class User extends Authenticatable
    {
        use FilamentUserHelpers;
    }
    ```
    
4. **Clear your config cache**:
   ```bash
    php artisan optimize:clear
    # or
    php artisan config:clear
   ```

5. Register the plugin in your Panel provider:
   > **Important:  Register the plugin in your Panel provider after version 2.x**
   ``` bash
    use SolutionForest\FilamentAccessManagement\FilamentAccessManagementPanel;
 
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->plugin(FilamentAccessManagementPanel::make());
    }
   ```

6. Then execute the following commands:
   ```bash
   php artisan filament-access-management:install
   ```
   If you don't already have a user named `admin`, this command creates a **Super Admin User** with the following credentials:

    - Name: admin
    - E-mail address: admin@("slug" pattern from config("app.name")).com
    - Password: admin

    You can also create the super admin user with:

    ```bash
    php artisan make:super-admin-user
    ```

6. Call upgrade command to upgrade data after version **2.2.0**
    ```bash
    php artisan filament-access-management:upgrade
    ```


## Publish Configs, Views, Translations and Migrations

You can publish the configs, views, translations and migrations with:

```bash
php artisan vendor:publish --tag="filament-access-management-config"

php artisan vendor:publish --tag="filament-access-management-views"

php artisan vendor:publish --tag="filament-access-management-translations"

php artisan vendor:publish --tag="filament-access-management-migrations"
```

## Migration

```bash
php artisan migrate
```

## Usage

Upon installation, "Menu", "Users", "Roles" and "Permissions" pages will be created. Each user have roles and each role have permissions.

![image](https://user-images.githubusercontent.com/73818060/232434966-91ab94fe-620a-4894-8632-dbe5e535e5ae.png)

Manage Menu:
![image](https://user-images.githubusercontent.com/73818060/232438118-0b4089e7-4ff0-40b8-93b1-c6d4c089ef14.png)

Manage Users and their roles:
![image](https://user-images.githubusercontent.com/73818060/232437828-73039db1-8976-4a23-a14d-2943d9495a47.png)
![image](https://user-images.githubusercontent.com/73818060/232437890-2db887e1-dcbb-4d96-b072-365720be66d7.png)

Manage Roles and their permissions:
![image](https://user-images.githubusercontent.com/73818060/232438496-002b56d6-db98-4672-82cc-efcfc06fba9e.png)
![image](https://user-images.githubusercontent.com/73818060/232438548-29b655bc-d683-4924-90b7-6ba25991d7ff.png)

Manage Permissions:
![image](https://user-images.githubusercontent.com/73818060/232438632-e5d9a5e5-7ef5-4ca5-a330-37948acd9748.png)
![image](https://user-images.githubusercontent.com/73818060/232438719-fc2bca0b-7233-4aae-bf87-9c1d8524e42d.png)

## Routing control

In this plugin, permissions and routes are bound together, set the routes that the current permissions can access in the edit permissions page, select the method to access the routes in the `HTTP method` select box, and fill in the path that can be accessed in the `HTTP path`.

For example, if you want to add a permission, which can access the path `/admin/users` by `GET`, then `HTTP method` select `GET`, and `HTTP path` fill in `/users`.


If you want to access all the paths prefixed with `/admin/users`, then `HTTP path` fill in `/users*`; if you want to access the edit page, then `HTTP path` fill in `/users/*/edit`; if the method of each path in multiple paths is different, then `HTTP path` fill in `GET:users/*'. `.


If the above method is not sufficient, `HTTP path` also supports **routing aliases**, such as `admin.users.show`.

## Super Administrator

Create super admin user:

```bash

php artisan make:super-admin-user

```

Check permission:
```bash

# Check by permission's name
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::check($name)

# Check by http_path
\SolutionForest\FilamentAccessManagement\Http\Auth\Permission::checkPermission($path)

```

Get current user:
``` bash

\SolutionForest\FilamentAccessManagement\Facades\FilamentAuthenticate::user();

```

## Advance Usage

In default, the menu created will co-exist with the original menu of filament. To override the original menu with the menu from this package, modify `/config/filament-access-management.php` as following:

1. Set ```filament.navigation.enabled => true```

``` php

    'filament' => [
        ...
        'navigation' => [
            /**
             * Using db based filament navigation if true.
             */
            'enabled' => true,
            /**
             * Table name db based filament navigation.
             */
            'table_name' => 'filament_menu',
            /**
             * Filament Menu Model.
             */
            'model' => Models\Menu::class,
        ]
        ...
    ]

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Carly](https://github.com/n/a)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
