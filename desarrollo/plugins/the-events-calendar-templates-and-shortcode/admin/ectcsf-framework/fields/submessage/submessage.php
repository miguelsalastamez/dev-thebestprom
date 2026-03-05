<?php if ( ! defined( 'ABSPATH' ) ) { die; } // Cannot access directly.
/**
 *
 * Field: submessage
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if ( ! class_exists( 'ECTCSF_Field_submessage' ) ) {
  class ECTCSF_Field_submessage extends ECTCSF_Fields {

    public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
      parent::__construct( $field, $value, $unique, $where, $parent );
    }

    public function render() {

      $style = ( ! empty( $this->field['style'] ) ) ? $this->field['style'] : 'normal';

      echo '<div class="ectcsf-submessage ectcsf-submessage-'. esc_attr( $style ) .'">'. $this->field['content'] .'</div>';

    }

  }
}
