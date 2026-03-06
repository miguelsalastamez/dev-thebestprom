<?php
class PAFE_Gradient_Text extends \Elementor\Widget_Base {

	public function __construct() {
		parent::__construct();
		$this->init_control();
	}

	public function get_name() {
		return 'pafe-gradient-text';
	}

	public function pafe_register_controls( $element, $args ) {

		$element->start_controls_section(
			'pafe_gradient_text_section',
			[
				'label' => __( 'PAFE Gradient Text', 'pafe' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$element->add_control(
			'pafe_gradient_text',
			[
				'label' => __( 'Enable Gradient Text', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'default' => '',
				'label_on' => 'Yes',
				'label_off' => 'No',
				'return_value' => 'yes',
			]
		);

		$element->add_control(
			'pafe_gradient_text_color',
			[
				'label' => _x( 'Color', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#670093',
				'title' => _x( 'Background Color', 'Background Control', 'pafe' ),
				'selectors' => [
					'{{WRAPPER}} .elementor-heading-title' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'pafe_gradient_text' => 'yes',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_color_stop',
			[
				'label' => _x( 'Location', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'default' => [
					'unit' => '%',
					'size' => 0,
				],
				'render_type' => 'ui',
				'condition' => [
					'pafe_gradient_text' => 'yes',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_color_b',
			[
				'label' => _x( 'Second Color', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => '#f2295b',
				'render_type' => 'ui',
				'condition' => [
					'pafe_gradient_text' => 'yes',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_color_b_stop',
			[
				'label' => _x( 'Location', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'render_type' => 'ui',
				'condition' => [
					'pafe_gradient_text' => 'yes',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_gradient_type',
			[
				'label' => _x( 'Type', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'linear' => _x( 'Linear', 'Background Control', 'pafe' ),
					'radial' => _x( 'Radial', 'Background Control', 'pafe' ),
				],
				'default' => 'linear',
				'render_type' => 'ui',
				'condition' => [
					'pafe_gradient_text' => 'yes',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_gradient_angle',
			[
				'label' => _x( 'Angle', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'deg' ],
				'default' => [
					'unit' => 'deg',
					'size' => 180,
				],
				'range' => [
					'deg' => [
						'step' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-heading-title' => 'background-color: transparent; background-image: linear-gradient({{SIZE}}{{UNIT}}, {{pafe_gradient_text_color.VALUE}} {{pafe_gradient_text_color_stop.SIZE}}{{pafe_gradient_text_color_stop.UNIT}}, {{pafe_gradient_text_color_b.VALUE}} {{pafe_gradient_text_color_b_stop.SIZE}}{{pafe_gradient_text_color_b_stop.UNIT}}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;',
				],
				'condition' => [
					'pafe_gradient_text' => 'yes',
					'pafe_gradient_text_gradient_type' => 'linear',
				],
			]
		);

		$element->add_control(
			'pafe_gradient_text_gradient_position',
			[
				'label' => _x( 'Position', 'Background Control', 'pafe' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => [
					'center center' => _x( 'Center Center', 'Background Control', 'pafe' ),
					'center left' => _x( 'Center Left', 'Background Control', 'pafe' ),
					'center right' => _x( 'Center Right', 'Background Control', 'pafe' ),
					'top center' => _x( 'Top Center', 'Background Control', 'pafe' ),
					'top left' => _x( 'Top Left', 'Background Control', 'pafe' ),
					'top right' => _x( 'Top Right', 'Background Control', 'pafe' ),
					'bottom center' => _x( 'Bottom Center', 'Background Control', 'pafe' ),
					'bottom left' => _x( 'Bottom Left', 'Background Control', 'pafe' ),
					'bottom right' => _x( 'Bottom Right', 'Background Control', 'pafe' ),
				],
				'default' => 'center center',
				'selectors' => [
					'{{WRAPPER}} .elementor-heading-title' => 'background-color: transparent; background-image: radial-gradient(at {{VALUE}}, {{pafe_gradient_text_color.VALUE}} {{pafe_gradient_text_color_stop.SIZE}}{{pafe_gradient_text_color_stop.UNIT}}, {{pafe_gradient_text_color_b.VALUE}} {{pafe_gradient_text_color_b_stop.SIZE}}{{pafe_gradient_text_color_b_stop.UNIT}}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;',
				],
				'condition' => [
					'pafe_gradient_text' => 'yes',
					'pafe_gradient_text_gradient_type' => 'radial',
				],
			]
		);

		$element->end_controls_section();

	}

	protected function init_control() {
		add_action( 'elementor/element/heading/section_title_style/after_section_end', [ $this, 'pafe_register_controls' ], 10, 2 );
	}

}
