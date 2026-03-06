# Changelog

### [5.0.7] 2025-05-20

* Version - Events Community 5.0.7 is only compatible with The Events Calendar 6.13.0 and higher.
* Version - Events Community 5.0.7 is only compatible with Event Tickets 5.23.0 and higher.
* Fix - Corrected an issue where Admin Alerts would fatal when TEC was not active [CE-268]
* Tweak - Make sure update callbacks are executed prior `wp_loaded` action. [TEC-5436]
* Tweak - Improved email alert templates with dedicated versions for TEC and Event Tickets integrations.
* Tweak - Added helper functions for event labels that respect plugin integrations.
* Tweak - Refactored email alert handling for better maintainability and extensibility.
* Tweak - Switched from using `tribe_tickets_plugin_loaded` to `tec_tickets_fully_loaded` for better Event Tickets compatibility.
* Tweak - Added template path filters to allow customizing email template paths
* Deprecated - Deprecated `sendEmailAlerts` method in favor of `send_email_alerts` with improved functionality
* Language - 3 new strings added, 40 updated, 0 fuzzied, and 1 obsoleted

### [5.0.6] 2025-03-04

* Tweak - Switched `$event[ID]` to `$events->ID` in `update_series` function. [CE-253]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted.

### [5.0.5.1] 2025-02-19

* Fix - Corrected an issue where split payments weren't working due to a missing PayPal library. [CE-264]
* Language - 0 new strings added, 8 updated, 0 fuzzied, and 0 obsoleted.

### [5.0.5] 2024-10-21

* Fix - Organizer validation updated to only require name rather than all fields. [CE-248]
* Fix - Venue validation updated to only require name rather than all fields. [CE-249]
* Fix - Correct text in Community URLs tab. [CE-250]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 1 obsoleted

### [5.0.4] 2024-09-26

* Version - Events Community 5.0.4 is only compatible with The Events Calendar 6.7.0 and higher.
* Feature - New and improved settings layout and styles. [TEC-5124]
* Tweak - Introduced a new filter tec_events_community_event_slug allowing customization of the event slug. By default, when The Events Calendar is installed, the Events URL Slug will be used. [CE-243]
* Language - 6 new strings added, 171 updated, 1 fuzzied, and 9 obsoleted

### [5.0.3] 2024-09-11

* Fix - Corrected an issue where a fatal would occur when saving an event with specific Taxonomy for users other than admin. [CE-236]
* Fix - Fix reCaptcha for anonymous event submissions. [TEC-5143]
* Fix - Updated compatibility logic for WooCommerce HPOS. [ETP-940]
* Tweak - Updated template override paths in `src/views/integrations/the-events-calendar/modules/[file].php`. [TEC-5149]
* Language - 0 new strings added, 70 updated, 0 fuzzied, and 0 obsoleted

### [5.0.2] 2024-08-28

* Fix - Set the global $post variable with the event when on the Event Submission page to fix issues with third party plugins. [CE-238][CE-239][CE-240]

### [5.0.1.1] 2024-08-07

* Fix - Corrected an issue where a fatal would occur when saving an event with specific Taxonomy for users other than admin. [CE-236]

### [5.0.1] 2024-08-06

* Tweak - Improved the logic for handling case sensitivity when using the `tribe_events_community_required_fields` filter. [CE-232][CE-233].
* Fix - Ensure we don't try to enqueue nonexistent override stylesheets. [ECP-1811]
* Tweak - Changed views: `integrations/the-events-calendar/modules/taxonomy`
* Language - 0 new strings added, 64 updated, 0 fuzzied, and 0 obsoleted

### [5.0.0.1] 2024-07-23

* Fix - Added compatibility check for WooCommerce HPOS when Ticket functionality is enabled. [CE-229]
* Fix - Corrected a fatal that occurred when saving the settings with "Allow Anonymous Submissions" enabled. [CE-230]
* Language - 1 new strings added, 36 updated, 0 fuzzied, and 0 obsoleted

### [5.0.0] 2024-07-22

* Version - Events Community 5.0.0 is only compatible with The Events Calendar 6.6.0 and higher.
* Version - Events Community 5.0.0 is only compatible with Event Tickets 5.13.0 and higher.
* Feature - Added the ability to sell tickets via Community when using Event Tickets.
* Feature - Added the ability to sell tickets via Community with any post type.
* Tweak - Added filters: `tec_community_tickets_settings_provider_options`, `tec_community_tickets_settings_provider_whitelist`, `tec_events_community_submission_allowed_organizer_fields`, `tec_events_community_submission_allowed_venue_fields`, `tec_events_community_event_tickets_event_update_args`, `tec_events_community_event_tickets_event_prevent_update`, `tec_events_community_event_tickets_event_insert_args`, `tec_events_community_submission_save_handler`, `tec_events_community_submission_custom_required_validation_{$key}`, `tec_events_community_validate_field_contents`, `tec_events_community_get_urls_for_actions`, `tec_events_community_submission_anonymous_users_handler`, `tec_events_community_posttype`, `tec_events_community_settings_strategy`, `tribe_community_events_event_label_singular`, `tribe_community_events_event_label_plural`, `tribe_community_events_event_label_singular_lowercase`, `tribe_community_events_event_label_default`, `tribe_community_events_get_event_query`, `tec_events_community_form_layout`, `tec_events_community_modify_default_rewrite_slugs`, `tec_events_community_allowed_fields`, `tec_events_community_allowed_fields_inner_key_{$key}`, `tribe_events_template_paths`, `tribe_events_template`, `tribe_events_template_`, `tribe_get_template_part_templates`, `tribe_get_template_part_path`, `tribe_get_template_part_path_`, `tribe_get_template_part_content`, `tribe_community_tickets_supported_currencies`, `tribe_community_tickets_cart_fee_text`, `tribe_community_tickets_add_fee_to_all_tickets`, `tribe-events-community-tickets-event-list-attendees-button-text`, `tribe-events-community-tickets-event-list-sales-button-text`, `tribe_community_events_tickets_can_update_ticket_price`, `tribe_community_events_tickets_get_options`, `tribe_get_single_option`, `tribe_community_tickets_fee_migration_interval`, `tribe_community_tickets_fee_migration_batch_size`, `tribe_enable_pue`, `tribe_community_tickets_register_payout_post_type_args`, `tribe_community_tickets_payouts_organizer_fee_display_override`, `event_community_tickets_show_payouts_on_front_end`, `tribe_community_tickets_payouts_record_changes_on_status_change`, `tribe_community_tickets_paypal_payment_args`, `tribe_community_tickets_payouts_paypal_api_context_config`, `tribe_community_tickets_payouts_paypal_api_sender_subject`, `tribe_community_tickets_payout_supported_meta_keys`, `tribe_community_tickets_payouts_record_processor_interval`, `tribe_community_tickets_payouts_batch_size`, `event_community_tickets_event_action_links_edit_url`, `tribe_tickets_attendees_event_action_links`, `tribe_community_tickets_payouts_tabbed_view_tab_map`, `events_community_tickets_payouts_table_column`, `events_community_tickets_payouts_pagination`, `tribe_community_tickets_template`, `tribe_community_tickets_attendees_report_title`, `tribe_community_tickets_attendees_report_url`, `tribe_community_tickets_payment_options_title`, `tribe_community_tickets_event_list_payment_options_button_text`, `tribe_community_tickets_payment_options_url`, `tribe_community_tickets_sales_report_title`, `tribe_community_tickets_sales_report_url`, `tribe_community_settings_base_urls`, `tribe_community_settings_edit_urls`, `tec_events_community_events_listing_show_prev_next_nav`, `tribe_community_tickets_payout_repository_map`, `tec_community_tickets_is_tickets_commerce_enabled`, `tribe_community_tickets_attendees_show_title`, `tec_events_community_events_listing_display_options_dropdown`, `tec_events_community_header_links_title`
* Tweak - Removed filters: `tribe_events_community_template`, `tec_events_community_event_form_post_id`, `tribe_community_events_show_form`, `tribe_events_community_required_organizer_fields`, `tribe_events_community_my_events_query_orderby`, `tribe_events_community_my_events_query_order`, `tribe_events_community_my_events_query`, `tribe_events_community_list_page_template_include`, `tribe_community_events_rewrite_slug`, `tribe_community_events_`, `tribe_events_community_submission_message`, `tribe_events_community_allowed_event_fields`, `tribe_events_community_allowed_venue_fields`, `tribe_events_community_allowed_organizer_fields`
* Tweak - Added actions: `tribe_events_event_save_failed_invalid_meta`, `tribe_events_event_save`, `tribe_events_update_meta`, `tribe_community_event_save_failure`, `tribe_community_event_save_updated`, `tec_events_community_event_form_setup_hooks`, `tec_events_community_form_before_module_{$module_key}`, `tec_events_community_form_after_module_{$module_key}`, `tribe_pre_get_template_part_`, `tribe_before_get_template_part`, `tribe_after_get_template_part`, `tribe_post_get_template_part_`, `woocommere_paypal_adaptive_payments_ipn`, `tribe_community_tickets_payouts_process_queue`, `tribe_community_tickets_orders_report_site_fees_note`, `tribe_community_tickets_attendees_nav`, `tribe_community_tickets_sales_report_nav`, `events_community_tickets_report_list_first`, `events_community_tickets_after_report_list_first`, `events_community_tickets_report_list_middle`, `events_community_tickets_after_report_list_middle`, `events_community_tickets_report_list_last`, `events_community_tickets_after_report_list_last`, `tribe_community_tickets_payment_options_nav`, `tribe_community_tickets_before_the_payment_options`, `tec_community_tickets_after_the_payment_options`, `tribe_events_tickets_attendees_event_details_top`, `tribe_tickets_attendees_event_details_list_top`, `tribe_tickets_attendees_event_details_list_bottom`, `tribe_community_tickets_attendees_do_event_action_links`, `tribe_community_tickets_attendees_event_details_bottom`, `tribe_events_tickets_attendees_ticket_sales_top`, `tribe_events_tickets_attendees_ticket_sales_bottom`, `tribe_events_tickets_attendees_totals_top`, `tribe_tickets_attendees_totals`, `tribe_events_tickets_attendees_totals_bottom`, `tribe_community_tickets_attendees_event_summary_table_after`, `tribe_events_community_section_before_tickets`, `tribe_events_community_section_after_tickets`
* Tweak - Removed actions: `tribe_community_before_event_page`, `tribe_events_community_event_submission_login_form`, `tribe_events_community_before_event_submission_page`, `tribe_events_community_after_form_validation`, `tribe_events_community_before_event_submission_page_template`, `tribe_events_community_before_event_list_page`, `tribe_events_community_before_event_list_page_template`, `tribe_tribe_events_community_event_list_login_form`, `tribe_events_community_form_before_linked_posts`, `tribe_events_community_form_after_linked_posts`
* Tweak - Changed views: `community-tickets/modules/email-item-event-details`, `community-tickets/modules/orders-report-after-organizer`, `community-tickets/modules/payment-options`, `community-tickets/modules/shortcode-attendees`, `community-tickets/modules/tickets`, `community/blank-comments-template`, `community/columns/category`, `community/columns/end_date`, `community/columns/organizer`, `community/columns/recurring`, `community/columns/start_date`, `community/columns/status`, `community/columns/title`, `community/columns/venue`, `community/default-placeholder`, `community/default-template`, `community/edit-event`, `community/edit-organizer`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/login-form`, `community/modules/captcha`, `community/modules/custom/fields/dropdown`, `community/modules/custom/fields/input-option`, `community/modules/custom/fields/text`, `community/modules/custom/fields/textarea`, `community/modules/custom/fields/url`, `community/modules/custom/table-row`, `community/modules/custom/table`, `community/modules/delete`, `community/modules/description`, `community/modules/header-links`, `community/modules/image`, `community/modules/organizer-fields`, `community/modules/recurrence`, `community/modules/spam-control`, `community/modules/submit`, `community/modules/terms`, `community/modules/title`, `community/modules/venue-fields`, `community/modules/virtual`, `community/edit-venue`, `community/modules/cost`, `community/modules/custom`, `community/modules/datepickers`, `community/modules/organizer`, `community/modules/series`, `community/modules/taxonomy`, `community/modules/venue`, `community/modules/website`
* Language - 171 new strings added, 171 updated, 20 fuzzied, and 38 obsoleted

### [4.10.18] 2024-06-11

* Feature - Adapt to the change to using Stellar Assets in tribe_asset() [TCMN-172]
* Fix - Corrected template override path from `tribe` back to `tribe-events` for `/src/views/community/[file].php` for `edit-event.php`, `email-template.php`, and `default-template.php`. [CE-208]
* Language - 0 new strings added, 57 updated, 0 fuzzied, and 0 obsoleted

### [4.10.17] 2024-04-18

* Feature - Add compatibility with the new Attendees page. [ET-1707]
* Fix - Corrected an issue where duplicate events could be created when refreshing the submitted event page. [CE-209]
* Fix - Corrected a missing <div> in default-template.php. [CE-207]
* Fix - Corrected template override paths from `tribe-events` to `tribe` for templates in the `/src/views/community` folder. [CE-208]
* Tweak - Added filters: `tec_events_community_event_form_post_id`, `tec_events_community_event_editor_post_content`
* Tweak - Changed views: `community/blank-comments-template`, `community/default-placeholder`, `community/default-template`, `community/edit-event`, `community/edit-organizer`, `community/edit-venue`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/login-form`
* Language - 0 new strings added, 29 updated, 0 fuzzied, and 0 obsoleted

