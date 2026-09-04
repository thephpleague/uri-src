<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\UriTemplate;

use BackedEnum;
use Deprecated;
use League\Uri\Exceptions\SyntaxError;
use Stringable;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function preg_replace;
use function preg_replace_callback;
use function preg_split;
use function rawurlencode;
use function str_starts_with;
use function strpbrk;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

/**
 * @internal The class exposes the internal representation of a Template and its usage
 */
final class Template implements Stringable
{
    /**
     * Expression regular expression pattern.
     */
    private const REGEXP_EXPRESSION_DETECTOR = '/(?<expression>\{[^}]*})/x';

    /**
     * Literal characters which must be percent encoded in the expanded
     * template: everything outside "unreserved / reserved / pct-encoded", a
     * "%" starting no triplet included.
     *
     * @link https://www.rfc-editor.org/rfc/rfc6570#section-3.1
     */
    private const REGEXP_LITERAL_TO_ENCODE = '/[^A-Za-z\d\-._~:\/?#\[\]@!$&\'()*+,;=%]+|%(?![A-Fa-f\d]{2})/';

    /** @var array<string|Expression> */
    private readonly array $parts;
    /** @var array<string> */
    public readonly array $variableNames;

    private function __construct(public readonly string $value, string|Expression ...$parts)
    {
        $this->parts = $parts;

        $expressions = [];
        foreach ($parts as $part) {
            if ($part instanceof Expression) {
                $expressions[$part->value] = $part;
            }
        }

        $this->variableNames = array_unique(
            array_merge(
                ...array_map(
                    static fn (Expression $expression): array => $expression->variableNames,
                    array_values($expressions)
                )
            )
        );
    }

    /**
     * @throws SyntaxError if the template contains invalid expressions
     * @throws SyntaxError if the template contains invalid variable specification
     */
    public static function new(BackedEnum|Stringable|string $template): self
    {
        if ($template instanceof BackedEnum) {
            $template = $template->value;
        }

        $template = (string) $template;
        /** @var string $remainder */
        $remainder = preg_replace(self::REGEXP_EXPRESSION_DETECTOR, '', $template);
        false === strpbrk($remainder, '{}') || throw new SyntaxError('The template "'.$template.'" contains invalid expressions.');

        /** @var array<string> $segments */
        $segments = preg_split(self::REGEXP_EXPRESSION_DETECTOR, $template, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $parts = [];
        $expressions = [];
        foreach ($segments as $segment) {
            if (!str_starts_with($segment, '{')) {
                $parts[] = self::encodeLiteral($segment);
                continue;
            }

            $parts[] = $expressions[$segment] ??= Expression::new($segment);
        }

        return new self($template, ...$parts);
    }

    /**
     * Percent encodes the template characters which are not allowed to be copied as-is into a URI.
     *
     * @link https://www.rfc-editor.org/rfc/rfc6570#section-3.1
     */
    private static function encodeLiteral(string $literal): string
    {
        return (string) preg_replace_callback(
            self::REGEXP_LITERAL_TO_ENCODE,
            static fn (array $matches): string => rawurlencode($matches[0]),
            $literal
        );
    }

    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid
     */
    public function expand(iterable $variables = []): string
    {
        if (!$variables instanceof VariableBag) {
            $variables = new VariableBag($variables);
        }

        return $this->expandAll($variables);
    }

    /**
     * @throws TemplateCanNotBeExpanded if the variables are invalid or missing
     */
    public function expandOrFail(iterable $variables = []): string
    {
        if (!$variables instanceof VariableBag) {
            $variables = new VariableBag($variables);
        }

        $missing = array_filter($this->variableNames, fn (string $name): bool => !isset($variables[$name]));
        if ([] !== $missing) {
            throw TemplateCanNotBeExpanded::dueToMissingVariables(...$missing);
        }

        return $this->expandAll($variables);
    }

    private function expandAll(VariableBag $variables): string
    {
        return implode('', array_map(
            static fn (string|Expression $part): string => $part instanceof Expression ? $part->expand($variables) : $part,
            $this->parts
        ));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @throws SyntaxError if the template contains invalid expressions
     * @throws SyntaxError if the template contains invalid variable specification
     * @deprecated Since version 7.0.0
     * @codeCoverageIgnore
     * @see Template::new()
     *
     * Create a new instance from a string.
     *
     */
    #[Deprecated(message:'use League\Uri\UriTemplate\Template::new() instead', since:'league/uri:7.0.0')]
    public static function createFromString(Stringable|string $template): self
    {
        return self::new($template);
    }
}
