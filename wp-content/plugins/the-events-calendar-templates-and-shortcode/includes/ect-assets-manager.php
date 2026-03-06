<?php

class Ect_Assets_Manager {


	/**
	 * Constructor.
	 *
	 * @param array $options
	 */
	public function __construct() {
		 $this->registers();
	}

	 /**
	  * Register all hooks
	  */

	public function registers() {
		$thisPlugin = $this;
		/*** Enqueued script and styles */
		// add_action('wp_enqueue_scripts', array($thisPlugin, 'ect_register_assets'));
	}

	/*** Register style/scripts assets */
	function ect_register_assets() {
		wp_register_style( 'ect-common-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-common-styles.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-timeline-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-timeline-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-list-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-list-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-grid-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-grid-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-carousel-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-carousel-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-slider-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-slider-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-accordion-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-accordion-view.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-minimal-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-minimal-list-view.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-cover-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-cover-view.css', null, ECT_PRO_VERSION, 'all' );

		wp_register_style( 'ect-grid-view-bootstrap', ECT_PRO_PLUGIN_URL . 'assets/css/bootstrap.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-slider-slick', ECT_PRO_PLUGIN_URL . 'assets/css/slick.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_register_style( 'ect-collapse-bootstrap', ECT_PRO_PLUGIN_URL . 'assets/css/collapse-bootstrap-4.0.min.css', null, ECT_PRO_VERSION, 'all' );

		wp_register_script( 'ect-accordion-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-accordion-view.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		wp_register_script( 'ect-slider-slick-js', ECT_PRO_PLUGIN_URL . 'assets/js/slick.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );

		// mansory layout scripts
		wp_register_script( 'imagesloaded', ECT_PRO_PLUGIN_URL . 'assets/js/imagesloaded.pkgd.min.js' );
		wp_register_script( 'masonry-lib', ECT_PRO_PLUGIN_URL . 'assets/js/masonry-3.1.4.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		wp_register_script( 'masonry.filter', ECT_PRO_PLUGIN_URL . 'assets/js/masonry.filter.js', array( 'jquery', 'masonry-lib' ), ECT_PRO_VERSION, true );
		wp_register_script( 'ect-masonry-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-masonry.js', array( 'jquery', 'masonry-lib', 'masonry.filter', 'imagesloaded' ), ECT_PRO_VERSION, true );

		// like and share style and script
		wp_register_script( 'ect-events_data', ECT_PRO_PLUGIN_URL . 'assets/js/ect-sendajax-request.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		wp_register_script( 'ect-sharebutton', ECT_PRO_PLUGIN_URL . 'assets/js/ect-sharebutton.js', array( 'jquery' ), ECT_PRO_VERSION, true );

		wp_register_script( 'ect-common-scripts', ECT_PRO_PLUGIN_URL . 'assets/js/ect.js', array( 'jquery' ), ECT_PRO_VERSION, true );

	}

	/*** Loading required styles/scripts according to the type of layout */
	public static function ect_load_requried_assets( $template, $style, $slider_pp_id, $autoplay, $carousel_slide_show ) {

		// load common styles and scripts
		// wp_enqueue_style('ect-common-styles');
		wp_enqueue_style( 'ect-common-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-common-styles.min.css', null, ECT_PRO_VERSION, 'all' );
		wp_enqueue_script( 'ect-common-scripts', ECT_PRO_PLUGIN_URL . 'assets/js/ect.js', array( 'jquery' ), ECT_PRO_VERSION, true );
		$custom_style = EctPROStyles::ect_custom_styles( $template, $style );
		/*** TIMELINE styles/scripts */
		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ) ) ) {
			wp_enqueue_style( 'ect-timeline-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-timeline-view.css', null, ECT_PRO_VERSION, 'all' );

			wp_add_inline_style( 'ect-timeline-styles', $custom_style );
		}
		 /*** LIST styles/scripts */
		elseif ( in_array( $template, array( 'default', 'classic-list', 'modern-list' ) ) ) {
			wp_enqueue_style( 'ect-list-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-list-view.css', null, ECT_PRO_VERSION, 'all' );
			wp_add_inline_style( 'ect-list-styles', $custom_style );
		} elseif ( $template == 'minimal-list' ) {
			wp_enqueue_style( 'ect-minimal-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-minimal-list-view.css', null, ECT_PRO_VERSION, 'all' );
			wp_add_inline_style( 'ect-minimal-styles', $custom_style );
		}
		/*** ACCORDION styles/scripts */
		elseif ( $template == 'accordion-view' ) {
			wp_enqueue_style( 'ect-accordion-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-accordion-view.css', null, ECT_PRO_VERSION, 'all' );

			wp_add_inline_style( 'ect-accordion-styles', $custom_style );
			wp_enqueue_style( 'ect-collapse-bootstrap', ECT_PRO_PLUGIN_URL . 'assets/css/collapse-bootstrap-4.0.min.css', null, ECT_PRO_VERSION, 'all' );

			// wp_enqueue_style('ect-custom-icons');
		}  /*** GRID styles/scripts */
		elseif ( $template == 'grid-view' ) {
			// wp_enqueue_style('ect-grid-view-bootstrap');
			wp_enqueue_style( 'ect-grid-view-bootstrap', ECT_PRO_PLUGIN_URL . 'assets/css/bootstrap.min.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_style( 'ect-grid-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-grid-view.css', null, ECT_PRO_VERSION, 'all' );
			// wp_enqueue_style('ect-grid-styles');
			wp_add_inline_style( 'ect-grid-styles', $custom_style );

		}
		/*** MASONRY styles/scripts */
		elseif ( $template == 'masonry-view' ) {
			wp_enqueue_style( 'ect-grid-view-bootstrap', ECT_PRO_PLUGIN_URL . 'assets/css/bootstrap.min.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_style( 'ect-grid-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-grid-view.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_script( 'masonry-lib', ECT_PRO_PLUGIN_URL . 'assets/js/masonry-3.1.4.js', array( 'jquery' ), ECT_PRO_VERSION, true );
			wp_enqueue_script( 'imagesloaded', ECT_PRO_PLUGIN_URL . 'assets/js/imagesloaded.pkgd.min.js' );

			wp_enqueue_script( 'masonry.filter', ECT_PRO_PLUGIN_URL . 'assets/js/masonry.filter.js', array( 'jquery', 'masonry-lib' ), ECT_PRO_VERSION, true );
			wp_enqueue_script( 'ect-masonry-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-masonry.js', array( 'jquery', 'masonry-lib', 'masonry.filter', 'imagesloaded' ), ECT_PRO_VERSION, true );

			wp_add_inline_style( 'ect-grid-styles', $custom_style );

		}

		/*** SLIDER styles/scripts */
		elseif ( in_array( $template, array( 'slider-view' ) ) ) {
			wp_enqueue_style( 'ect-slider-slick', ECT_PRO_PLUGIN_URL . 'assets/css/slick.min.css', null, ECT_PRO_VERSION, 'all' );
			// wp_enqueue_style('ect-slider-slick');
			wp_enqueue_style( 'ect-slider-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-slider-view.css', null, ECT_PRO_VERSION, 'all' );
			// wp_enqueue_style('ect-slider-styles');
			wp_add_inline_style( 'ect-slider-styles', $custom_style );
			wp_enqueue_script( 'ect-slider-slick-js', ECT_PRO_PLUGIN_URL . 'assets/js/slick.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );
			// wp_enqueue_script('ect-slider-slick-js');
			$next_arrow = '<div class="ctl-slick-next"><i class="ect-icon-right"></i></div>';
			$prev_arrow = '<div class="ctl-slick-prev"><i class="ect-icon-left"></i></div>';
			wp_add_inline_script(
				'ect-slider-slick-js',
				"
                  (function($) {
                    $('#" . $slider_pp_id . "').not('.slick-initialized').slick({
                        dots: false,
                        infinite: true,
                        nextArrow:'" . $next_arrow . "',
                        prevArrow:'" . $prev_arrow . "',
                        slidesToShow: 1,
                        speed:3000,
                        infinite: $autoplay,
                        autoplay: $autoplay,
                    });
                })(jQuery);
             "
			);
		}

		/*** SLIDER styles/scripts */
		elseif ( $template == 'cover-view' ) {
			wp_enqueue_style( 'ect-slider-slick', ECT_PRO_PLUGIN_URL . 'assets/css/slick.min.css', null, ECT_PRO_VERSION, 'all' );

			wp_enqueue_style( 'ect-cover-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-cover-view.css', null, ECT_PRO_VERSION, 'all' );
			wp_add_inline_style( 'ect-cover-styles', $custom_style );
			wp_enqueue_script( 'ect-slider-slick-js', ECT_PRO_PLUGIN_URL . 'assets/js/slick.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );

			$prev_arrow = '<button class="ctl-slick-prev">Previous</button>';
			$next_arrow = '<button class="ctl-slick-next">Next</button>';
			wp_add_inline_script(
				'ect-slider-slick-js',
				"
                  (function($) {
                    $('#" . $slider_pp_id . "').not('.slick-initialized').slick({
                        dots: false,
                        infinite: true,
                        prevArrow:'" . $prev_arrow . "',
                        nextArrow:'" . $next_arrow . "',
                        slidesToShow: 1,
                        infinite: $autoplay,
                        autoplay: $autoplay,
                        adaptiveHeight: true,
                    });
                })(jQuery);
             "
			);
		}
		 /*** Advane List styles/scripts */
		elseif ( $template == 'advance-list' ) {
			wp_enqueue_style( 'ect-advance-list-datatable-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-datatable.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_style( 'ect-datatable-responsive', ECT_PRO_PLUGIN_URL . 'assets/css/ect-datatable-responsive.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_style( 'ect-advance-list-css', ECT_PRO_PLUGIN_URL . 'assets/css/ect-advance-list.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_script( 'ect-advance-list-datatable-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-datatable.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );
			wp_enqueue_script( 'ect-advance-list-dt-res', ECT_PRO_PLUGIN_URL . 'assets/js/ect-datatable-responsive.js', array( 'jquery', 'ect-advance-list-datatable-js' ), ECT_PRO_VERSION, true );
			wp_enqueue_script( 'ect-advance-list-js', ECT_PRO_PLUGIN_URL . 'assets/js/ect-advance-list.js', array( 'jquery', 'ect-advance-list-datatable-js' ), ECT_PRO_VERSION, true );
			wp_add_inline_style( 'ect-advance-list-css', $custom_style );
		}
		/*** CAROUSEL styles/scripts */
		elseif ( in_array( $template, array( 'carousel-view' ) ) ) {
			wp_enqueue_style( 'ect-slider-slick', ECT_PRO_PLUGIN_URL . 'assets/css/slick.min.css', null, ECT_PRO_VERSION, 'all' );
			wp_enqueue_style( 'ect-carousel-styles', ECT_PRO_PLUGIN_URL . 'assets/css/ect-carousel-view.css', null, ECT_PRO_VERSION, 'all' );
			// wp_enqueue_style('ect-slider-slick');
			// wp_enqueue_style('ect-carousel-styles');
			wp_add_inline_style( 'ect-carousel-styles', $custom_style );
			wp_enqueue_script( 'ect-slider-slick-js', ECT_PRO_PLUGIN_URL . 'assets/js/slick.min.js', array( 'jquery' ), ECT_PRO_VERSION, true );
			// ('ect-slider-slick-js');
			$next_arrow = '<div class="ctl-slick-next"><i class="ect-icon-right"></i></div>';
			$prev_arrow = '<div class="ctl-slick-prev"><i class="ect-icon-left"></i></div>';
			wp_add_inline_script(
				'ect-slider-slick-js',
				"
                (function($) {
                    $('#" . $slider_pp_id . "').not('.slick-initialized').slick({
                        dots: false,
                        infinite: $autoplay,
                        autoplay: $autoplay,
                        slidesToShow: $carousel_slide_show,
                        arrows:true,
                        slidesToScroll: 1,
                        nextArrow:'" . $next_arrow . "',
                        prevArrow:'" . $prev_arrow . "',
                        responsive: [
                        {
                        breakpoint: 950,
                        settings: {
                            slidesToShow: 2
                        }
                        },
                        {
                        breakpoint: 580,
                        settings: {
                            slidesToShow: 1
                        }
                        }
                        ]
                    });
                })(jQuery);
            "
			);
		}

	}


}
