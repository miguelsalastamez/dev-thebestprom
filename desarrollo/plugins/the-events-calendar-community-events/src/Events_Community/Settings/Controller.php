<?php
/**
 * Controller that handles the Settings process.
 *
 * @since   5.0.0
 *
 * @package TEC\Events_Community\Submission
 */

namespace TEC\Events_Community\Settings;

use TEC\Common\Contracts\Provider\Controller as Controller_Base;


/**
 * Class Controller
 *
 * @since 5.0.0
 *
 * @package TEC\Events_Community\Settings
 */
class Controller extends Controller_Base {

	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail we have the boolean living on the method.
	 *
	 * @since 5.0.0
	 *
	 * @var bool
	 */
	protected bool $is_active = true;

	/**
	 * @inheritDoc
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->boot();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {
	}

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		$this->container->singleton( Default_Settings_Strategy::class, Default_Settings_Strategy::class );
	}
}
