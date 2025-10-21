---
layout: default
title: The Fragment Directive component
---

# The Fragment Directive component

<p class="message-notice">available since version <code>7.6.0</code></p>

This specialized fragment component contains a list of `Directives` that can be used by user-agent
to further improve UX when navigating to or inside a website. As of time of this writing, only
the **Text Directive** is defined by the [URL Fragment Text Directives](https://wicg.github.io/scroll-to-text-fragment/)
but nothing preclute the addition of other directives in the future.

The component on itself includes the same public API as the generic [Fragment](/components/7.0/fragment/) class
and, in addition, provides methods to handle directives.

```php
use League\Uri\Components\FragmentDirective;
use League\Uri\Components\Directives\GenericDirective;
use League\Uri\Components\Directives\TextDirective;

$fragment = new FragmentDirective(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    new GenericDirective(name: 'foo', value: 'bar').
));

echo $fragment->toString();
//returns ":~:text=Deprecated-,attributes,attribute&foo=bar"
echo $fragment->getUriComponent();
// returns "#:~:text=Deprecated-,attributes,attribute&foo=bar"
```
As you can see with the example the `FragmentDirective` acts as a container for distinct directives.
You can use the following methods to navigate around the `Directives` container:

```php
use League\Uri\Components\Directives\Directive;

FragmentDirective::count(): int;
FragmentDirective::first(): ?Directive;
FragmentDirective::last(): ?Directive;
FragmentDirective::nth(int $offset): ?Directive;
FragmentDirective::has(int ...$offset): bool;
```
Apart from implementing the `Countable` interface, the `FragmentDirective` class implements
the `IteratorAggregate` interface to allow iterating over all the `Directives`, if needed.

```php
$fragment = new FragmentDirective(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    new GenericDirective(name: 'foo', value: 'bar').
));

foreach ($fragment as $directive) {
    echo $directive->toString();
}
```

The `FragmentDirective` allows you to manipulate its content using the following methods:

```php
FragmentDirective::append(Directive|Stringable|string ...$directives): self;
FragmentDirective::prepend(Directive|Stringable|string ...$directives): self;
FragmentDirective::replace(int $offset, Directive|Stringable|string $directive): self;
FragmentDirective::remove(int ...$offset): self;
FragmentDirective::slice(int $offset, ?int $length = null): self;
FragmentDirective::filter(callabck $callback): self;
```

<p class="message-notice">All the modifying methods return a new instance to make the class immutable.</p>

```php
$fragment = new FragmentDirective();
$newFragment = $fragment
    ->append(new GenericDirective(name: 'foo', value: 'bar'))
    ->prepend( new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'))
));

var_dump($fragment === $newFragment); // false
```

## The supported Directive

The package supports the following directives the text and the generic directive.

### Text Directive

The text directive is used in browsers to highlights section of a page:

```php
use League\Uri\Components\Directives\TextDirective;

$directive = new TextDirective(
    start: 'attributes',
    end: 'attribute',
    prefix: 'Deprecated',
    suffix: 'instead'
);
```
The class follows the specification of the [URL Fragment Text Directives](https://wicg.github.io/scroll-to-text-fragment/).
Apart from the `start` argument all the other arguments are optionals.

Once you have a `TextDirective` instance you can change any of its property using the following `wither-` methods.

```php
TextDirective::startingOn(string $start): self; //change the starting text
TextDirective::endingOn(?string $start): self; //change the optional ending text
TextDirective::leadedBy(?string $prefix): self; //change the prefix text
TextDirective::trailedBy(?string $suffix): self; //change the suffix text
```

All the methods return a new instance making the class immutable.

```php
echo new TextDirective('foo')
        ->startingOn('y&lo')
        ->endingOn('bar')
        ->leadBy('john')
        ->trailedBy('doe')
        ->toString();
// returns "text=john-,y%26lo,bar,-doe"
```

### Generic Directive

This directive is marked generic because it has no special effect.

```php
use League\Uri\Components\Directives\TextDirective;

$directive = new GenericDirective(name: 'foo', value: 'bar');
```

This class holds the minimum information needed to generate a `Directive`. It's use case
is to handle all the other `Directives` as long as they don't have their own specific syntax.
