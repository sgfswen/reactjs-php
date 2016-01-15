<?php
/**
 * Created by PhpStorm.
 * User: mayorov
 * Date: 15.01.16
 * Time: 17:19
 *
 * A PHP class to server side render React JS components.
 * Requires v8js PHP extension: https://github.com/phpv8/v8js
 *
 * @author Alexander Mayorov
 * @mail: alexander@majorov.su
 */
class ReactJS {

	protected
		/**
		 * Instance of V8Js class
		 * @var object
		 */
		$v8,

		/**
		 * UDF error handler
		 * @var callable
		 */
		$errorHandler,

		/**
		 * Bootstrap JS source
		 * @var string
		 */
		$source = '',

		/**
		 * Entry settings and source
		 * @var object
		 */
		$entry,

		/**
		 * Default configuration
		 * @var array|object
		 */
		$opt = [
		'node_modules' => 'node_modules'
	]
	;

	/**
	 * Initialize by passing JS code as a string.
	 * The application source code is concatenated string
	 * of all custom components and app code
	 *
	 * @param array $opt Options
	 */
	public function __construct(array $opt = []) {

		$this->opt = (object) array_merge($this->opt, $opt);

		$this->source = implode(";\n", [
			'var console = { log: print, warn: print, error: print}',
			'var global  = global || this, self = self || this, window = window || this',
			'var exports = exports || { default: null }',
			'var process = process || { env: { NODE_ENV: "development" } }',
			(
				$this->loadFile($this->opt->node_modules . '/react/dist/react.js')
				. $this->loadFile($this->opt->node_modules . '/react-dom/dist/react-dom.js')
				. $this->loadFile($this->opt->node_modules . '/react-dom/dist/react-dom-server.js')
			),
			'var React=global.React,ReactDOM=global.ReactDOM,ReactDOMServer=global.ReactDOMServer;',
		]);

		$this->v8 = new V8Js();

		/*
				$this->v8->setModuleLoader(function($path) {
					//var_dump($path);
					switch ($path) {
						case 'react': file_get_contents(__DIR__ . '/node_modules/react/dist/react.min.js');
						default:
							return file_get_contents(__DIR__ . "/js/bld/$path");
					}
				});
		//*/
	}

	/**
	 * Set entry point to render
	 *
	 * @param array $opt Options
	 * @throws Exception
	 */
	public function entry(array $opt) {

		if (!isset($opt['component'])) throw new Exception('Not set "component" name for render!');
		if (!isset($opt['require'])  ) throw new Exception('Not set "require" path for compnent js file!');

		if (isset($opt['props']) && !is_array($opt['props']))
			throw new Exception('Property "props" can by only array type!');

		if (!file_exists($opt['require']))
			throw new Exception("File module note exists: {$opt['require']}");

		$this->entry = (object) $opt;

		$this->entry->js = implode(";\n", [
			sprintf('__react_html=ReactDOMServer.renderToString(React.createFactory(%s)(%s))',
				$opt['component'],
				json_encode($opt['props'])
			),
			'__react_html;'
		]);
	}

	/**
	 * Build HTML markup
	 *
	 * @return string
	 * @throws Exception
	 */
	public function preRender() {
		return
			$this->source
			. $this->loadFile($this->entry->require)
			. $this->entry->js;
	}

	/**
	 * Returns the markup to print to the page
	 *
	 * @return string HTML string
	 */
	public function render() {
		return $this->execjs( $this->preRender() );
	}

	/**
	 * Auto convert object ReactJS to string
	 *
	 * Example:
	 * 		$rjs = new ReactJS;
	 * 		echo $rjs;
	 *
	 * @return string HTML string
	 */
	public function __toString() { return (string) $this->render(); }

	/**
	 *
	 * @return string
	 */
	public function __invoke() {
		return $this->execjs( $this->preRender() );
	}

	/**
	 * Custom error handler. The default one var_dumps the exception
	 * and die()s.
	 *
	 * @param callable $err Callback passed to call_user_func()
	 * @return object $this instance
	 */
	public function setErrorHandler($err) {
		$this->errorHandler = $err;
	}

	/**
	 * Executes Javascript using V8JS, with primitive exception handling
	 *
	 * @param string $js JS code to be executed
	 * @return string The execution response
	 */
	private function execjs($js) {
		try {
			//ob_start();
			return $this->v8->executeString($js, 'server-side-render-react.js');
			//return ob_get_clean();
		}
		catch (V8JsException $e)
		{
			$this->errorHandler && is_callable($this->errorHandler)
				? call_user_func($this->errorHandler, $e)
				: var_dump($e)
			;
		}
	}

	/**
	 * Load file helper
	 *
	 * @param $file
	 * @return string
	 * @throws Exception
	 */
	private function loadFile($file) {
		if (!file_exists($file)) throw new Exception("File not found: $file");
		return file_get_contents($file);
	}

}