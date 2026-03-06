<?php

class PAFE_Dual_Color_Headline extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'pafe-dual-color-headline';
    }

    public function get_title()
    {
        return __('PAFE Dual Color Headline', 'pafe');
    }

    public function get_icon()
    {
        return 'fas fa-toggle-off';
    }

    public function get_categories()
    {
        return ['pafe-free-widgets'];
    }

    public function get_keywords()
    {
        return ['headline', 'dual'];
    }

    public function get_script_depends()
    {
        return [
            'pafe-widget-free'
        ];
    }

    public function get_style_depends()
    {
        return [
            'pafe-widget-style-free'
        ];
    }


    /** Insert Content Section**/
    protected function _register_controls()
    {
        $this->pafe_dual_color_headline_controls();
    }

    protected function register_controls()
    {
        $this->pafe_dual_color_headline_controls();
    }

    public function pafe_dual_color_headline_controls()
    {
        $this->start_controls_section(
            'pafe_dual_color_headline_content_section',
            [
                'label' => __('Headline Content', 'pafe'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'pafe_dual_color_content_section_before',
            [
                'label' => __('Headline-Before', 'pafe'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_control(
            'pafe_dual_color_content_section_after',
            [
                'label' => __('Headline-After', 'pafe'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
            ]
        );

        $this->add_responsive_control(
            'pafe_dual_color_text_align',
            [
                'label' => __('Alignment', 'pafe'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'pafe'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'pafe'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'pafe'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justified' => [
                        'title' => __('Justified', 'pafe'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => 'center',
                'toggle' => true,
                'prefix_class' => 'content-align-%s',
            ]
        );


        $this->add_control(
            'pafe_dual_color_link',
            [
                'label' => __('Link', 'pafe'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-link.com', 'pafe'),
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );

        $this->add_control(
            'pafe_dual_color_html_tag',
            [
                'label' => __('HTML Tag', 'pafe'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'h2',
                'options' => [
                    'h1' => __('h1', 'pafe'),
                    'h2' => __('h2', 'pafe'),
                    'h3' => __('h3', 'pafe'),
                    'h4' => __('h4', 'pafe'),
                    'h5' => __('h5', 'pafe'),
                    'h6' => __('h6', 'pafe'),
                    'div' => __('div', 'pafe'),
                    'span' => __('span', 'pafe'),
                ],
            ]
        );

        $this->end_controls_section();



        /* Edit Content Style Section*/

        $this->start_controls_section(
            'pafe_dual_color_headline_style_content',
            [
                'label' => __('Headline Style', 'pafe'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs(
            'pafe_headline'
        );
        $this->start_controls_tab(
            'headline_before_tabs',
            [
                'label' => __('Headline Before', 'pafe'),
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'headline_before_typography',
                'selector' => '{{WRAPPER}} .pafe_dual_color_content_section_before',
                'global' => [
                    'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_SECONDARY,
                ],
            ]
        );

        $this->add_control(
            'pafe_headline_before_color',
            [
                'label' => __('Color', 'pafe'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000',
                'selectors' => [
                    '{{WRAPPER}} .pafe_dual_color_content_section_before' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'headline_before_typography',
                'selector' => '{{WRAPPER}} .pafe_dual_color_content_section_before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'background',
                'label' => __('Background', 'pafe'),
                'types' => ['classic', 'gradient', 'video'],
                'selector' => '{{WRAPPER}} .pafe-dual-color-headline-before',
            ]
        );

        $this->add_control(
            'pafe_headline_before_style_background_general_padding',
            [
                'label' => __('Padding', 'pafe'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-before' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pafe_headline_before_style_background_general_margin',
            [
                'label' => __('Margin', 'pafe'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-before' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'background_headline_before_general_border_type',
                'label' => __('Background Headline Before Border', 'pafe'),
                'selector' => '{{WRAPPER}} .pafe-dual-color-headline-before',
            ]
        );

        $this->add_responsive_control(
            'pafe_headline_before_style_background_general_border_radius',
            [
                'label' => __('Border Radius', 'pafe'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-before' => 'Border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'background_headline_before_general_box_shadow',
                'label' => __('Box Shadow', 'pafe'),
            ]
        );
        $this->end_controls_tab();

        $this->start_controls_tab(
            'headline_after_tabs',
            [
                'label' => __('Headline After', 'pafe'),
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'headline_after_typography',
                'selector' => '{{WRAPPER}} .pafe_dual_color_content_section_after',
                'global' => [
                    'default' => \Elementor\Core\Kits\Documents\Tabs\Global_Typography::TYPOGRAPHY_SECONDARY,
                ],
            ]
        );

        $this->add_control(
            'pafe_headline_after_color',
            [
                'label' => __('Color', 'pafe'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000',
                'selectors' => [
                    '{{WRAPPER}} .pafe_dual_color_content_section_after' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'headline_after_typography',
                'selector' => '{{WRAPPER}} .pafe_dual_color_content_section_after',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'background_after',
                'label' => __('Background', 'pafe'),
                'types' => ['classic', 'gradient', 'video'],
                'selector' => '{{WRAPPER}} .pafe-dual-color-headline-after',
            ]
        );

        $this->add_control(
            'pafe_headline_after_style_background_general_padding',
            [
                'label' => __('Padding', 'pafe'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-after' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'pafe_headline_after_style_background_general_margin',
            [
                'label' => __('Margin', 'pafe'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-after' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \ELEMENTOR\Group_Control_Border::get_type(),
            [
                'name' => 'background_headline_after_general_border_type',
                'label' => __('Background Headline After Border', 'pafe'),
                'selector' => '{{WRAPPER}} .pafe-dual-color-headline-after',
            ]
        );

        $this->add_responsive_control(
            'pafe_headline_after_style_background_general_border_radius',
            [
                'label' => __('Border Radius', 'pafe'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 0,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pafe-dual-color-headline-after' => 'Border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'background_headline_after_general_box_shadow',
                'label' => __('Box Shadow', 'pafe'),
                // s
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();


        $this->end_controls_section();
    }


    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $target = $settings['pafe_dual_color_link']['is_external'] ? ' target="_blank"' : '';
        $nofollow = $settings['pafe_dual_color_link']['nofollow'] ? ' rel="nofollow"' : '';
        $settings = $this->get_settings_for_display();
        $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span'];
        $html_tag = in_array($settings['pafe_dual_color_html_tag'], $allowed_tags) ? esc_attr($settings['pafe_dual_color_html_tag']) : 'h2';
        ?>
        <<?php echo esc_html($html_tag); ?> class="pafe-dual-color-headline">
            <?php if (!empty($settings['pafe_dual_color_link']['url'])):
                echo '<a href="' . esc_url($settings['pafe_dual_color_link']['url']) . '"' . esc_attr($target) . esc_attr($nofollow) . '>'
                    ?>
            <?php endif ?>

            <div class="pafe-dual-color-headline-before">
                <?php
                if (!empty($settings['pafe_dual_color_content_section_before'])) {
                    ?>
                    <span class="pafe_dual_color_content_section_before">
                        <?php echo esc_html($settings['pafe_dual_color_content_section_before']); ?>
                    </span>
                    <?php
                }
                ?>
            </div>
            <div class="pafe-dual-color-headline-after">
                <?php
                if (!empty($settings['pafe_dual_color_content_section_after'])) {
                    ?>
                    <span class="pafe_dual_color_content_section_after">
                        <?php echo esc_html($settings['pafe_dual_color_content_section_after']); ?>
                    </span>
                    <?php
                }
                ?>
            </div>
            <?php if (!empty($settings['pafe_dual_color_link']['url'])):
                echo '</a>';
                ?>
            <?php endif ?>
        </<?php echo esc_html($settings['pafe_dual_color_html_tag']); ?>
    <?php
    }
}
