<?php

namespace Poshtiban\Options\Fields;

abstract class FieldsAbstract {
	abstract public function set_id();

	abstract public function set_title();

	abstract public function set_setting_id();

	abstract public function render();

	protected $id;
	protected $title;
	protected $setting_id;

	public function __construct( $page, $section ) {
		$this->set_id();
		$this->set_title();
		$this->set_setting_id();
		if( $this->default_value === null ) {
			$this->set_default_value();
		}
		add_settings_field( $this->id, $this->title, [ $this, 'render' ], $page, $section );
	}

	protected function select_field(
		array $options,
		$selected_values,
		$name,
		$multiple = true,
		$class_name = '',
		$description = false,
		$id = false,
		$placeholder = false
	) {
		$options_html = '';
		foreach ( $options as $key => $option ) {
			if ( $multiple ) {
				$selected = in_array( $key, $selected_values ) ? 'selected' : '';
			} else {
				$selected = $key == $selected_values ? 'selected' : '';
			}
			$options_html .= sprintf( '<option value="%s" %s>%s</option>', $key, $selected, $option );
		}

		return sprintf( '<select name="%s" class="%s" id="%s" %s %s>%s</select>%s', $name, $class_name, $id ? $id : '',
			$placeholder ? sprintf( 'data-placeholder="%s"', $placeholder ) : '', $multiple ? 'multiple' : '',
			$options_html, $description ? sprintf( '<span class="description">%s</span>', $description ) : '' );
	}
}