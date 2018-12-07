# Spaceland

List available classes/traits/functions in a PHP project

## Why

The primary usage is to automate the use of the `use` statements in
editors like `Emacs` or `Vim`.

When you write in your editor something like

``` php
$app->get('/ping, function(Request $req) {
    // ...
});
```

You need to remember that the full name of `Request` is
`Symfony\Component\HttpFoundation\Request` so that you can add the
appropriate use statement

``` php
use Symfony\Component\HttpFoundation\Request;
```

With `spaceland` you can explore your project and its dependencies so
that you can automate the resolution process

``` shell
./vendor/bin/spaceland locate:classes | grep Request
```

## Installation

``` shell
composer require --dev gabrielelana/spaceland
```
