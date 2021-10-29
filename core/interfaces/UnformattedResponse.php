<?php

declare(strict_types=1);

namespace core\interfaces;

use Psr\Http\Message\ResponseInterface;

interface UnformattedResponse extends ResponseInterface
{
    /**
     * Retrieve a single derived response attribute.
     *
     * Retrieves a single derived response attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute(string $attribute, $default = null);

    /**
     * Retrieve attributes derived from the response.
     *
     * The response "attributes" may be used to allow injection of any
     * parameters derived from the response: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and response specific, and CAN be mutable.
     *
     * @return array Attributes derived from the response.
     */
    public function getAttributes(): array;

    /**
     * Return an instance with the specified derived response attribute.
     *
     * This method allows setting a single derived response attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute(string $attribute, $value): ResponseInterface;

    /**
     * Return an instance that removes the specified derived response attribute.
     *
     * This method allows removing a single derived response attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute(string $attribute): ResponseInterface;

    /**
     * Return an instance with the specified unformatted response body data.
     *
     * @see withAttribute()
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withBodyData($value): ResponseInterface;

    /**
     * Return an instance that removes unformatted response body data.
     *
     * This method allows removing unformatted response body data.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see withBodyData()
     * @return static
     */
    public function withoutBodyData(): ResponseInterface;

    /**
     * Return unformatted response body data.
     *
     * @return mixed
     */
    public function getBodyData();

    /**
     * Checks if unformatted body data exists.
     *
     * @return bool Returns true if exists.
     */
    public function hasBodyData(): bool;

    /**
     * Set response body format, i.e. json, xml, html...
     * 
     * This determines how to convert `bodyData` into response content
     * 
     * @param string $format
     * 
     * @return ResponseInterface
     * @see \core\web\ContentType
     */
    public function withFormat(string $format): ResponseInterface;

    /**
     * Get response body format.
     * 
     * This determines how to convert `bodyData` into response content
     * 
     * @return string
     * @see \core\web\ContentType
     */
    public function getFormat(): string;

    /**
     * Set response body format.
     * 
     * @see \core\web\ContentType
     */
    public function setFormat(string $format);

    /**
     * Get response content charset.
     * 
     * @return string
     */
    public function getCharset(): string;

    /**
     * Set response content charset
     * 
     * @param string $charset
     * 
     * @return string
     */
    public function setCharset(string $charset);
}