### [4.10.16] 2024-03-25

* Fix - Corrected an issue that was occurring when using the pagination on the `My Events` page. [CE-204]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

### [4.10.15] 2024-02-06

* Fix - Implement dynamic event names within the header links template. [CE-197]
* Tweak - Added additional validation for when End Time is prior to Start Time. [CE-196]
* Tweak - Changed views: `community/modules/header-links`
* Language - 1 new strings added, 5 updated, 0 fuzzied, and 0 obsoleted

### [4.10.14] 2023-12-13

* Feature - Refactored Community Events callbacks, separating logic into new directory for enhanced maintainability. [CE-188]
* Tweak - Pagination on Past Events will now allow you to go back to page 1 without resetting. [CE-188]
* Tweak - Pagination when using shortcodes will now properly work. [CE-188]
* Tweak - Hardened the logic for users who can edit their own submissions. [CE-188]
* Language - 4 new strings added, 78 updated, 0 fuzzied, and 3 obsoleted

### [4.10.13] 2023-09-13

* Fix - Event Status will now properly save when the user does not have the correct capabilities. [CE-194]
* Fix - When an event is submitted, the event edit link will now only display when `Edit their submissions` is enabled. [GTRIA-1062]
* Language - 0 new strings added, 25 updated, 0 fuzzied, and 0 obsoleted

### [4.10.12] 2023-08-16

* Tweak - Improved styling for checkboxes when using Twenty Twenty-One theme. [CE-190]
* Fix - When deleting recurring events, the page will now automatically refresh to ensure the proper removal of all instances. [CE-191]
* Language - 0 new strings added, 55 updated, 0 fuzzied, and 0 obsoleted

### [4.10.11] 2023-07-18

* Tweak - Deprecated Methods, Filters, and Actions older than version 4.9.2 have been removed. See release notes for a complete list. [CE-185]
* Tweak - Removed filters: `tribe_get_event_website_url`
* Tweak - Changed views: `community/columns/title`
* Language - 0 new strings added, 66 updated, 0 fuzzied, and 1 obsoleted

### [4.10.10] 2023-07-13

* Tweak - Updated Community Events routes to fix a potential fatal with other plugins that use a di52 container. [CE-187]

### [4.10.9] 2023-06-29

* Tweak - Refactored Community Events routes for improved testability and robustness. [CE-184]
* Tweak - Updated internal test for Edit Event, Title, and Website views.
* Tweak - Added filters: `tec_events_community_{$this->get_slug()}_page_title`, `tec_events_community_route_page_title`
* Tweak - Removed filters: `tribe_events_community_edit_event_page_title`, `tribe_events_community_submit_event_page_title`, `tribe_events_community_remove_event_page_title`, `tribe_events_community_event_list_page_title`
* Tweak - Changed views: `community/edit-event`, `community/modules/title`, `community/modules/website`
* Language - 2 new strings added, 62 updated, 0 fuzzied, and 1 obsoleted

### [4.10.8] 2023-06-22

* Version - Events Community 4.10.8 is only compatible with The Events Calendar 6.1.2 and higher.
* Version - Events Community 4.10.8 is only compatible with Event Tickets 5.6.1 and higher.
* Fix - Lock our container usage(s) to the new Service_Provider contract in tribe-common. This prevents conflicts and potential fatals with other plugins that use a di52 container.

### [4.10.7] 2023-05-04

* Tweak - Added internal tests for Terms. [CE-72]
* Tweak - Added unique CSS class selectors to the Venue and Orgization sections on the Submit Event page. [GTRIA-967]
* Tweak - Added filter `tec_events_community_taxonomy_module_template_classes` to allow customizing classes for the Venue and Orgization sections on the Submit Event page. [GTRIA-967]
* Fix - Prevent styling issue for Virtual Events when using the Twenty Twenty theme.
* Language - 0 new strings added, 5 updated, 0 fuzzied, and 0 obsoleted

### [4.10.6] 2023-04-03

* Tweak - The Organizer and Venue fields will now only show published posts. [CE-27]
* Tweak - Hide the share area on Virtual Events if Community Tickets is not enabled. [CE-112]
* Tweak - Changed views: `community/modules/terms`
* Fix - Upcoming events will display events that that are currently ongoing. [CE-179]
* Fix - `Terms of submission` will now properly display special characters. [CE-72]
* Language - 0 new strings added, 88 updated, 0 fuzzied, and 0 obsoleted

### [4.10.5] 2023-03-08

* Fix - Updated the deprecated method Tribe__Events__Main::getPostTypes to use Tribe__Main::get_post_types(). [CE-175]
* Fix - Venues will now be properly required when the required snippet is used in conjunction with `Users cannot create new Venues`. [CE-176]
* Fix - The correct assets will load now when using the `tribe_community_events` shortcode with Events Calendar PRO enabled. [CE-177]

### [4.10.4] 2023-02-22

* Version - Events Community 4.10.4 is only compatible with The Events Calendar 6.0.10 and higher.
* Version - Events Community 4.10.4 is only compatible with Event Tickets 5.5.8 and higher.
* Tweak - PHP version compatibility bumped to PHP 7.4
* Tweak - Version Composer updated to 2
* Tweak - Version Node updated to 18.13.0
* Tweak - Version NPM update to 8.19.3

