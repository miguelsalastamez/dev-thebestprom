<?php
/**
 * Class Messages
 *
 * Handles storing and retrieving submission messages.
 *
 * @package TEC\Events_Community\Submission
 */

namespace TEC\Events_Community\Submission;

/**
 * Class Messages
 *
 * Handles storing and retrieving submission messages.
 *
 * @package TEC\Events_Community\Submission
 */
class Messages {
	/**
	 * Array to store messages.
	 *
	 * @var array
	 */
	protected array $messages = [];

	/**
	 * This method ensures that each message is unique by using an MD5 hash of the message
	 * text as the array key. This prevents duplicate messages from appearing.
	 *
	 * @since 5.0.0
	 *
	 * @param string $message The message to add.
	 * @param string $type The type of message (e.g., 'error', 'success', 'update').
	 */
	public function add_message( string $message, string $type = 'update' ): void {
		// md5 the message as the key so that duplicates do not appear.
		$this->messages[ md5( $message ) ] = (object) [
			'message' => $message,
			'type'    => $type,
		];
	}

	/**
	 * Retrieves all unique messages.
	 *
	 * @since 5.0.0
	 *
	 * @return array The array of unique messages.
	 */
	public function get_messages(): array {
		return array_values( $this->messages );
	}

	/**
	 * Clears all messages.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function clear_messages(): void {
		$this->messages = [];
	}

	/**
	 * Returns the singleton instance.
	 *
	 * @since 5.0.0
	 *
	 * @return Messages
	 */
	public static function get_instance(): Messages {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}
