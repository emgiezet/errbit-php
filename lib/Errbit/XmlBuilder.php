<?php

class Errbit_XmlBuilder {
	public function __construct($xml = null) {
		$this->_xml = $xml ? $xml : new SimpleXMLElement('<__ErrbitXMLBuilder__/>');
	}

	public function tag($name /* , $value, $attributes, $callback */) {
		$value      = '';
		$attributes = array();
		$callback   = null;
		$idx        = count($this->_xml->$name);
		$args       = func_get_args();

		array_shift($args);
		foreach ($args as $arg) {
			if (is_string($arg)) {
				$value = $arg;
			} elseif (is_callable($arg)) {
				$callback = $arg;
			} elseif (is_array($arg)) {
				$attributes = $arg;
			}
		}

		$this->_xml->{$name}[$idx] = $value;

		foreach ($attributes as $attr => $v) {
			$this->_xml->{$name}[$idx][$attr] = $v;
		}

		// FIXME: This isn't the last child, it's the first, it just doesn't happen to matter in this project
		$node = new self($this->_xml->$name);

		if ($callback) {
			$callback($node);
		}

		return $node;
	}

	public function attribute($name, $value) {
		$this->_xml[$name] = $value;
		return $this;
	}

	public function asXml() {
		return $this->_xml->asXML();
	}
}