### [4.10.3] 2022-12-08

* Tweak - Added new action 'tribe_events_community_after_form_validation'. [CE-170]
* Fix - Updated the search found on "My Events" to search either Upcoming Events or Past Events depending on which tab you are on. [CE-171]

### [4.10.2] 2022-11-09

* Fix - Corrected an issue where Subscribers were unable to edit their events. [CE-172]

### [4.10.1] 2022-09-22

* Fix - Correct some malformed custom prop names.
* Language - 0 new strings added, 61 updated, 0 fuzzied, and 0 obsoleted

### [4.10.0] 2022-08-29

* Version - Events Community 4.10.0 is only compatible with The Events Calendar 6.0.0 and higher.
* Version - Events Community 4.10.0 is only compatible with Event Tickets 5.5.0 and higher.
* Tweak - Adds a compatibility layer to work with the new Recurrence Backend Engine in TEC/ECP.
* Language - 4 new strings added, 102 updated, 0 fuzzied, and 0 obsoleted

### [4.9.3] 2022-08-15

* Fix - Corrected an issue where tickets added via Community Tickets weren't being associated correctly.
* Fix - Virtual Events will now work when Anonymous Submissions are enabled. [CE-161]
* Language - 0 new strings added, 103 updated, 0 fuzzied, and 4 obsoleted

### [4.9.2] 2022-07-05

* Tweak - Virtual Events via Community Events now only displays `Video` and `Youtube` as default video options. [CE-159]
* Language - 1 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted.

### [4.9.1] 2022-06-08

* Fix - Making dropdown additional fields required will now trigger validation when `None` is selected. [CE-121]
* Fix - Fixed an issue where the 'My Events' page would return a 404 error when using the pagination. [CE-152]

### [4.9.0] 2022-05-19

* Version - Community Events 4.9.0 is only compatible with The Events Calendar 5.15.0 and higher
* Feature - Add compatibility to the new TEC admin menu system. [ET-1335]
* Language - 0 new strings added, 60 updated, 0 fuzzied, and 0 obsoleted
* Tweak - Allow admins to enable/disable selecting existing Series' to community created events. [ECP-1122]

### [4.8.14] 2022-05-11

* Tweak - Updated the publish status icons (draft, future, pending, publish) to use new SVG images. [CE-149]
* Tweak - Added new filter `tribe_community_events_event_status_icon_extension` to overwrite image extension to allow for customization. [CE-149]
* Tweak - Added filters: `tribe_community_events_event_status_icon_extension`.
* Fix - Fix an issue where ampersands were being escaped. [CE-126]
* Language - 0 new strings added, 8 updated, 0 fuzzied, and 0 obsoleted.

### [4.8.13] 2022-04-05

* Tweak - Changed views: `community/modules/taxonomy`
* Fix - Fix an issue on the Add Events page where category would become blank if the page was refreshed. [CE-140]
* Language - 0 new strings added, 4 updated, 0 fuzzied, and 0 obsoleted

### [4.8.12] 2022-03-15

* Tweak - Changed views: `community/modules/image`
* Fix - Corrected an issue when making the image upload required, didn't make it required. [CE-6]
* Fix - Fixed the image upload when an image was uploaded, removed, and uploaded again. [CE-142]
* Language - 0 new strings added, 67 updated, 0 fuzzied, and 0 obsoleted

### [4.8.11.1] 2022-03-02

* Tweak - Improved The Events Calendar REST API compatibility for Events, Organizers and Venues. [CE-143]

### [4.8.11] 2022-01-19

* Tweak - Added support for the Event Status field. [CE-136]
* Tweak - Added new filter 'tribe_community_events_event_status_enabled'. [CE-136]
* Tweak - Added new action 'tribe_events_community_section_before_event_status' and 'tribe_events_community_section_after_event_status'. [CE-136]
* Tweak - Changed `status`  on the upcoming and past events page to display as `Publish Status`. [CE-136]
* Tweak - Updated the settings description link to use a new user primer. [CE-46]
* Fix - Select2 alignment when logged into WordPress via the add community event page. [CE-136]
* Language - 1 new strings added, 73 updated, 0 fuzzied, and 1 obsolete

### [4.8.10] 2021-12-15

* Fix - Use datepicker format from the date utils library to autofill the start and end dates. [CE-135]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [4.8.9] 2021-11-17

* Fix - Corrected typo and added translation for text on the events list page. [CE-124]
* Fix - Corrected document titles on paginated "My Events" pages. [CE-132]
* Language - 0 new strings added, 61 updated, 0 fuzzied, and 0 obsoleted

### [4.8.8] 2021-09-27

* Tweak - Include compatibility for the latest style modifications on The Events Calendar around the Customizer variables.
* Fix - Virtual Events assets not being loaded when using the [tribe_community_events] shortcode. [CE-115]
* Language - 1 new strings added, 61 updated, 1 fuzzied, and 1 obsoleted

### [4.8.7] 2021-08-03

* Tweak - Updated verbiage when editing recurring events. [CE-125]
* Language - 1 new strings added, 0 updated, 0 fuzzied, and 1 obsoleted

### [4.8.6] 2021-03-30

* Tweak - Changed views: `community/blank-comments-template`, `community/columns/category`, `community/columns/end_date`, `community/columns/organizer`, `community/columns/recurring`, `community/columns/start_date`, `community/columns/status`, `community/columns/title`, `community/columns/venue`, `community/default-placeholder`, `community/edit-event`, `community/edit-organizer`, `community/edit-venue`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/modules/captcha`, `community/modules/cost`, `community/modules/custom`, `community/modules/custom/fields/dropdown`, `community/modules/custom/fields/input-option`, `community/modules/custom/fields/text`, `community/modules/custom/fields/textarea`, `community/modules/custom/fields/url`, `community/modules/custom/table-row`, `community/modules/custom/table`, `community/modules/datepickers`, `community/modules/delete`, `community/modules/description`, `community/modules/header-links`, `community/modules/image`, `community/modules/organizer-fields`, `community/modules/organizer`, `community/modules/recurrence`, `community/modules/spam-control`, `community/modules/submit`, `community/modules/taxonomy`, `community/modules/terms`, `community/modules/title`, `community/modules/venue-fields`, `community/modules/venue`, `community/modules/virtual`, `community/modules/website`
* Fix - Updated the URL that points to the reCAPTCHA creation page. [CE-122]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 1 obsoleted

### [4.8.5] 2021-03-04

* Version - Community Events 4.8.5 is only compatible with The Events Calendar 5.4.0 and higher.
* Fix - Compatibility with WordPress 5.7 and jQuery 3.5.X [CE-111]
* Language - 0 new strings added, 7 updated, 0 fuzzied, and 0 obsoleted

### [4.8.4] 2021-02-15

* Tweak - Changed views: `community/blank-comments-template`, `community/columns/category`, `community/columns/end_date`, `community/columns/organizer`, `community/columns/recurring`, `community/columns/start_date`, `community/columns/status`, `community/columns/title`, `community/columns/venue`, `community/default-placeholder`, `community/edit-event`, `community/edit-organizer`, `community/edit-venue`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/modules/captcha`, `community/modules/cost`, `community/modules/custom`, `community/modules/custom/fields/dropdown`, `community/modules/custom/fields/input-option`, `community/modules/custom/fields/text`, `community/modules/custom/fields/textarea`, `community/modules/custom/fields/url`, `community/modules/custom/table-row`, `community/modules/custom/table`, `community/modules/datepickers`, `community/modules/delete`, `community/modules/description`, `community/modules/header-links`, `community/modules/image`, `community/modules/organizer-fields`, `community/modules/organizer`, `community/modules/recurrence`, `community/modules/spam-control`, `community/modules/submit`, `community/modules/taxonomy`, `community/modules/terms`, `community/modules/title`, `community/modules/venue-fields`, `community/modules/venue`, `community/modules/virtual`, `community/modules/website`
* Language - 2 new strings added, 2 updated, 0 fuzzied, and 2 obsoleted

### [4.8.3] 2020-12-15

* Feature - Add Customizer integration and CSS overrides for background color. [CE-105]
* Fix - Enqueue Virtual Events' Admin CSS and Admin JS, if applicable, which resolves the issue of clicking to generate a Zoom link taking the event editor to a new URL in the browser with a large display of a video icon SVG. [CE-104]

### [4.8.2] 2020-11-19

