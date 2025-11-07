---
layout: default
title: The Fragment Directive component
---

# The Fragment Directives component

<p class="message-notice">Available since version <code>7.6.0</code></p>

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
use League\Uri\Components\FragmentDirectives\GenericDirective;
use League\Uri\Components\FragmentDirectives\TextDirective;

$fragment = new FragmentDirectives(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    GenericDirective::fromString('foo=bar'),
    'unknownDirective'
));

echo $fragment->toString();
//returns ":~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective"
echo $fragment->getUriComponent();
// returns "#:~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective"
```
As you can see with the example the `FragmentDirectives` acts as a container for distinct directives.

Directives can be provided either as a specialized`Directive` object or as simple **encoded** directive string.

For convenience, a `FragmentDirectives` instance can be created using one of the named constructors:

- `fromUri()` — creates an instance from a full URI.
- `fromFragment()` — creates an instance from a fragment component.
- `new()` — creates an instance from a raw string.

```php
use League\Uri\Components\FragmentDirectives;

$fragment = FragmentDirectives::fromUri('https://example.com#section2:~:text=Deprecated-,attributes,attribute&foo=bar&unknownDirective');
count($fragment); //returns 3; the number of parsed directives.
```

The `fromUri()` method extracts the fragment component from the given URI and initializes a new
`FragmentDirectives` object.

<p class="message-info">The fragment directive does not need to represent the entire fragment value.
As shown in the example above, the directive’s delimiter (<code>:~:</code>) does not need to appear
at the start of the fragment.</p> 

Both `fromUri()` and `fromFragment()` allow the delimiter to appear anywhere in the input string.
In contrast, the `new()` constructor expects a string **without any delimiter**; providing an
invalid input will result in an exception.

### Accessing Properties

You can use the following methods to navigate around the `Directives` container:

```php
FragmentDirectives::isEmpty(): bool;
FragmentDirectives::count(): int;
FragmentDirectives::first(): ?Directive;
FragmentDirectives::last(): ?Directive;
FragmentDirectives::nth(int $offset): ?Directive;
FragmentDirectives::has(int ...$offset): bool;
FragmentDirectives::contains(FragmentDirective|Stringable|string $directive): bool;
FragmentDirectives::indexOf(FragmentDirective|Stringable|string $directive): ?int;
```

Apart from implementing the `Countable` interface, the `FragmentDirectives` class implements
the `IteratorAggregate` interface to allow iterating over all the `Directives`, if needed.

```php
$fragment = new FragmentDirectives(
    new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'),
    GenericDirective::fromString('foo=bar').
));

foreach ($fragment as $directive) {
    echo $directive->toString();
}
```

### Modifying Properties

The `FragmentDirectives` allows you to manipulate its content using the following methods:

```php
FragmentDirectives::append(FragmentDirective|Stringable|string ...$directives): self;
FragmentDirectives::prepend(FragmentDirective|Stringable|string ...$directives): self;
FragmentDirectives::replace(int $offset, FragmentDirective|Stringable|string $directive): self;
FragmentDirectives::remove(int ...$offset): self;
FragmentDirectives::slice(int $offset, ?int $length = null): self;
FragmentDirectives::filter(callabck $callback): self;
```

<p class="message-notice">All the modifying methods return a new instance to make the class immutable.</p>

```php
$fragment = new FragmentDirectives();
$newFragment = $fragment
    ->append(GenericDirective::fromString('foo=bar'))
    ->prepend(new TextDirective(start: 'attributes', end: 'attribute', prefix: 'Deprecated'))
));

$fragment->equals($newFragment); //returns false
```

## The Supported Directives

While the `FragmentDirectives` class allows submitting Directives as string, it is highly recommended to
use a dedicated class to do so, as the grammar around building or parsing directives may be complex in
regard to encoding characters and/or delimiters usage.

The `FragmentDirectives` class supports the `TextDirective` and the `GenericDirective` classes. Both classes
implement the following `FragmentDirective` interface.

```php
FragmentDirective::name(): string
FragmentDirective::value(): ?string
FragmentDirective::equals(mixed $value): ?string
FragmentDirective::toString(): string
FragmentDirective::__toString(): string
```

A directive is composed of two parts separated by the `=` separator. The name is required as it
defines the directive syntax, while its value **MAY** be optional. When the value is not defined,
the separator is omitted. The `name()` and `value()` methods return the **decoded** value of
the directive part whereas the `toString()` method returns its encoded string representation.
The `__toString()` method is an alias of the `toString()` method.

### Text Directive

The text directive is used in browsers to highlight page fragments. It is represented by the `TextDirective` class.

```php
use League\Uri\Components\FragmentDirectives\TextDirective;

$directive = new TextDirective(
    start: 'attributes',
    end: 'attribute',
    prefix: 'Deprecated',
    suffix: 'instead'
);
echo $directive->name();  //display "text"
echo $directive->value(); //display "Deprecated-,attributes,attribute,-instead"
echo $directive;          //display "text=Deprecated-,attributes,attribute,-instead"
```

<p class="message-notice">the <code>-</code>, <code>&</code> and <code>,</code> characters
are special and <strong>must</strong> be encoded if found in the text to avoid parsing errors.</p>

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
$directive = new TextDirective('foo')
        ->startsWith('y&lo')
        ->endsWith('bar')
        ->precededBy('john')
        ->followedBy('doe');

$directive->name();     // returns 'text';
$directive->value();    // returns "john-,y&lo,bar,-doe" the decoded value
$directive->toString(); // returns "text=john-,y%26lo,bar,-doe"
```

### Generic Directive

This directive is considered **generic** because it only meets the minimal syntax requirements of a Directive.
Unlike the `TextDirective` class, it does not perform any additional parsing or validation around the directive value.
As a result, it can only be instantiated from a directive’s string representation.

```php
use League\Uri\Components\FragmentDirectives\GenericDirective;

$directive = GenericDirective::fromString('fo%26o=bar');
$directive->value(); //returns "bar"
$directive->name();  //returns "fo&o"
```

It's use case is to handle all the other `Directives` as long as they don't have their own specific syntax.

### Directive equivalence

The `equals()` method compares two directives based on their string representations.
It returns `true` if both representations are identical, and `false` otherwise.

```php
use League\Uri\Components\FragmentDirectives\GenericDirective;
use League\Uri\Components\FragmentDirectives\TextDirective;

$directive1 = GenericDirective::fromString('fo%26o=bar');
$directive2 = new TextDirective('foo')
        ->startsWith('y&lo')
        ->endsWith('bar')
        ->precededBy('john')
        ->followedBy('doe');

$directive3 = GenericDirective::fromString($directive2);

$directive1->equals($directive2);  // returns false
$directive1->equals($directive3);  // returns true
$directive1->equals('fo%26o=bar'); // returns true
```

### Directive Resolver

The `DirectivString` class ease resolving to the correct `Directive` object on
creation.

```php
use League\Uri\Components\FragmentDirectives\DirectiveString;

$directive = DirectiveString::resolve('fo%26o=bar'); // returns a GenericDirective instance
$directive = DirectiveString::resolve('text=start,end'); // returns a TextDirective instance
```

If and when a new type of directives is created or added, the class will automatically
use it once it is implemented.
