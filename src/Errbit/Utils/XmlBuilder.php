<?php
declare(strict_types=1);
namespace Errbit\Utils;

use DOMElement;
use SimpleXMLElement;

/**
 * Like Nokogiri, but shittier.
 *
 * SimpleXML's addChild() and friends don't escape the XML, so this wraps
 * simplexml in a very specific way.
 *
 * Lambdas are used to construct a full tree of XML.
 *
 * @example
 *
 *   $builder = new Errbit_XmlBuilder();
 *   $builder->tag('product', function($product) {
 *     $product->tag('name', 'Plush Puppy Toy');
 *     $product->tag('price', '$8', array('currency' => 'USD'));
 *     $product->tag('discount', function($discount) {
 *       $discount->tag('percent', 20);
 *       $discount->tag('name',    '20% off promotion');
 *     });
 *   })
 *   ->asXml();
 */
class XmlBuilder
{
    private \SimpleXMLElement $_xml;
    /**
     * Instantiate a new XmlBuilder.
     *
     * @param SimpleXMLElement $xml the parent node (only used internally)
     */
    public function __construct(?\SimpleXMLElement $xml = null)
    {
        $this->_xml = $xml ?: new \SimpleXMLElement('<__ErrbitXMLBuilder__/>');
    }

    /**
     * Insert a tag into the XML.
     *
     * @param string $name the name of the tag, required
     * @param mixed $value the text value of the element, optional
     * @param array<string, mixed> $attributes an array of attributes for the tag, optional
     * @param callable|null $callback a callback to receive an XmlBuilder for the new tag, optional
     * @param bool $getLastChild whether to get the last child element
     *
     * @return XmlBuilder a builder for the inserted tag
     */
    public function tag(string $name, mixed $value = '', array $attributes = [], ?callable $callback = null, bool $getLastChild = false): XmlBuilder
    {
        $idx = is_countable($this->_xml->$name) ? count($this->_xml->$name) : 0;

        $this->_xml->{$name}[$idx] = $this->normalizeValue($value);

        $childElement = null;
        if (isset($this->_xml->{$name}[$idx]) && $this->_xml->{$name}[$idx] instanceof SimpleXMLElement) {
            $childElement = $this->_xml->{$name}[$idx];
        } elseif ($this->_xml->{$name} instanceof SimpleXMLElement) {
            $childElement = $this->_xml->{$name};
        }

        if ($childElement instanceof SimpleXMLElement) {
            foreach ($attributes as $attr => $v) {
                if (!is_string($attr) || $attr === '') {
                    continue;
                }
                $this->setAttribute($childElement, $attr, $this->normalizeValue($v));
            }
        }

        $node = new self($childElement instanceof SimpleXMLElement ? $childElement : null);
        if ($getLastChild) {
            $array = $this->_xml->xpath($name."[last()]");
            if (is_array($array)) {
                $xml = array_shift($array);
                if ($xml instanceof \SimpleXMLElement) {
                    $node = new self($xml);
                }
            }
        }

        if ($callback) {
            $callback($node);
        }

        return $node;
    }

    /**
     * Add an attribute to the current element.
     *
     * @param string $name the name of the attribute
     * @param mixed $value the value of the attribute
     *
     * @return static the current builder
     */
    public function attribute(string $name, mixed $value): static
    {
        if ($name === '') {
            return $this;
        }

        $this->setAttribute($this->_xml, $name, $this->normalizeValue($value));

        return $this;
    }

    /**
     * Cast any scalar or object value into a string for XML nodes.
     */
    private function normalizeValue(mixed $value): string
    {
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return sprintf('[%s]', $value::class);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value) {
            return '';
        }

        if (is_resource($value)) {
            return sprintf('[resource:%s]', get_resource_type($value));
        }

        if (is_array($value)) {
            return '[array]';
        }

        return (string) $value;
    }

    /**
     * Return this XmlBuilder as a string of XML.
     *
     * @return string the XML of the document
     */
    public function asXml(): string
    {
        $xml = $this->_xml->asXML();
        return self::utf8ForXML($xml !== false ? $xml : '');
    }

    /**
     * Util to converts special chars to be valid with xml
     *
     * @param string $string xml string to converte the special chars
     *
     * @return string escaped string
     */
    public static function utf8ForXML($string)
    {
        $filtered = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);

        return is_string($filtered) ? $filtered : '';
    }

    private function setAttribute(SimpleXMLElement $element, string $name, string $value): void
    {
        $domNode = dom_import_simplexml($element);
        if (!$domNode instanceof DOMElement) {
            return;
        }

        $domNode->setAttribute($name, $value);
    }
}
