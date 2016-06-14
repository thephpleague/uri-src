<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package   League.uri
 * @author    Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.2.0
 * @link      https://github.com/thephpleague/uri/
 */
namespace League\Uri\Components;

use League\Uri\Interfaces\User as UserInterface;

/**
 * Value object representing a URI user component.
 *
 * @package League.uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   1.0.0
 */
class User extends AbstractComponent implements UserInterface
{
    /**
     * new instance
     *
     * @param string|null $data the component value
     */
    public function __construct($data = null)
    {
        if ($data !== null) {
            $data = $this->validateString($data);
            $this->data = $this->decodeComponent($data);
        }
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return $this->encodeUser((string) $this->data);
    }

    /**
     * @inheritdoc
     */
    public function __debugInfo()
    {
        return ['user' => $this->__toString()];
    }
}
