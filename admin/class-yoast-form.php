<?php

class Yoast_Form {
	public static $instance;
	public $option_name;
	public $options;
	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function admin_header( $form = true, $option = 'wpseo', $contains_files = false, $option_long_name = false ) {
		if ( ! $option_long_name ) {
			$option_long_name = WPSEO_Options::get_group_name( $option );
		}
		?>
		<div class="wrap yoast wpseo-admin-page <?php echo esc_attr( 'page-' . $option ); ?>">
		<?php
		require_once ABSPATH . 'wp-admin/options-head.php';
		?>
		<h1 id="wpseo-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div class="wpseo_content_wrapper">
		<div class="wpseo_content_cell" id="wpseo_content_top">
		<?php
		if ( $form === true ) {
			$enctype = ( $contains_files ) ? ' enctype="multipart/form-data"' : '';
			$network_admin = new Yoast_Network_Admin();
			if ( $network_admin->meets_requirements() ) {
				$action_url       = network_admin_url( 'settings.php' );
				$hidden_fields_cb = array( $network_admin, 'settings_fields' );
			} else {
				$action_url       = admin_url( 'options.php' );
				$hidden_fields_cb = 'settings_fields';
			}
			echo '<form action="' . esc_url( $action_url ) . '" method="post" id="wpseo-conf"' . $enctype . ' accept-charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '">';
			call_user_func( $hidden_fields_cb, $option_long_name );
		}
		$this->set_option( $option );
	}

	public function set_option( $option_name ) {
		$this->option_name = $option_name;
		$this->options     = $this->get_option();
	}

	public function set_options_value( $key, $value, $overwrite = false ) {
		if ( $overwrite || ! array_key_exists( $key, $this->options ) ) {
			$this->options[ $key ] = $value;
		}
	}

	public function get_option() {
		if ( is_network_admin() ) {
			return get_site_option( $this->option_name );
		}
		return get_option( $this->option_name );
	}

	public function admin_footer( $submit = true, $show_sidebar = true ) {
		if ( $submit ) {
			submit_button( __( 'Save changes', 'wordpress-seo' ) );
			echo '
			</form>';
		}
		do_action( 'wpseo_admin_footer', $this );
		do_action( 'wpseo_admin_promo_footer' );
		echo '</div>';
		if ( $show_sidebar ) {
			$this->admin_sidebar();
		}
		echo '</div>';
		do_action( 'wpseo_admin_below_content', $this );
		echo '</div>';
	}
	
	public function admin_sidebar() {
		if ( class_exists( 'WPSEO_Product_Premium' ) ) {
			$product_premium   = new WPSEO_Product_Premium();
			$extension_manager = new WPSEO_Extension_Manager();
			if ( $extension_manager->is_activated( $product_premium->get_slug() ) ) {
				return;
			}
		}
		require_once 'views/sidebar.php';
	}

	public function label( $text, $attr ) {
		$attr = wp_parse_args( $attr, array(
				'class' => 'checkbox',
				'close' => true,
				'for'   => '',
			)
		);
		echo "<label class='" . esc_attr( $attr['class'] ) . "' for='" . esc_attr( $attr['for'] ) . "'>$text";
		if ( $attr['close'] ) {
			echo '</label>';
		}
	}

	public function legend( $text, $attr ) {
		$attr = wp_parse_args( $attr, array(
				'id'    => '',
				'class' => '',
			)
		);
		$id = ( '' === $attr['id'] ) ? '' : ' id="' . esc_attr( $attr['id'] ) . '"';
		echo '<legend class="yoast-form-legend ' . esc_attr( $attr['class'] ) . '"' . $id . '>' . $text . '</legend>';
	}

	public function checkbox( $var, $label, $label_left = false ) {
		if ( ! isset( $this->options[ $var ] ) ) {
			$this->options[ $var ] = false;
		}
		if ( $this->options[ $var ] === true ) {
			$this->options[ $var ] = 'on';
		}
		$class = '';
		if ( $label_left !== false ) {
			if ( ! empty( $label_left ) ) {
				$label_left .= ':';
			}
			$this->label( $label_left, array( 'for' => $var ) );
		} else {
			$class = 'double';
		}
		echo '<input class="checkbox ', esc_attr( $class ), '" type="checkbox" id="', esc_attr( $var ), '" name="', esc_attr( $this->option_name ), '[', esc_attr( $var ), ']" value="on"', checked( $this->options[ $var ], 'on', false ), '/>';
		if ( ! empty( $label ) ) {
			$this->label( $label, array( 'for' => $var ) );
		}
		echo '<br class="clear" />';
	}

