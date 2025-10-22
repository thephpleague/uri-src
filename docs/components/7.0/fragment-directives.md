---
layout: default
title: The Fragment Directive component
---

# The Fragment Directives component

<p class="message-notice">available since version <code>7.6.0</code></p>

## The Component

This specialized fragment component contains a list of `Directives` that can be used by user-agent
to further improve UX when navigating to or inside a website. As of time of this writing, only
the **Text Directive** is defined by the [URL Fragment Text Directives](https://wicg.github.io/scroll-to-text-fragment/)
but nothing preclude the addition of other directives in the future.

The component on itself includes the same public API as the generic [Fragment](/components/7.0/fragment/) class
and, in addition, it provides methods to handle directives.

### Instantiation

```php
use League\Uri\Components\FragmentDirectives;
use League\Uri\Components\Directives\GenericDirective;
use League\Uri\Components\Directives\TextDirective;

$fragment = new FragmentDirectives(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    new GenericDirective(name: 'foo', value: 'bar'),
    'unknownDirective'
));

echo $fragment->toString();
//returns ":~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective"
echo $fragment->getUriComponent();
// returns "#:~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective"
```
As you can see with the example the `FragmentDirectives` acts as a container for distinct directives.

Directives can be submitted as specialized `Directive` class or as simple **encoded** directive string.
For ease of usage you can also create a new instance from a submitted URI:

```php
use League\Uri\Components\FragmentDirectives;

$fragment = FragmentDirectives::fromUri('https://example.com#:~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective');
count($fragment); //returns 3; the number of parsed directives.
```

### Component Accessor Methods

You can use the following methods to navigate around the `Directives` container:

```php
use League\Uri\Components\Directives\Directive;use League\Uri\Components\FragmentDirectives;

FragmentDirectives::count(): int;
FragmentDirectives::first(): ?Directive;
FragmentDirectives::last(): ?Directive;
FragmentDirectives::nth(int $offset): ?Directive;
FragmentDirectives::has(int ...$offset): bool;
FragmentDirectives::contains(Directive|Stringable|string $directive): bool;
FragmentDirectives::indexOf(Directive|Stringable|string $directive): ?int;
```

Apart from implementing the `Countable` interface, the `FragmentDirectives` class implements
the `IteratorAggregate` interface to allow iterating over all the `Directives`, if needed.

```php
$fragment = new FragmentDirectives(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    new GenericDirective(name: 'foo', value: 'bar').
));

foreach ($fragment as $directive) {
    echo $directive->toString();
}
```

### Component Manipulation Methods

The `FragmentDirectives` allows you to manipulate its content using the following methods:

```php
FragmentDirectives::append(Directive|Stringable|string ...$directives): self;
FragmentDirectives::prepend(Directive|Stringable|string ...$directives): self;
FragmentDirectives::replace(int $offset, Directive|Stringable|string $directive): self;
FragmentDirectives::remove(int ...$offset): self;
FragmentDirectives::slice(int $offset, ?int $length = null): self;
FragmentDirectives::filter(callabck $callback): self;
```

<p class="message-notice">All the modifying methods return a new instance to make the class immutable.</p>

```php
$fragment = new FragmentDirectives();
$newFragment = $fragment
    ->append(new GenericDirective(name: 'foo', value: 'bar'))
    ->prepend( new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'))
));

var_dump($fragment === $newFragment); // false
```

## The supported Directives

The package supports the Text Directive and the Generic Directive.

### Text Directive

The text directive is used in browsers to highlight page fragments:

```php
use League\Uri\Components\Directives\TextDirective;

$directive = new TextDirective(
    start: 'attributes',
    end: 'attribute',
    prefix: 'Deprecated',
    suffix: 'instead'
);
echo $directive; //display "text=Deprecated-,attributes,attribute,-instead"
```

when added in a fragment directive and applied on a webpage the text range which
starts with `attributes` and ends with `attribute` and which is preceded by
`Deprecated` and followed by `instead` will be highlighted. Depending on the
user agent, the browser may scroll up to the highlighted text when the page loads.

The class follows the specification of the [URL Fragment Text Directives](https://wicg.github.io/scroll-to-text-fragment/).
Apart from the `start` argument all the other arguments are optionals.

Once you have a `TextDirective` instance you can change any of its properties
using the following `wither-` methods.

```php
TextDirective::startsWith(string $text): self;  //change the starting text
TextDirective::endsWith(?string $text): self;   //change the optional ending text
TextDirective::precededBy(?string $text): self; //change the optional prefix context
TextDirective::followedBy(?string $text): self; //change the optional suffix context
```

All the methods return a new instance making the class immutable.

```php
echo new TextDirective('foo')
        ->startsWith('y&lo')
        ->endsWith('bar')
        ->precededBy('john')
        ->followedBy('doe')
        ->toString();
// returns "text=john-,y%26lo,bar,-doe"
```

### Generic Directive

This directive is marked generic because it has no special effect.

```php
use League\Uri\Components\Directives\GenericDirective;

$directive = new GenericDirective(name: 'foo', value: 'bar');
```

This class holds the minimum information needed to generate a `Directive`. It's use case
is to handle all the other `Directives` as long as they don't have their own specific syntax.