* Tweak - Add helper text to roles to block settings clarifying the use case. [CE-100]
* Tweak - Add helper section notices for updated template structure. [CE-103]
* Tweak - Changed views: `community/blank-comments-template`, `community/columns/category`, `community/columns/end_date`, `community/columns/organizer`, `community/columns/recurring`, `community/columns/start_date`, `community/columns/status`, `community/columns/title`, `community/columns/venue`, `community/default-placeholder`, `community/edit-event`, `community/edit-organizer`, `community/edit-venue`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/modules/captcha`, `community/modules/cost`, `community/modules/custom`, `community/modules/custom/fields/dropdown`, `community/modules/custom/fields/input-option`, `community/modules/custom/fields/text`, `community/modules/custom/fields/textarea`, `community/modules/custom/fields/url`, `community/modules/custom/table-row`, `community/modules/custom/table`, `community/modules/datepickers`, `community/modules/delete`, `community/modules/description`, `community/modules/header-links`, `community/modules/image`, `community/modules/organizer-fields`, `community/modules/organizer`, `community/modules/recurrence`, `community/modules/spam-control`, `community/modules/submit`, `community/modules/taxonomy`, `community/modules/terms`, `community/modules/title`, `community/modules/venue-fields`, `community/modules/venue`, `community/modules/virtual`, `community/modules/website`
* Language - 2 new strings added, 96 updated, 0 fuzzied, and 0 obsoleted

### [4.8.1] 2020-09-21

* Tweak - Add more spacing between checkboxes for the Virtual Events integration. [CE-95]
* Tweak - Changed views: `community/modules/taxonomy`
* Fix - Show the saved category and tags while editing the event from frontend. [CE-73]
* Fix - Ensure that submission for multiple category or tags is saved properly. [CE-70]
* Language - 0 new strings added, 3 updated, 0 fuzzied, and 0 obsoleted

### [4.8.0] 2020-08-26

* Tweak - Removed unused HTML files in the plugin root folder that were there for your reference to our plugin's data collection transparency. This information is included within WordPress' Privacy Guide at /wp-admin/privacy-policy-guide.php [CE-74]
* Feature - New integration with the Virtual Events plugin will allow configuring Virtual Events from the front of your site. Zoom connection and link generation is disabled for all non-admins on the front, but you can generate links as an admin from the front or within the Event Editor itself. [CE-86]
* Language - 0 new strings added, 69 updated, 0 fuzzied, and 0 obsoleted

### [4.7.3] 2020-07-28

* Fix - Ensure required fields added via hooks suggested on our knowledge base articles are still fully functional. [CE-76]
* Language - 0 new strings added, 22 updated, 0 fuzzied, and 0 obsoleted

### [4.7.2] 2020-06-24

* Tweak - Added filters: `tribe_community_events_virtual_events_integration_enabled`, `tribe_events_community_required_organizer_fields`
* Tweak - Added actions: `tribe_events_community_section_before_virtual`, `tribe_events_community_section_after_virtual`
* Tweak - Changed views: `community/modules/virtual`
* Fix - Validation of required Description field for Community submission works as expected with Visual Editor (tinyMCE). [CE-64]
* Fix - Ensure that when requiring organizer via filter for event submissions that the -1 option is handled properly. [CE-63]
* Fix - Linked Posts title sanitization for Organizer and Venue handles all data submission formats supported previously, resolving problem with String being converted to Array. [CE-69]
* Language - 0 new strings added, 31 updated, 0 fuzzied, and 0 obsoleted

### [4.7.1] 2020-05-20

* Feature - Add the "Terms of Submission" setting to allow requiring accepting the terms before submitting the events form. [CE-58]
* Tweak - Add generic JavaScript validation for the event community form. [CE-10]
* Fix - Fix JavaScript validation error when tinyMCE wasn't loaded. [CE-10]
* Language - 8 new strings added, 169 updated, 0 fuzzied, and 2 obsoleted

### [4.7.0] 2020-04-23

* Tweak - Deprecate Select2 3.5.4 in favor of SelectWoo
* Tweak - Load plugin text domain on the new 'tribe_load_text_domains' hook instead of the 'plugins_loaded' hook to support third-party translation providers. [CE-50]
* Fix - Fix some issues that could cause PHP errors and notices during the plugin activation. [CE-47]
* Fix - Remove duplicate registration of the `[tribe_community_events]` shortcode to avoid PHP notice and correct loading of assets. [CE-52]
* Language - 0 new strings added, 70 updated, 0 fuzzied, and 0 obsoleted

### [4.6.7] 2019-02-06

* Tweak - Changed views: `community/modules/venue-fields`
* Fix - Update WP Router library to avoid fatal errors with PHP 7.3 when using custom rewrite routes for Community Events URLs [CE-4]
* Fix - "Community URLs" section of admin settings warns if Pretty Permalinks are not enabled and fixes missing slash in URLs if site's "Homepage" option is set to "Main Events Page" [CE-5]
* Language - 1 new strings added, 162 updated, 0 fuzzied, and 9 obsoleted

### [4.6.6.1] 2019-10-16

* Fix - Resolved problem with CSS styles missing from the last release ZIP package [135851]

### [4.6.6] 2019-10-14

* Fix - Custom Rewrite URL slugs are no longer able to be accidentally reset by saving the settings page without making any changes [133395]
* Language - 1 new strings added, 73 updated, 1 fuzzied, and 1 obsoleted

### [4.6.5] 2019-09-16

* Fix - Prevent Community custom settings styles from "bleeding" into other settings tabs. [132357]
* Fix - "Email addresses to be notified" option now saves its input [131196]
* Fix - Enqueue Thickbox script on all admin pages when needed [131080]
* Language - 0 new strings added, 23 updated, 0 fuzzied, and 0 obsoleted

### [4.6.4] 2019-08-22

* Fix - Adjusted CSS/JS loading for Community Events frontend views [131669]
* Fix - Adjusted CSS for Community settings tab fields [131669]
* Language - 0 new strings added, 82 updated, 0 fuzzied, and 0 obsoleted

### [4.6.3] 2019-07-18

* Feature - Add shortcode attributes to change the Add New and View Your Submitted Events links to custom urls [128295]
* Tweak - A failed login now keeps the user on the front end login form, displays an error message, and the overall login form's styling is more consistent; added new `tribe_events_community_successful_login_redirect_to` filter [40584]
* Tweak - Added filters: `tribe_events_community_edit_event_page_title`, `tribe_events_community_submit_event_page_title`, `tribe_events_community_remove_event_page_title`, `tribe_events_community_event_list_page_title`, `tribe_events_community_submit_event_page_title`, `tribe_events_community_successful_login_redirect_to`, `tribe_events_community_my_events_query_orderby`, `tribe_events_community_my_events_query_order`, `tribe_events_community_my_events_query`, `tribe_events_community_logout_url_redirect_to`, `tribe_events_community_submit_event_page_title`
* Tweak - Removed filters: `tribe_ce_edit_event_page_title`, `tribe_ce_submit_event_page_title`, `tribe_ce_remove_event_page_title`, `tribe_ce_event_list_page_title`, `tribe_ce_submit_event_page_title`, `tribe_ce_my_events_query_orderby`, `tribe_ce_my_events_query_order`, `tribe_ce_my_events_query`, `tribe_community_events_allowed_taxonomies`, `tribe_events_community_required_venue_fields`, `tribe_ce_submit_event_page_title`
* Tweak - Added actions: `tribe_events_community_event_submission_login_form`, `tribe_events_community_before_event_submission_page`, `tribe_events_community_before_event_submission_page_template`, `tribe_events_community_before_event_list_page`, `tribe_events_community_before_event_list_page_template`, `tribe_tribe_events_community_event_list_login_form`, `tribe_events_community_event_submission_login_form`, `tribe_events_community_event_list_table_row_actions`
* Tweak - Removed actions: `tribe_ce_event_submission_login_form`, `tribe_ce_before_event_submission_page`, `tribe_ce_before_event_submission_page_template`, `tribe_ce_before_event_list_page`, `tribe_ce_before_event_list_page_template`, `tribe_ce_event_list_login_form`, `tribe_ce_event_submission_login_form`, `tribe_ce_event_list_table_row_actions`
* Tweak - Changed views: `community/columns/title`, `community/edit-event`, `community/edit-organizer`, `community/edit-venue`, `community/email-template`, `community/event-list-shortcode`, `community/event-list`, `community/modules/custom`, `community/modules/custom/fields/dropdown`, `community/modules/custom/fields/input-option`, `community/modules/custom/fields/text`, `community/modules/custom/fields/textarea`, `community/modules/custom/fields/url`, `community/modules/custom/table-row`, `community/modules/custom/table`, `community/modules/taxonomy`
* Fix - Logged-in users not allowed to access the WordPress Dashboard ("Roles to block" setting) can now be redirected to a custom URL, whether on-site or off-site; the new default URL is the Community Events List View instead of the site's homepage; added new `tribe_events_community_logout_url_redirect_to` filter [72214]
* Fix - tinyMCE.get(...) is null error on submit of event when visual editor is active [128515]
* Fix - Stop translating slugs and let the site owner set them if they so desire [98503]
* Fix - Events Calendar PRO Additional Fields section now renders well for accessibility (A11Y), has correct class names, and drop downs are enhanced with Select2 [127176]
* Fix - Change all namespacing of hooks to match plugin namespacing. Use `apply_filters_deprecated` and `do_action_deprecated` for backwards-compatibility [130084]
* Language - 29 new strings added, 164 updated, 0 fuzzied, and 9 obsoleted

### [4.6.2] 2019-06-20

* Feature - Shortcodes for Community Events. With the [tribe_community_events] shortcode, you can embed the Event Submission form, the "My Events" page and edit forms on posts and pages [78707]
* Feature - Deleting events from the "My Events" page is now done via ajax. [123620]
* Tweak - Reduced file size by removing .po files and directing anyone creating or editing local translations to translations.theeventscalendar.com
* Tweak - Clean up the layout styles for the ticket controls on small-medium screens [127193]
* Tweak - Ensure that "My Events" page defaults to sort by start date. Add `tribe_ce_my_events_query_orderby` and `tribe_ce_my_events_query_order` filters. Added comment blocks to all filters in the `doMyEvents` function [126393]
* Tweak - Added filters: `tribe_ce_my_events_query_orderby`, `tribe_ce_my_events_query_order`, `tribe_events_community_list_page_template_include`, `tribe_events_community_submission_url`, `tribe_events_community_shortcode_nav_link`, `tribe_community_events_list_columns_blocked`, `tribe_events_community_add_event_label`, `tribe_community_events_list_display_button_text`
* Tweak - Removed filters: `tribe_events_community_stylesheet_url` as it never worked correctly [125096]
* Tweak - Added actions: `tribe_ce_event_submission_login_form`, `tribe_events_community_before_shortcode`, `tribe_community_events_shortcode_before_list_navigation`, `tribe_community_events_shortcode_after_list_navigation_buttons`, `tribe_community_events_shortcode_before_list_table`, `tribe_community_events_shortcode_after_list_table`
* Tweak - Changed views: `community/event-list-shortcode`, `community/event-list`, `community/modules/delete`, `community/modules/submit`
* Fix - Add styles to prevent recurrence controls from showing on small screens until they are triggered [127193]
* Fix - Changed the text for the submit button to "Update", in the Community Events edit submission screen [123363]
* Fix - Move from deprecated hook to ensure license key fields show up properly in settings [125258]
* Fix - Move creation of `Tribe__Events__Community__Anonymous_Users` to `init` to be sure we have an authenticated user [124619]
* Fix - Correct our implementation of custom styles so that they are applied in addition to our base styles, rather than instead of them [125096]
* Fix - dependency-checker now correctly identifies missing TEC on activation/deactivation of TEC [122638]
* Language - 13 new strings added, 96 updated, 2 fuzzied, and 9 obsoleted

### [4.6.1.2] 2019-04-04

* Fix - Ensure Community Events filter does not remove the Attendee bulk actions in the admin area [124783]

### [4.6.1.1] 2019-03-06

* Fix - Ensure Community Events filter does not remove the Attendee bulk actions in the admin area [123608]

### [4.6.1] 2019-02-26

* Fix - allow event creators to check in attendees via the FE attendee list [118675]
* Language - 0 new strings added, 64 updated, 0 fuzzied, and 0 obsoleted

### [4.6] 2019-02-05

* Feature - Add check and enforce PHP 5.6 as the minimum version [116283]
* Feature - Add system to check plugin versions to inform you to update and prevent site breaking errors [116841]
* Tweak - Ensure we load the text domain before loading activation messages [104746]
* Tweak - Change whitespace positions for better and more consistent translations [120964]
* Tweak - Update plugin header [90398]
* Tweak - Added filters: `tribe_not_php_version_names`
* Deprecated - The function ` Tribe_CE_Load()` and constant `REQUIRED_TEC_VERSION`, `init_addon()` method has been deprecated in `Tribe__Events__Community__Main` in favor of Plugin Dependency Checking system

### [4.5.16] 2019-01-15

* Tweak - Scroll to top of form if submitted with errors [108095]
* Tweak - Improve the default styling for the list view on smaller screens [119166]
* Tweak - Updated view for e-mail template [119145]
* Tweak - Changed views: `community/email-template`
* Fix - Ensure featured image is not submitted if removed prior to submit [119247]
* Fix - Correct blank url in review submission email for anonymously submitted events [119145]
* Language - 7 new strings added, 106 updated, 0 fuzzied, and 1 obsoleted

### [4.5.15] 2018-12-05

* Feature - Added a new action, `tribe_community_events_before_form_messages`, to allow easier addition of content before various form messages are displayed [118438]
* Fix - Ensure images can be removed from events after uploading them--on both the submission form *and* the edit form for already-submitted events [104450]
* Fix - Ensure that fields required for Organizers work on both the submission form *and* the edit-Organizer form [110203]
* Fix - Prevent event-status tooltips from being cut off in the "My Events" list on the front end [116621]

### [4.5.14] 2018-11-13

* Feature - The email alert now displays a list with all organizers. The venue and the Organizers are now linked to their respective edit pages. New action hooks added before and after the email template. The email now indicates if an event is recurring [110657]
* Tweak - Allow the HTML `<img>` tag in the Community Events submission form [111539]
* Tweak - Add a "Lost password?" URL to Community Events login forms so that users can reset their passwords, just like they can via other WordPress Core login forms (thanks to Karen White for suggesting this!) [105952]
* Fix - Ensure that the form won't submit if new Venues or Organizers are being created on the form but the form's missing the required Event Title and Event Description fields [116196]
* Fix - The 'Show Google Map' and 'Show Google Maps Link' fields are now disabled when the `tribe_events_community_apply_map_defaults` filter is being used [97842]

### [4.5.13.1] 2018-08-27

* Fix - Don't dequeue `tribe-events-calendar-script` as it has too many dependents ( props @focusphoto, @tatel, @lindsayhanna17, and many others who reported this) [113083]

### [4.5.13] 2018-08-01

* Tweak - Manage plugin assets via `tribe_assets()` [40267]
* Tweak - Added new `tribe_events_community_form_before_linked_posts` and `tribe_events_community_form_after_linked_posts` action hooks within the Edit Event form to enhance customizability [109448]

### [4.5.12] 2018-05-29

* Tweak - Updated views: `src/views/community/modules/image.php`
* Fix - Added method with `tribe_community_events_max_file_size_allowed` filter to set the max file size allowed [61354]
* Language - 0 new strings added, 8 updated, 0 fuzzied, and 0 obsoleted

### [4.5.11] 2018-04-18

* Tweak - Fixed some misalignment of buttons above Community Events' front-end "My Events" list in the Twenty Seventeen theme [99846]
* Fix - Prevent multiple notification emails from being sent every time an already-submitted event is edited (thanks to @proactivedesign in the forums for flagging this bug!) [99244]
* Fix - Fixed JavaScript error with datepickers on small viewport sizes [98861]

### [4.5.10] 2018-03-28

* Feature - Added updater class to enable changes on future updates [84675]
* Fix - Prevented errors under PHP 7.2 in relation to the use of `create_function` [100037]
* Fix - Restored the ability of community organizers to email the attendee list, even if they are blocked from accessing the admin environment (thanks to mindaji in our forums for reporting this) [99979]

### [4.5.9] 2018-02-14

* Fix - Prevent the loss of event start date, end date, start time, end time, "all day" event, and timezone choices upon failed event submission (thanks to @netdesign and many others for highlighting this issue) [94010]
* Fix - Fixed additional fields from Events Calendar PRO on the event-submission form so that their values are saved upon a failed form submission (thanks @myrunningresource for flagging this bug!) [94908]
* Tweak - Fixed misalignment of "Display Options" button in the front-end "My Events" list [93521]
* Tweak - Adjust the edit-organizer and edit-venue form styles to make both forms more readable (props @artcantina for highlighting these issues) [95043]

### [4.5.8] 2017-11-21

* Tweak - Only display admin links in Community Tickets if user is able to access the admin [79565]
* Language - 0 new strings added, 11 updated, 0 fuzzied, and 0 obsoleted

### [4.5.7] 2017-11-16

* Fix - Improved translatability of the taxonomy dropdowns ("Searching..." placeholder can now be translated - our thanks to Oliver for flagging this!) [84926]
* Fix - Changed the attendee and other links exposed to event owners so that they stay in the frontend environment where possible (our thanks to Gurdeep Sandhu for flagging this problem) [89015]
* Fix - Added logic to prevent an event end time earlier than the event start time being set [89825]
* Fix - Enhanced ease of marking nested fields as required (our thanks to dsb cloud services GmbH & Co. KG for flagging the need for this) [86299]
* Fix - Ensure the correct wording is used for the Edit Venue and Edit Organizer pages [90154]
* Tweak - The options presented by the timezone picker are now filterable (when used alongside an up-to-date version of The Events Calendar) [92909]
* Language - 5 new strings added, 59 updated, 0 fuzzied, and 0 obsoleted

### [4.5.6] 2017-10-04

* Fix - Fixed issues with the jQuery Timepicker vendor script conflicting with other plugins' similar scripts (props: @hcny et al.) [74644]
* Fix - Fixed the creation of the "back" (to the events list) URL, so that translated slugs are used (our thanks to dezemberundjuli for flagging this) [85607]

### [4.5.5] 2017-08-24

* Fix - Set the Show Map and Show Map Link fields to true by default, restoring the behavior from past versions [84438]
* Tweak - Fixed some typos that would sometimes show up in the Organizer selection fields [84482]
* Tweak - Added filter: `tribe_events_community_apply_map_defaults` for filtering venue map fields
* Language - 0 new strings added, 28 updated, 0 fuzzied, and 0 obsoleted [tribe-events-community]

### [4.5.4] 2017-08-09

* Fix - Fix a conflict with the WP Edit plugin to ensure its "disable wpautop" option does not break the Community Events submission form (our thanks to Karen for highlighting this) [73898]
* Fix - Hide delete button for event recurrence rules [80491]
* Fix - Prevent a notice level error occuring when the edit page is accessed while the Divi theme is in use [72700]
* Fix - Add some responsive styling to account for a wide table on the My List page (our thanks to Iwan for flagging this problem) [79635]
* Fix - Added support for venue meta fields to front-end venue editor (thanks to Mario for flagging this issue) [77260]
* Fix - Fixed bug that erroneously showed the fields for a new linked post when there was an submission error [80389]
* Tweak - Enhance submission form labels for taxonomy fields so they use the real taxonomy name, not "categories" generically (our thanks to Hans-Gerd for bringing our attention to this issue) [80542]
* Tweak - Merged date format settings with The Events Calendar, can now be found in WP Admin > Settings > Display [44911]
* Tweak - Add some UI to the "Timezone" selection field on the submission form to help clarify that it can be edited [80423]
* Tweak - Added helper text to Admin settings clarifying that subscribers can only edit/remove their own submissions [77260]
* Tweak - Added styling for themes like Genesis that put too much padding in the datepicker [79636]
* Tweak - Enhance submission form labels for taxonomy fields so they use the real taxonomy name, not "categories" generically [80542]
* Compatibility - Minimum supported version of WordPress is now 4.5
* Language - 4 new strings added, 119 updated, 0 fuzzied, and 3 obsoleted

### [4.5.3] 2017-07-26

* Fix - Stop adjustments to ReCaptcha settings from impacting other Community Events settings and vice versa in a multisite context [79728]
* Tweak - Remove case sensitivty when specifying custom "required fields" via the tribe_events_community_required_fields filter [76297]
* Tweak - add filter to show event cost if filled in from the front end even if ticketing plugins active [80215]

### [4.5.2] 2017-06-28

* Fix - Improved handling of Venue fields that allows for better form validation in Community Events [76297]
* Fix - Ensure the "Users cannot create new Venues|Organizers" setting is respected [80487]
* Fix - Ensure the tribe-no-js class is removed when appropriate. [79335]
* Tweak - Do not render the venue or organizer template modules if the current user can neither select nor create those posts [80487]

### [4.5.1] 2017-06-14

* Fix - Preserve 'Event Options' when the Community form is submitted [72055]

### [4.5] 2017-06-06

* Feature - Post tags to the Community Events Editor [35822]
* Feature - Completely revamp the HTML and CSS for the Community "My Events" and "Events Editor" [76968]
* Feature - Increase the customization hook for all Community Event templates [76968]
* Feature - Improve user experience for featured image uploading on "Events Editor" [76948]
* Fix - Display of checkboxes in the additional field section to be one per line [74002]
* Tweak - Modify Categories user experience on the Community event editor [77125]
* Tweak - Adding community events options to sysinfo data available viewable in Events > Help [38730]
* Tweak - Event Editor now has a better Mobile CSS [77189]
* Tweak - Removed Class `Tribe__Events__Community__Modules__Taxonomy_Block`
* Tweak - Added Template tag: `tribe_community_events_list_columns`, `tribe_community_events_prev_next_nav`
* Tweak - Added filters: `tribe_community_events_allowed_taxonomies`, `tribe_community_events_list_columns`, `tribe_community_events_list_columns_blocked`, `tribe_community_events_add_event_label`, `tribe_community_events_list_display_button_text`, `tribe_events_community_custom_field_value`, `tribe_community_event_edit_button_label`
* Tweak - Removed filters: `tribe_community_events_form_spam_control`, `tribe_events_community_category_dropdown_shown_item_count`, `tribe_ce_event_update_button_text`, `tribe_ce_event_submit_button_text`, `tribe_ce_add_event_button_text`, `tribe_ce_event_list_display_button_text`, `tribe_community_custom_field_value`
* Tweak - Added actions: `tribe_community_events_before_list_navigation`, `tribe_community_events_after_list_navigation_buttons`, `tribe_community_events_before_list_table`, `tribe_community_events_after_list_table`, `tribe_events_community_section_before_captcha`, `tribe_events_community_section_after_captcha`, `tribe_events_community_section_before_cost`, `tribe_events_community_section_after_cost`, `tribe_events_community_section_before_custom_fields`, `tribe_events_community_section_after_custom_fields`, `tribe_events_community_section_before_datetime`, `tribe_events_community_section_after_datetime`, `tribe_events_community_section_before_description`, `tribe_events_community_section_after_description`, `tribe_events_community_section_before_featured_image`, `tribe_events_community_section_after_featured_image`, `tribe_events_community_section_before_organizer`, `tribe_events_community_section_after_organizer`, `tribe_events_community_section_before_honeypot`, `tribe_events_community_section_after_honeypot`, `tribe_events_community_section_before_submit`, `tribe_events_community_section_after_submit`, `tribe_events_community_section_before_taxonomy`, `tribe_events_community_section_after_taxonomy`, `tribe_events_community_section_before_title`, `tribe_events_community_section_after_title`, `tribe_events_community_section_before_venue`, `tribe_events_community_section_after_venue`, `tribe_events_community_section_before_website`, `tribe_events_community_section_after_website`
* Tweak - Removed actions: `tribe_events_community_before_the_event_title`, `tribe_events_community_after_the_event_title`, `tribe_events_community_before_the_content`, `tribe_events_community_after_the_content`, `tribe_events_community_before_form_submit`, `tribe_events_community_after_form_submit`, `tribe_ce_before_event_list_top_buttons`, `tribe_ce_after_event_list_top_buttons`, `tribe_ce_before_event_list_table`, `tribe_ce_after_event_list_table`, `tribe_events_community_before_the_captcha`, `tribe_events_community_after_the_captcha`, `tribe_events_community_before_the_cost`, `tribe_events_community_after_the_cost`, `tribe_events_community_before_the_datepickers`, `tribe_events_community_after_the_datepickers`, `tribe_events_community_before_the_featured_image`, `tribe_events_community_after_the_featured_image`, `tribe_events_community_before_the_categories`, `tribe_events_community_after_the_categories`, `tribe_events_community_before_the_website`, `tribe_events_community_after_the_website`
* Language - 17 new strings added, 134 updated, 0 fuzzied, and 13 obsoleted [events-community]

### [4.4.7] 2017-06-01

* Fix - Fixed the display of the submission form to be more consistent in different page templates in default themes [75545]
* Tweak - Added new hooks: 'tribe_community_before_login_form' and 'tribe_community_after_login_form' [67510]

### [4.4.6] 2017-05-17

* Tweak - Further adjustments made to our plugin licensing system [78506]

### [4.4.5] 2017-05-04

* Fix - Made timepicker compatible with 24hr time format. [72674]
* Fix - Fix a fatal error introduced in our last release, relating to venues being submitted for events (thanks to @artsgso for flagging this!) [77650]
* Tweak - adjustments made to our plugin licensing system

### [4.4.4] 2017-04-19

* Fix - Improvements to the submission scrubber to improve safety and avoid conflicts with other plugins (props: @georgestephanis, @cliffordp) [72412]

### [4.4.3] 2017-03-23

* Fix - Ensure the Google Map settings for submitted/edited events are saved as expected (Thanks @Werner for the report in our support forums) [72124]

### [4.4.2] 2017-02-09

* Fix - Fix a bug that caused the z-index in .min vendor CSS file to be different than the non-minified file.(thanks to @Nicholas) [72603]
* Fix - Fixed untranslated strings within the frontend submission form [72576]

### [4.4.1] 2017-01-26

* Fix - Prevent Fatals from happening due to Missing classes Di52 implementaton [71943]
* Fix - Comments showing again for Posts and Pages [71943]
* Fix - Corrected a closing div tag position in the modules/cost template[72204]

### [4.4] 2017-01-09

* Feature - Design refresh for the front end submission form. [68498]
* Feature - The front end submission form now satisfies requirements for WCAG 2.0 Level AA. [69553]
* Fix - Ensure the submission form retains expected styles when default permalinks are in use [32409]
* Tweak - Made customization of the start and end date fields easier [32412]
* Tweak - Moved reCAPTCHA API key fields to new API Settings Tab [62031]
* Tweak - Organizers and Venues now have a better and cleaner interface [68430, 38129]
* Tweak - Adjustments to recurring event support in order to match changes made in Events Calendar PRO [66717]

### [4.3.2] 2016-12-20

* Tweak - Updated the template override instructions in a number of templates [68229]

### [4.3.1] 2016-10-20

* Tweak - Added plugin dir and file path constants.
* Tweak - Registered plugin as active with Tribe Common. [66657]

### [4.3] 2016-10-13

* Add - Add Community Events links for the add and list pages into the system information [41136]
* Add - Better styling and datepicker support for Community Events pages embedded via the [tribe_community_events] shortcode [32409]
* Tweak - Adjust helper text for redirect URL setting [28029]

### [4.2.5] 2016-09-15

* Fix - Ensure sample URLs for the /add/ and /list/ pages provided in the settings page match those currently in use (our heartfelt thanks to Asko in the forums for highlighting this discrepancy)
* Fix - Improve interaction with reCaptcha service and avoid errors when handling the result (our thanks to Christine in the forums for highlighting this problem)

### [4.2.4] 2016-08-17

* Fix - Front-end event edit form not displaying an event assigned categories [62547] (Thank you @indycourses for the report in the support forums.)
* Fix - Improve organizer and venue validation and add in two filters to validate individual fields for their respective linked posts [63949]

### [4.2.3] 2016-07-20

* Fix - Always Show Google Map and Link checkbox when editing an event [Thanks to @groeteke for reporting this on our forums.]
* Fix - Enable logged in users same access to community events form when anonymous submissions disabled [Emily of @designagency took the time to report this one. Thanks Emily.]

### [4.2.2] 2016-07-06

* Fix - Event before and after HTML content appearing two times when when listing events
* Fix - Fill in venue fields when editing it on the front end [62685]

### [4.2.1] 2016-06-22

* Fix - Adjust layout for List View in Twenty Sixteen

### [4.2] 2016-06-08

* Tweak - Language files in the `wp-content/languages/plugins` path will be loaded before attempting to load internal language files (Thank you to user @aafhhl for bringing this to our attention!)
* Tweak - Move plugin CSS to PostCSS
* Tweak - Adjusted text directing people to the new user primer
* Tweak - Updated venue and organizer templates to use the new architecture for attaching custom post types to events

### [4.1.2] 2016-05-19

* Fix - Make the fields within the organizer section of the event submission form sticky

### [4.1.1] 2016-03-30

* Fix - Allow the organizer metabox on the community add form to be overridden by themers via the new template: community/modules/organizer-multiple.php (Props to Mad Dog for reporting this issue)
* Fix - Resolved issue where the "Before HTML" content was sometimes duplicated on community pages (props to Brent for reporting this!)
* Fix - Removed whitespace to fix translation of submitted events (Props to Oliver for this report)
* Fix - Resolved various capitalization issues with German translations (Props to Oliver for reporting this issue as well)

### [4.1] 2016-03-15

* Feature - Added filter for changing the number of categories to display on the event add form: tribe_events_community_category_dropdown_shown_item_count
* Feature - Added a checkbox to remove the country, state/province, and timezone selectors from the event add form
* Fix - Fixed bug that prevented logged in users from submitting new venues and organizers when anonymous submissions were enabled

### [4.0.6] 2016-03-02

* Fix - Category is now inserted when uploading a featured image
* Fix - Errors on the public submission no longer reset the event date

### [4.0.5] 2016-02-17

* Fix - Prevent information on the confirmation Email to be related to the featured Image
* Tweak - Only allow valid URLs on Events Pro custom fields when community events are submitted

### [4.0.4] 2016-01-15

* Security - Security fix with front end submissions (props to grandayjames for reporting this!)

### [4.0.3] 2015-12-22

* Tweak - Include Admin edit link on the notification email sent when a new Event is submitted (Thank you Judy for reporting this!)
* Fix - Prevents notices from happening on the Add New Event page

### [4.0.2] 2015-12-16

* Tweak - Ignore default venue values to reduce the amount of duplicate venues generated by community organizers (Thank you Carly!)

### [4.0.1] 2015-12-10

* Tweak - Respect venue and organizer post type permissions when providing add/edit fields to a user
* Tweak - Added better support for creating organizers as an anonymous user
* Fix - Fields with multiple values now are kept if you get an error on the Community Submit page
* Fix - Resolved issue where creating new organizers and venues was failing for anonymous users
* Fix - Resolved bug where community event submissions that resulted in an error would cause some fields to be cleared out

### [4.0] 2015-12-02

* Feature - Added new Filter on Community Events related pages (`tribe_ce_i18n_page_titles`) (Thank you Mad Dog!)
* Tweak - Add support for wp_get_document_title in response to the WordPress 4.4 deprecation of wp_title
* Tweak - Output the "Advanced Template Settings" custom HTML before and after the event list and event add form (Thank you Benjamin for the heads up!)
* Fix - My Events ordered now reverse chronologically, as it was intended (earlier first)
* Fix - Better CSS for when My Events page Navigation has multiple pages
* Fix - Make some strings translatable that were not (Props to Oliver for bringing it to our attention)
* Fix - Resolved an issue where translations sometimes failed to load (Thanks for the report and the fix Murat!)

### [3.12.1] 2015-11-04

* Feature - Added support for the new Events Community Tickets plugin

### [3.12] 2015-09-08

* Security - Resolved JS vulnerability in minified JS by upgrading to uglifyjs 2.4.24
* Feature - Added support for Events PRO's Arbitrary Recurrence for events in the event submission form
* Feature - Added none option for both Radio and Dropdown Additional Fields (Thanks to Justin on the forums!)
* Feature - Modified timezone handling to take advantage of new capabilities within The Events Calendar
* Tweak - Added currency position field to the event submission form
* Tweak - Submitting a featured image that is too large will now generate an error
* Tweak - Relocated the ReCaptcha class to avoid conflicts with other ReCaptcha enabled plugins (Props to ryandc for the original report!)
* Tweak - Disable the organizer email obfuscation message on the Community Add form (Thank you cliffy for bringing this to our attention!)
* Tweak - Default Country being respected without locking the user options
* Fix - Resolved bug that prevented organizers from being identified as present in the submitted form when they were set as required fields (That you Rob for the report!)
* Fix - Fixed an issue with the admin bar showing for user roles that were blocked from admin
* Fix - Fixed an issue with additional fields not showing as selected when a symbol is included in the label (Props to Justin!)
* Fix - Fixed issue where the start and end dates for events were defaulted to the current hour on the Community Add form rather than the defaults used in the dashboard

### [3.11] 2015-07-22

* Security - Added escaping to a number of previously un-escaped values
* Feature - Event Categories that contain a hierarchy will now display in a hierarchical format when creating/editing events (Thank you Christian K for suggesting this on UserVoice!)
* Tweak - Switched the "Back" link that appears after deleting a Community Event to an actual URL rather than a JS history call (Thanks to Pablo for reporting this!)
* Tweak - Conformed code to updated coding standards
* Tweak - Changed priority on the 'parse_request' hooked method for compatibility with Shopp
* Fix - Fixed an issue where the Community Events UI was tucked under the sidebar in the TwentyFourteen theme
* Fix - Removed double-wrapped paragraph tags in error messages (Props to operapreneur for finding this!)
* Fix - Resolved an issue where localizable URL parts were not getting localized (Thank you kiralybalazs for the heads up!)
* Fix - Fixed some display issues with the community submission form in TwentyFifteen
* Fix - Resolved some notices about undefined variables

### [3.10] 2015-06-16

* Fix - Ensured all recurrence fields are required when a recurring event is submitted (thanks to sean on the forums for the report!)
* Fix - Fixed an issue causing the submission form datepicker fonts to be huge in the 2014 theme
* Fix - Fixed an issue causing the events-per-page setting to be ignored
* Fix - Fixed an issue where the Google Maps Link and Venue URL values were not correctly displaying on the edit form (thanks to pasada on the PRO forums for the report!)
* Fix - Fixed an issue where Venue, Organizer, and Website values were not preserved after a validation error
* Tweak - Plugin code has been refactored to new standards: that did result in a new file structure and many renamed classes. Old class names will be deprecated in future releases and, while still working as expected, you can keep track of any deprecated classes yours or third party plugins are calling using the Log Deprecated Notices plugin (https://wordpress.org/plugins/log-deprecated-notices/)
* Tweak - Improved messaging shown to customers when they upload an image exceeding the max permitted size
* Tweak - Improved the admin access controls so that an unauthenticated user visiting wp-admin is taken to wp-login.php
* Tweak - Added some changelog formatting enhancements after seeing keepachangelog.com :)
* Tweak - Improved compatibility with The Events Calendar 3.10 default values
* Feature - Incorporated updated Finish translation files, courtesy of Ari-Pekka Koponen
* Feature - Incorporated updated German translation files, courtesy of Oliver Heinrich
* Feature - Incorporated updated French translation files, courtesy of Sylvain Delisle
* Feature - Incorporated new Bulgarian translation files, courtesy of Nedko Ivanov
* Feature - Incorporated new Swedish translation files, courtesy of Johan Falk

### [3.9.1] 2015-03-21

* Fix - Hardened URL output to protect against XSS attacks.

### [3.9] 2014-12-08

* Feature - Added spam filtering based on reCaptcha (thanks to Scott Fennell for setting the initial framework on this!)
* Language - Incorporated updated German translation files, courtesy of Oliver Heinrich
* Language - Incorporated new Russian translation files, courtesy of Evgenii Rybak

### [3.8.2] 2014-10-22

* Fix - Fixed an issue with URL parsing that could cause a nasty unexpected deletion of most/all events in some situations (thanks to kiralybalazs in the forum for reporting this!)

### [3.8.1] 2014-10-14

* Fix - Ensured that categories are saved from community-submitted events under certain settings combinations (thanks to presis on the forums for the report!)
* Fix - Removed the dependency on Events Calendar PRO for community default content settings

### [3.8] 2014-09-29

* Fix - Fixed an issue with shortcodes pre-populating in the form (thanks to jhatzi for the report!)
* Fix - Fix a bug causing post status of submitted events to always revert to draft (thanks to bodin on the forums for the first report here!)
* Fix - Added a venue website/URL field to the frontend submission form (thanks to persyst on the forums for bringing this up!)
* Fix - Improved support and detection of 24hr time formats to those which include hours without a leading zero
* Fix - Default values for the community submission form no longer depend on defaults enabled in PRO
* Language - Incorporated new Portuguese translation files, courtesy of Sérgio Leite
* Language - Incorporated updated Italian translation files, courtesy of Gabriele Taffi
* Language - Incorporated updated German translation files, courtesy of Oliver Heinrich
* Language - Incorporated updated Finnish translation files, courtesy of Elias Okkonen

### [3.7] 2014-07-28

* Language - Incorporated new Chinese translation files, courtesy of Massound Huang
* Language - Incorporated new Indonesian translation files, courtesy of Didik Priyanto
* Language - Incorporated updated Spanish translation files, courtesy of Juanjo Navarro
* Fix - Corrected an issue where submitted events were losing categories and metadata (Thanks to immeemz on the  forums for reporting this)
* Fix - Improved the effects of the `tribe_events_community_required_fields` filter for marking required fields (Thank you to Chris for the idea!)
* Fix - Fixed duplicate HTML ID in submission templates (Thank you integrity for bringing this to our attention)
* Fix - Fixed a bug where a user could view drafts events they did not have permission to see
* Fix - Changed email notification markup to a template for customization (Thank you hackauf for bringing this up)
* Fix - Added better error handling for submitting images with invalid file types
* Fix - Added the ability to override the labels & slugs for venues and organizers
* Fix - Renamed files and classes to be inline with official naming scheme

### [3.6.1] 2014-05-30

* Fix - Fix minification bug.

### [3.6] 2014-05-27

* Fix - Fixed editing of recurring events when Pro is active
* Feature - Added a "Delete All" option for recurring events
* Language - Incorporated new Ukranian translation files, courtesy of Vasily Vishnyakov
* Language - Incorporated updated German translation files, courtesy of Dennis Gruebner

### [3.5] 2014-03-27

* Fix - Fixed handling of user roles blocked from admin for superadmins on multisite
* Fix - Fixed an issue where borders weren't displaying properly on the WYSIWYG editor (thanks to memeco on the forums for his report here!)
* Fix - Fixed inconsistencies in the event submission form when PRO and Community have different default venues or organizers
* Fix - Updated sanitization filters to allow shortcodes in event descriptions by default (thanks to elmalak on the forum for reporting this!)
* Fix - Fixed broken templates when editing venues and organizers while using the default events template
* Fix - Fixed a variety of untranslatable strings
* Language - Incorporated updated Romanian translation files, courtesy of Cosmin Vaman
* Language - Added updated German translation files, courtesy of Oliver Heinrich
* Language - Added updated Brazilian Portuguese translation files, courtesy of by Emerson Marques
* Language - Incorporated updated Dutch translation files, courtesy of J.F.M. Cornelissen
* Language - Incorporated updated Spanish translation files, courtesy of Juan Jose Reparaz Sarasola

### [3.4] 2014-01-23

* Feature - Added a "View Submitted Event" link that appears after a submission has gone through
* Fix - Addressed an issue where the datepicker would not honor the core WordPress (thanks to lamagia on the forums for the report!)
* Fix - Fixed a bug for PRO users where the custom venue and organizer configured in PRO would remain even after that plugin was deactivated

### [3.3] 2013-12-18

* Feature - Community now uses the same events template setting as core plugin views
* Feature - Default Events Template can now be chosen to display the submission form etc (not previously allowed)
* Feature - User-submitted data is more thoroughly scrubbed for malicious data
* Language - Incorporated updated German translation files, courtesy of Oliver Heinrich
* Language - Incorporated updated French translation files, courtesy of Bastien BC

### [3.2] 2013-11-05

* Fix - Fixed a bug where recurring event instances were not visible on the "My Events" list under certain settings
* Fix - Fixed a handful of minor PHP notices
* Tweak - Added a minor improvement to recurrence settings fieldset display
* Fix - Fixed a bug where the datepicker was huge in some themes
* Fix - Template overrides for Community Events in your theme should now all be inside the [your-theme]/tribe-events/community directory; a deprecated notice will be generated if they are directly in the [your-theme]/tribe-events folder
* Language - Incorporated updated French translation files, courtesy of Ali Senhaji

### [3.1] 2013-09-30

* Fix - Improved behavior of recurring events deletion from My Events list
* Fix - Cannot reach the community list page
* Fix - Fixed bug where new venues submitted via Community weren't being published along with their event
* Tweak - Community now uses the specified Events template under Settings > Display
* Fix - Improved spam prevention technique (honeypot) implemented on the Community submission form
* Fix - Community submission form now respects default venue setting and hides the other venue fields (address, etc.)
* Fix - Community submission form now respects default content fields
* Fix - Event Website URL field is no longer missing from the Community submission form
* Fix - Styles are no longer stripped from Community submissions
* Fix - Fixed bug where the saved venues dropdown wasn't displaying on the Community submit form
* Fix - New Venues and Organizers no longer overwrite existing ones when editing an event
* Fix - Fixed bug where submit form wasn't working properly for anonymous users in some cases
* Fix - Users can now always view their My Events listing
* Fix - Users will no longer be redirected to wp-login.php upon logout, if they do not have dashboard access
* Language - Updated translations: Romanian (new), Finnish (new)
* Fix - Various minor bug and security fixes

### [3.0.1] 2013-07-23

* Performance - Performance improvements to the plugin update engine
* Language - Fixed two strings that weren't being translated in the admin bar menu

### [3.0] 2013-07-03

* Version - Updated version number to 3.0.x for plugin version consistency

### [1.0.7] 2013-07-02

* Fix - Fix plugin update system on multisite installations

### [1.0.6] 2013-06-26

* Tweak - Code modifications to ensure compatibility with The Events Calendar/Events Calendar PRO 3.0.
* Language - Incorporated new Norwegian translation files, courtesy of Eyolf Steffensen.
* Language - Incorporated new Polish translation files, courtesy of Lukasz Kruszewski-Zelman.
* Language - Incorporated new Swedish translation files, courtesy of Ben Andersen.
* Language - Incorporated new Croatian translation files, courtesy of Jasmina Kovacevic.
* Fix - Addressed a vulnerability where certain shortcodes can be used to exploit  sites running older versions of Community Events.
* Fix - Custom field values are no longer wiped after submitting when failing to check the anti-spam checkbox.
* Fix - Frontend community form now loads properly in WordPress 3.5 environments.
* Fix - Reinforced capabilities blocking unwanted users from the site admin.
* Fix - Users lacking organizer/venue edit permissions now see an appropriate error message.
* Fix - By removing the Next Event widget from Events Calendar PRO (see 3.0 release notes), we've eliminated the problem where Community and the Next Event widget conflicted when placed together on a page.
* Fix - Addressed a warning ("Creating default object from empty value") that impacted certain users.
* Fix - Corrected untranslatable elements in event-form.php.
* Fix - Addressed a bug causing labels to appear below fields on the frontend submit form for certain users.
* Fix - Redirect URLS (as configured under Events -> Settings -> Community) now function as expected.
* Fix - Removed various styling problems on the Twenty Twelve theme in WP 3.5.

### [1.0.5] 2013-01-21

* Fix - Various bug fixes.

### [1.0.4] 2012-11-12

* Language - Incorporated updated German translation files, courtesy of Marc Galliath.
* Fix - Fixed a bug that led to a fatal error in the WordPress 3.5 beta 2.
* Fix - Removed an illegal HTML style tag from the frontend Community form.

### [1.0.3] 2012-09-11

* Tweak - Clarified messaging regarding pre-populated "Free" text on cost field.
* Tweak - Disabled comments from the frontend submission form.
* Tweak - Added a filter -- 'tribe_community_events_event_categories' -- to allow users to filter the category list that appears on the frontend submission form.
* Tweak - Added a new hook -- $args = apply_filters( 'tribe_community_events_my_events_query', $args ); -- at a user's request. This alteration allows you to tap into the WotUser object and pull out a list of events the user has access to and add them into this query.
* Language - Incorporated new Dutch language files, courtesy of Jurgen Michiels.
* Language - Incorporated new French language files, courtesy of Vanessa Bianchi.
* Language - Incorporated new Italian language files, courtesy of Marco Infussi.
* Language - Incorporated new German language files, courtesy of Marc Galliath.
* Language - Incorporated new Czech language files, courtesy of Petr Bastan.
* Fix - Removed a duplicate name attribute from venue-meta-box.php.
* Fix - Categories now save on events submitted by subscriber-level members.
* Fix - Categories now save on events submitted by anonymous users.
* Fix - The default state selection as configured in Events Calendar PRO now appears (along with the country) on the frontend submission form.
* Fix - Subscriber-level users may now edit events when that option is enabled under Events --> Settings --> Community.
* Fix - Reconfigured the cost field to work for frontend submissions on sites running the Eventbrite Tickets add-on + Community Events.
* Fix - Removed any lingering redirects to the WP Router Placeholder Page.
* Fix - My Events filtering options no longer conflict with the calendar widget.
* Fix - Fixed a broken link in the message that appears when Community Events is activated without the core The Events Calendar.
* Fix - Removed code causing a division by zero error in tribe-community-events.class.php.
* Fix - Styles from Community-related pages (events-admin-ui.css) no longer load on non-Community pages.
* Language - Cleared up untranslatable language strings found in the 1.0.2 POT file.

### [1.0.2] 2012-07-16

* Fix - Removed unclear/confusing message warning message regarding the need for plugin consistency and added clearer warnings with appropriate links when plugins or add-ons are out date.

### [1.0.1] 2012-07-06

* Tweak - Removed the pagination range setting from the Community tab on Settings -> The Events Calendar.
* Tweak - Added body classes for both the community submit (tribe_community_submit) and list (tribe_community_list) pages.
* Language - Incorporated new Spanish translation files, courtesy of Hector at Signo Creativo.
* Language - Incorporated new German translation files, courtesy of Mark Galliath.
* Tweak - Added boolean template tags for tribe_is_community_my_events_page() and tribe_is_community_edit_event_page()
* Tweak - Added new "Events" admin bar menu with Community-specific options
* Fix - Rewrite rules are now being flushed when the allowAnonymousSubmissions setting is changed.
* Fix - Duplicate venues and organizers are no longer created with each new submission.
* Fix - Community no longer deactivates the Events Calendar PRO advanced post manager.
* Fix - Clarified messaging regarding the difference between trash/delete settings options.
* Fix - Header for status column is no longer missing in My Events.

### [1.0] 2012-07-06

* Feature - Initial release
