<?php
declare(strict_types=1);
namespace Errbit\Utils;

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
 *   $builder = new ErrbitxmlBuilder();
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
    private $xml;
    
    /**
     * Instantiate a new XmlBuilder.
     *
     * @param \SimpleXMLElement|null $xml the parent node (only used internally)
     */
    public function __construct(private ?\SimpleXMLElement $xml = null)
    {
        $this->$xml = $xml ?: new \SimpleXMLElement('<__ErrbitXMLBuilder__/>');
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
    public function tag($name, $value = '', $attributes = [], $callback = null, bool $getLastChild = false): XmlBuilder
    {

        $idx = is_countable($this->xml->$name) ? count($this->xml->$name) : 0;

        if (is_object($value)) {
            $value = "[" . $value::class . "]";
        } else {
                $value = (string) $value;
        }

        $this->xml->{$name}[$idx] = $value;

        foreach ($attributes as $attr => $v) {
            $this->xml->{$name}[$idx][$attr] = $v;
        }
        $node = new self($this->xml->$name);
        if ($getLastChild) {
            $array = $this->xml->xpath($name."[last()]");
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
     * @return static the current builder
     */
    public function attribute($name, $value): static
    {
        $this->xml[$name] = $value;

        return $this;
    }

    /**
     * Return this XmlBuilder as a string of XML.
     *
     * @return string the XML of the document
     */
    public function asXml(): string
    {
        return self::utf8ForXML($this->xml->asXML());
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
