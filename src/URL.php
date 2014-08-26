<?php

namespace SevenPercent;

class URL {

	private $_components = [];

	public $queryPrefix = '';

	public function __construct($url = '') {

		// Tokenise the supplied string
		if (($this->_components = (object)parse_url($url)) === FALSE) {
			throw new URLException('Invalid URL');

		// When parse_url() is called with an empty string, it sets ['path'] as an empty string, so unset this now for clarity later on
		} elseif (isset($this->_components->path) && $this->_components->path === '') {
			unset($this->_components->path);
		}

		// Parse the query token into an associative array
		if (isset($this->_components->query)) {
			parse_str($this->_components->query, $this->_components->query);
		}
	}

	// Magic getter
	public function __get($name) {
		switch ($name) {
			case @isSecure:
				return isset($this->_components->scheme) && strtolower(substr($this->_components->scheme, -1)) === 's';
			default:
				return isset($this->_components->$name) ? $this->_components->$name : NULL;
		}
	}

	// Magic setter, parses query parameter into an array to support setting the query as a string
	public function __set($name, $value) {
		if (empty($value)) {
			unset($this->_components->$name);
		} else {
			switch ($name) {
				case @query:
					if (!is_array($value)) {
						parse_str($value, $this->_components->$name);
						break;
					}
				default:
					$this->_components->$name = $value;
					break;
			}
		}
	}

	// Magic unsetter
	public function __unset($name) {
		unset($this->_components->$name);
	}

	// Magic cast to string
	public function __toString() {
		$url = '';

		// TO DO: this looping approach probably isn't sustainable - it may be necessary to apply multiple callbacks to an individual component, or separate pre- and post-concatenation callbacks
		foreach ([
			'scheme' => ['', '://', 'strtolower', []],
			'user' => ['', ':', '', []],
			'pass' => ['', '@', '', []],
			'host' => ['', '', 'strtolower', []],
			'port' => [':', '', '', []],
			'path' => ['', '', '', []],
			'query' => ['?', '', 'http_build_query', [$this->queryPrefix]],
			'fragment' => ['#', '', '', []],
		] as $component => list($prefix, $suffix, $callback, $parameters)) {
			if (isset($this->_components->$component)) {
				$url .= $callback === '' ? "$prefix$this->_components->$component$suffix" : $prefix . call_user_func_array($callback, array_merge([$this->_components->$component], $parameters)) . $suffix;
			}
		}

		// Throwing Exceptions or returning any other data type than a string are not allowed inside __toString(). It may make more sense to return $url regardless, and expect the client to check for validity
		return parse_url($url) === FALSE ? '' : $url;
	}
}
