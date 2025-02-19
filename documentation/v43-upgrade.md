
# SproutCMS 4.3

Seemingly, we've been abiding by semantic version since the 4.0 release.
However we've been saving up a few big changes and it was necessary to introduce another upgrade doc.


## New things


### Neon Forms

_Credit: `gwillz`_

JSON forms without the brain hemorrhage.


### Model Queries

_Credit: `gwillz`_

A base query class to fetch models by id/uid/date. This also serves as an example for extending the model query further.


### Checksum Media

_Credit: `gwillz`_

This solves stale media caches by checksumming the whole module and cache busting not only the browser, but also nginx/apache.

No longer do deployment require `media_tools/clean` in their deployments. Although this is still recommended.


### Smart Redirects

_Credit: `gwillz`_

Sprout has always created redirects when replacing a file in the media repo. However it now directly references the target file instead of a fixed download link. This reduces load on the Sprout application but also preserves the download filename for end users.

Sprout now also creates redirects for pages when updating the slug or parent page.


### Redis Cache Driver

_Credit: `gwillz`_

Back your application cache with Redis.


### S3 Files Backend

_Credit: `jamiemonksuk`_

Store files in AWS S3 (or API compatible service).


### PDB v1.0

_Credit: `gwillz`_

PDB has been quietly chuffing along in a "pre-release" version and an official v1.0 release long overdue. A full release means committing to the API as it is and forcing intentional decisions if we need to break things on major versions.

With this we also get a few new fun features.


- Conditions Interface
- Nested Transactions
- Timezone support
- Pdb Schemas



## Upgrading


```sh
composer require -W sproutcms/cms:4.3.*
```


### Environment Variables

Sprout previously loaded 'dotenv' files in the `BASE_PATH` automatically. This proved inflexible for some deployments.

The recommended migration:

1. Install the dotenv library: `composer require vlucas/phpdotenv:^5`

2. Modify the entrypoint `index.php` file:

```php
require VENDOR_PATH . 'autoload.php';

// -- LOAD ENV HERE --
Dotenv\Dotenv::createUnsafeImmutable(BASE_PATH)->safeLoad();

return require  VENDOR_PATH . 'sproutcms/cms/src/bootstrap.php';
```

The [Sprout Template](https://github.com/Karmabunny/sprout3-site) also installs the dotenv method by default.




### Model Rules

Model rules have always been a bit weak, compared to their origins in the `Validator` class. This introduces upgraded class based validators, enabling them to implicitly understand multi-check rules or understand context about the model that it is validating.

A migration for some existing rules is required. Basically any usage of `args`. Each validator now describes it's own parser to determine arguments.

Although the `key => value` syntax is still supported, a nested form is recommended.

```php
// OLD
'required' => ['name', 'email', 'password', 'mobile'],
'uniqueValue' => ['email', 'args' => ['users', 'email', 0, 'Email already registered']],
'length' => [
    ['name', 'args' => [0, 100]],
    ['email', 'args' => [0, 200]],
    ['password', 'args' => [0, 200]],
    ['mobile', 'args' => [0, 50]],
],

// NEW
[ 'required' => ['name', 'abn', 'email'] ],
[ 'uniqueValue' => ['email', 'message' => 'Email already registered'] ],
[ 'length', 'min' => 0, 'max'=> 100 ],
[ 'email', 'password', 'min' => 0, 'max'=> 200 ],
[ 'mobile', 'min' => 0, 'max'=> 50 ],
```


### Raw SQL conditions

PDB v1.0 no longer accepts 'unsafe' string conditions for it's `buildClause()` helpers and related `PdbQuery` methods.

To use unescaped, raw SQL it must be explicitly declared by using the `PdbRawCondition` class:

```php
Pdb::update('my_table', $data, [ new PdbRawCondition('1=1') ]);
```

Raw conditions do permit parameter interpolation. This is useful if one needs to write something complex that the condition builder cannot produce:

```php
Pdb::update('my_table', $data, [
    'active' => 1,
    new PdbRawCondition("first_name = CONCAT(?, last_name)", [$name]),
]);
```

__An exception__: the `PdbQuery::join()` helpers will naturally accept raw SQL for `ON` clauses:

```php
Pdb::find('my_table')
    ->innerJoin('other_table', '~my_table.id = ~other_table.target_id')
    ->all();
```


### Nested Transactions

PDB v1.0 introduces savepoints and nested transactions.

With the `TX_ENABLE_NESTED` config, the `transact()` method will not raise the `TransactionRecursionException` if called twice without commit/rollback. Instead it creates a savepoint.

A standard `commit()/rollback()` will still capture the whole transaction. However, provide the savepoint name and only changes since the inner transaction will be saved/discarded.

To aid this process, a few helpers:

```php
$tx1 = $pdb->transact();
$tx2 = $pdb->transact();

echo $tx1->key, "\n";
// => tx_{uuid} (outer transaction)

echo $tx2->key, "\n";
// => save_{uuid} (inner transaction)

// Undo changes since TX2.
$tx2->rollback();

// Now commit changes before TX2.
$tx1->commit();
```

Users can also use savepoint() directly with their own names:

```php
$pdb->transact();
$pdb->savepoint('savepoint-1');

// inner
$pdb->commit('savepoint-1');

// outer
$pdb->commit();
```

The `withTransaction()` helper makes most things easier:

```php
// Create transaction or savepoint.
$pdb->withTransaction(function($pdb, $transaction) => {
    echo $transaction->key, "\n";
    $count = $pdb->delete('my-table', ['active' => 0]);

    // An exception triggers a rollback.
    if ($count > 10) {
        throw new Exception('Too many deletes');
    }

    // Otherwise the transaction is committed.
});
```

Most applications will not need to do any migration unless they rely on `TransactionRecursionException`.


### Timezones

PDB v1.0 introduces a timezone aware `now()` helper. This means two things:

1. For non-static PDB, the `now()` method is an instance method.

2. The `Pdb::now()` and SQL `NOW()` helpers have the same timezone.

The `database.use_system_timezone` setting controls whether PHP (system) or the database is used to determine the authoritative timezone.

Alternatively, an explicit timezone can be set using the `database.session` config:

```php
$config['default'] = [
    ...
    'use_system_timezone' => false,
    'session' => [
        'time_zone' => 'Australia/Melbourne',
    ],
];
```


### Media Tags

We introduced the `Media::tag()` helper to simplify writing HTML links for resources. Carelessly, we made the shorthand format more confusing then it needed to me.

If anyone was silly enough to find and use this helper, a migration looks like this:

```php
// Old
Media::tag('MyModule/print.css');

// New
Media::tag('modules/MyModule/css/print.css');
```

