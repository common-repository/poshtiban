<?php


namespace Poshtiban\Options\Sections;


use Poshtiban\Bootstrap;
use Poshtiban\Main;

abstract class SectionsAbstract {
	abstract public function get_fields();

	private $id;
	private $title;
	private $page;

	public function __construct( $id, $title, $page) {
		$this->id       = sprintf( '%s-%s-section', Main::$slug, $id );
		$this->title    = $title;
		$this->page     = sprintf( '%s-%s-page', Main::$slug, $page );

		add_action( 'admin_init', [ $this, 'init' ] );
		add_action( 'admin_init', [ $this, 'fields' ] );
	}

	public function init() {
		add_settings_section( $this->id, $this->title, [get_called_class(), 'callback'], $this->page );
	}

	public function fields() {
		foreach ( $this->get_fields() as $field ) {
			require_once sprintf('%sincludes/%s', Bootstrap::$path, $field['path']);
			new $field['class']($this->page, $this->id);
		}
	}

	public static function callback() {

	}

}