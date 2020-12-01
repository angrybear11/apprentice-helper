<img alt="Apprentice icon with Mona Lisa" src="images/banner@2x.png" width="100%">
<br>
Apprentice makes running Artisan commands in your Laravel project easy and painless.

- Remotely manage your projects on the go
- Run commands securely and safely
- No terminal required!\*

To learn more about Apprentice [check out our website](http://getapprentice.com).

Requires Laravel >= 7.2

<sub>\*you will need terminal to install Apprentice</sub>

# Installation

Connecting Apprentice to your project is easy.

1. Install via Composer

```
composer require voronoi/apprentice
```

2. Run the setup command

```
php artisan apprentice:setup
```

3. Scan the generated QR code using the [Apprentice app]()

You're all ready to use Apprentice!

# Security

We take security very seriously in Apprentice. Among other precautions, access to your project is secured using a 256-bit ECDSA key securely stored within the Secure Enclave on your device. For more details about how we protect your data, reporting potential issues, and security updates see `security.md`.

# Tests

If you would like to run the unit tests make sure you have the latest dev dependencies by running `composer install`. Then execute

```
vendor/bin/phpunit tests
```
