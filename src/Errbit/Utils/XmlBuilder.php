<?php
namespace Errbit\Utils;

/**
 * Errbit PHP Notifier.
 *
 * Copyright © Flippa.com Pty. Ltd.
 * See the LICENSE file for details.
 */

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
    /**
     * Instantiate a new XmlBuilder.
     *
     * @param SimpleXMLElement $xml the parent node (only used internally)
     */
    public function __construct($xml = null)
    {
        $this->_xml = $xml ? $xml : new \SimpleXMLElement('<__ErrbitXMLBuilder__/>');
    }

    /**
     * Insert a tag into the XML.
     *
     * @param string   $name       the name of the tag, required.
     * @param string   $value      the text value of the element, optional
     * @param array    $attributes an array of attributes for the tag, optional
     * @param Callable $callback   a callback to receive an XmlBuilder for the new tag, optional
     *
     * @return XmlBuilder a builder for the inserted tag
     */
    /**
     * Insert a tag into the XML.
     *
     * @param string   $name       the name of the tag, required.
     * @param string   $value      the text value of the element, optional
     * @param array    $attributes an array of attributes for the tag, optional
     * @param Callable $callback   a callback to receive an XmlBuilder for the new tag, optional
     *
     * @return XmlBuilder a builder for the inserted tag
     */
    public function tag($name, $value = '', $attributes = array(), $callback = null, $getLastChild = false)
    {

        $idx = count($this->_xml->$name);

        if (is_object($value)) {
            $value = "[" . get_class($value) . "]";
        } else {
                $value = (string) $value;
        }

        $this->_xml->{$name}[$idx] = $value;

        foreach ($attributes as $attr => $v) {
            $this->_xml->{$name}[$idx][$attr] = $v;
        }
        $node = new self($this->_xml->$name);
        if ($getLastChild) {
            $array = $this->_xml->xpath($name."[last()]");
            $xml = array_shift($array);
            $node = new self($xml);
        }

        if ($callback) {
            $callback($node);
        }

        return $node;
    }

    /**
     * Add an attribute to the current element.
     *
     * @param String $name  the name of the attribute
     * @param String $value the value of the attribute
     *
     * @return XmlBuilder the current builder
     */
    public function attribute($name, $value)
    {
        $this->_xml[$name] = $value;

        return $this;
    }

    /**
     * Return this XmlBuilder as a string of XML.
     *
     * @return [String]
     *   the XML of the document
     */
    public function asXml()
    {
        return self::utf8ForXML($this->_xml->asXML());
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
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
    }
}
