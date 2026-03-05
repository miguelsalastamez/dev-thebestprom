<?php

namespace TEC\Events_Community\Integrations;

use TEC\Common\Integrations\Integration_Abstract as Common_Integration_Abstract;

/**
 * Class Integration_Abstract
 *
 * @since   5.0.0
 *
 * @link    https://docs.theeventscalendar.com/apis/integrations/including-new-integrations/
 *
 * @package TEC\Events_Community\Integrations
 */
abstract class Plugin_Integration_Abstract extends Common_Integration_Abstract {

	/**
	 * @inheritDoc
	 */
	public static function get_parent(): string {
		return 'events-community';
	}

	/**
	 * @inheritdoc
	 *
	 * We don't want the ability to disable these integrations via a hook.
	 */
	protected function filter_should_load( bool $value ): bool {
		return (bool) $value;
	}
}