	public function light_switch( $var, $label, $buttons = array(), $reverse = true, $help = '' ) {
		if ( ! isset( $this->options[ $var ] ) ) {
			$this->options[ $var ] = false;
		}
		if ( $this->options[ $var ] === true ) {
			$this->options[ $var ] = 'on';
		}
		$class           = 'switch-light switch-candy switch-yoast-seo';
		$aria_labelledby = esc_attr( $var ) . '-label';
		if ( $reverse ) {
			$class .= ' switch-yoast-seo-reverse';
		}
		if ( empty( $buttons ) ) {
			$buttons = array( __( 'Disabled', 'wordpress-seo' ), __( 'Enabled', 'wordpress-seo' ) );
		}
		list( $off_button, $on_button ) = $buttons;
		$help_class               = '';
		$screen_reader_text_class = '';
		$help_class = ! empty( $help ) ? ' switch-container__has-help' : '';
		echo "<div class='switch-container$help_class'>",
		"<span class='switch-light-visual-label'>{$label}</span>" . $help,
		'<label class="', $class, '"><b class="switch-yoast-seo-jaws-a11y">&nbsp;</b>',
		'<input type="checkbox" aria-labelledby="', $aria_labelledby, '" id="', esc_attr( $var ), '" name="', esc_attr( $this->option_name ), '[', esc_attr( $var ), ']" value="on"', checked( $this->options[ $var ], 'on', false ), '/>',
		"<b class='label-text screen-reader-text' id='{$aria_labelledby}'>{$label}</b>",
		'<span aria-hidden="true">
			<span>', esc_html( $off_button ) ,'</span>
			<span>', esc_html( $on_button ) ,'</span>
			<a></a>
		 </span>
		 </label><div class="clear"></div></div>';
	}
	
	public function textinput( $var, $label, $attr = array() ) {
		if ( ! is_array( $attr ) ) {
			$attr = array(
				'class' => $attr,
			);
		}
		$attr = wp_parse_args( $attr, array(
			'placeholder' => '',
			'class'       => '',
		) );
		$val  = ( isset( $this->options[ $var ] ) ) ? $this->options[ $var ] : '';
		$this->label(
			$label . ':',
			array(
				'for'   => $var,
				'class' => 'textinput',
			)
		);
		echo '<input class="textinput ' . esc_attr( $attr['class'] ) . ' " placeholder="' . esc_attr( $attr['placeholder'] ) . '" type="text" id="', esc_attr( $var ), '" name="', esc_attr( $this->option_name ), '[', esc_attr( $var ), ']" value="', esc_attr( $val ), '"/>', '<br class="clear" />';
	}

	public function textarea( $var, $label, $attr = array() ) {
		if ( ! is_array( $attr ) ) {
			$attr = array(
				'class' => $attr,
			);
		}
		$attr = wp_parse_args( $attr, array(
			'cols'  => '',
			'rows'  => '',
			'class' => '',
		) );
		$val  = ( isset( $this->options[ $var ] ) ) ? $this->options[ $var ] : '';
		$this->label(
			$label . ':',
			array(
				'for'   => $var,
				'class' => 'textinput',
			)
		);
		echo '<textarea cols="' . esc_attr( $attr['cols'] ) . '" rows="' . esc_attr( $attr['rows'] ) . '" class="textinput ' . esc_attr( $attr['class'] ) . '" id="' . esc_attr( $var ) . '" name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $var ) . ']">' . esc_textarea( $val ) . '</textarea><br class="clear" />';
	}

	public function hidden( $var, $id = '' ) {
		$val = ( isset( $this->options[ $var ] ) ) ? $this->options[ $var ] : '';
		if ( is_bool( $val ) ) {
			$val = ( $val === true ) ? 'true' : 'false';
		}
		if ( '' === $id ) {
			$id = 'hidden_' . $var;
		}
		echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $this->option_name ) . '[' . esc_attr( $var ) . ']" value="' . esc_attr( $val ) . '"/>';
	}

	public function select( $field_name, $label, array $select_options ) {
		if ( empty( $select_options ) ) {
			return;
		}
		$this->label(
			$label . ':',
			array(
				'for'   => $field_name,
				'class' => 'select',
			)
		);
		$select_name   = esc_attr( $this->option_name ) . '[' . esc_attr( $field_name ) . ']';
		$active_option = ( isset( $this->options[ $field_name ] ) ) ? $this->options[ $field_name ] : '';
		$select = new Yoast_Input_Select( $field_name, $select_name, $select_options, $active_option );
		$select->add_attribute( 'class', 'select' );
		$select->output_html();
		echo '<br class="clear"/>';
	}
	
	public function file_upload( $var, $label ) {
		$val = '';
		if ( isset( $this->options[ $var ] ) && is_array( $this->options[ $var ] ) ) {
			$val = $this->options[ $var ]['url'];
		}
		$var_esc = esc_attr( $var );
		$this->label(
			$label . ':',
			array(
				'for'   => $var,
				'class' => 'select',
			)
		);
		echo '<input type="file" value="' . esc_attr( $val ) . '" class="textinput" name="' . esc_attr( $this->option_name ) . '[' . $var_esc . ']" id="' . $var_esc . '"/>';
		if ( ! empty( $this->options[ $var ] ) ) {
			$this->hidden( 'file', $this->option_name . '_file' );
			$this->hidden( 'url', $this->option_name . '_url' );
			$this->hidden( 'type', $this->option_name . '_type' );
		}
		echo '<br class="clear"/>';
	}

	public function media_input( $var, $label ) {
		$val = '';
		if ( isset( $this->options[ $var ] ) ) {
			$val = $this->options[ $var ];
		}
		$var_esc = esc_attr( $var );
		$this->label(
			$label . ':',
			array(
				'for'   => 'wpseo_' . $var,
				'class' => 'select',
			)
		);
		echo '<input class="textinput" id="wpseo_', $var_esc, '" type="text" size="36" name="', esc_attr( $this->option_name ), '[', $var_esc, ']" value="', esc_attr( $val ), '" />';
		echo '<input id="wpseo_', $var_esc, '_button" class="wpseo_image_upload_button button" type="button" value="', esc_attr__( 'Upload Image', 'wordpress-seo' ), '" />';
		echo '<br class="clear"/>';
	}

	public function radio( $var, $values, $legend = '', $legend_attr = array() ) {
		if ( ! is_array( $values ) || $values === array() ) {
			return;
		}
		if ( ! isset( $this->options[ $var ] ) ) {
			$this->options[ $var ] = false;
		}
		$var_esc = esc_attr( $var );
		echo '<fieldset class="yoast-form-fieldset wpseo_radio_block" id="' . $var_esc . '">';
		if ( is_string( $legend ) && '' !== $legend ) {

			$legend_attr = wp_parse_args( $legend_attr, array(
				'id'    => '',
				'class' => 'radiogroup',
			) );
			$this->legend( $legend, $legend_attr );
		}
		foreach ( $values as $key => $value ) {
			$key_esc = esc_attr( $key );
			echo '<input type="radio" class="radio" id="' . $var_esc . '-' . $key_esc . '" name="' . esc_attr( $this->option_name ) . '[' . $var_esc . ']" value="' . $key_esc . '" ' . checked( $this->options[ $var ], $key_esc, false ) . ' />';
			$this->label(
				$value,
				array(
					'for'   => $var_esc . '-' . $key_esc,
					'class' => 'radio',
				)
			);
		}
		echo '</fieldset>';
	}

	public function toggle_switch( $var, $values, $label, $help = '' ) {
		if ( ! is_array( $values ) || $values === array() ) {
			return;
		}
		if ( ! isset( $this->options[ $var ] ) ) {
			$this->options[ $var ] = false;
		}
		if ( $this->options[ $var ] === true ) {
			$this->options[ $var ] = 'on';
		}
		if ( $this->options[ $var ] === false ) {
			$this->options[ $var ] = 'off';
		}
		$help_class = ! empty( $help ) ? ' switch-container__has-help' : '';
		$var_esc = esc_attr( $var );
		printf( '<div class="%s">', esc_attr( 'switch-container' . $help_class ) );
		echo '<fieldset id="', $var_esc, '" class="fieldset-switch-toggle"><legend>', $label, '</legend>', $help,
		'<div class="switch-toggle switch-candy switch-yoast-seo">';
		foreach ( $values as $key => $value ) {
			$screen_reader_text      = '';
			$screen_reader_text_html = '';

			if ( is_array( $value ) ) {
				$screen_reader_text      = $value['screen_reader_text'];
				$screen_reader_text_html = '<span class="screen-reader-text"> ' . esc_html( $screen_reader_text ) . '</span>';
				$value                   = $value['text'];
			}
			$key_esc = esc_attr( $key );
			$for     = $var_esc . '-' . $key_esc;
			echo '<input type="radio" id="' . $for . '" name="' . esc_attr( $this->option_name ) . '[' . $var_esc . ']" value="' . $key_esc . '" ' . checked( $this->options[ $var ], $key_esc, false ) . ' />',
			'<label for="', $for, '">', esc_html( $value ), $screen_reader_text_html,'</label>';
		}

		echo '<a></a></div></fieldset><div class="clear"></div></div>' . "\n\n";
	}

	public function index_switch( $var, $label, $help = '' ) {
		$index_switch_values = array(
			'off' => __( 'Yes', 'wordpress-seo' ),
			'on'  => __( 'No', 'wordpress-seo' ),
		);
		$this->toggle_switch(
			$var,
			$index_switch_values,
			sprintf(
				esc_html__( 'Show %s in search results?', 'wordpress-seo' ),
				'<strong>' . esc_html( $label ) . '</strong>'
			),
			$help
		);
	}

	public function show_hide_switch( $var, $label, $inverse_keys = false, $help = '' ) {
		$on_key  = ( $inverse_keys ) ? 'off' : 'on';
		$off_key = ( $inverse_keys ) ? 'on' : 'off';
		$show_hide_switch = array(
			$on_key  => __( 'Show', 'wordpress-seo' ),
			$off_key => __( 'Hide', 'wordpress-seo' ),
		);
		$this->toggle_switch( $var, $show_hide_switch, $label, $help );
	}
}
