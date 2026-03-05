<?php
/**
 * Plugin Helper Functions
 *
 * Core utility functions for file handling, mapping, security, and data transformation.
 *
 * @package IGD
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit();

use IGD\Account;
use IGD\App;
use IGD\Files;
use IGD\Integration;
use IGD\Permissions;
use IGD\Shortcode;
use IGD\Zip;

/**
 * Get breadcrumb navigation for a folder.
 *
 * @param array $folder The folder data.
 *
 * @return array Array of breadcrumb items (id => name).
 */
function igd_get_breadcrumb( $folder ) {
	// Early return for empty folder
	if ( empty( $folder ) || ! is_array( $folder ) ) {
		return [];
	}

	$folder_id  = $folder['id'] ?? '';
	$account_id = $folder['accountId'] ?? '';

	// Fetch folder name if not set
	if ( ! isset( $folder['name'] ) && isset( $folder['id'] ) ) {
		$folder = App::instance( $account_id )->get_file_by_id( $folder_id );

		if ( ! $folder ) {
			return [];
		}
	}

	$items = [ $folder_id => $folder['name'] ?? '' ];

	// Return early for root-level folders
	$root_level_ids = [ 'root', 'computers', 'shared-drives', 'shared', 'starred' ];
	if ( in_array( $folder_id, $root_level_ids, true ) ) {
		return $items;
	}

	// Fetch parents if not set
	if ( ! isset( $folder['parents'] ) ) {
		$folder = App::instance( $account_id )->get_file_by_id( $folder_id );

		if ( ! $folder ) {
			return $items;
		}
	}

	// Process parent folders
	if ( ! empty( $folder['parents'] ) ) {
		if ( in_array( 'shared-drives', $folder['parents'], true ) ) {
			$items['shared-drives'] = __( 'Shared Drives', 'integrate-google-drive' );

			return array_reverse( $items );
		}

		$parent_id = $folder['parents'][0];
		$item      = App::instance( $account_id )->get_file_by_id( $parent_id );

		if ( $item ) {
			// Replace root folder ID with 'root'
			if ( Account::instance()->get_root_id( $account_id ) === $item['id'] ) {
				$item['id'] = 'root';
			}

			$items = array_merge( igd_get_breadcrumb( $item ), $items );
		}
	} elseif ( ! empty( $folder['shared'] ) ) {
		$items['shared'] = __( 'Shared with me', 'integrate-google-drive' );

		return array_reverse( $items );
	} elseif ( ! empty( $folder['computers'] ) ) {
		$items['computers'] = __( 'Computers', 'integrate-google-drive' );

		return array_reverse( $items );
	}

	return $items;
}

/**
 * Check if file is a directory.
 *
 * @param array $file File data to check.
 *
 * @return bool True if file is a directory, false otherwise.
 */
function igd_is_dir( $file ) {
	if ( ! isset( $file['type'] ) || ! is_string( $file['type'] ) ) {
		return false;
	}

	$folder_mime = 'application/vnd.google-apps.folder';

	// Check if file is a folder
	if ( $file['type'] === $folder_mime ) {
		return true;
	}

	// Check if shortcut targets a folder
	if ( ! empty( $file['shortcutDetails']['targetMimeType'] ) ) {
		return $file['shortcutDetails']['targetMimeType'] === $folder_mime;
	}

	return false;
}

/**
 * Check if file is a shortcut.
 *
 * @param string $type File MIME type.
 *
 * @return bool True if file is a shortcut, false otherwise.
 */
function igd_is_shortcut( $type ) {
	return 'application/vnd.google-apps.shortcut' === $type;
}

/**
 * Get files recursively from a folder.
 *
 * @param array $file File or folder data.
 * @param string $current_path Current path for file listing.
 * @param array $list Reference array to accumulate results.
 *
 * @return array Files list with folders, files, and total size.
 */
function igd_get_files_recursive(
	$file,
	$current_path = '',
	&$list = [
		'folders' => [],
		'files'   => [],
		'size'    => 0,
	]
) {
	// Validate input
	if ( ! is_array( $file ) || empty( $file['id'] ) ) {
		return $list;
	}

	if ( igd_is_dir( $file ) ) {
		// Process folder
		$folder_path       = $current_path . ( $file['name'] ?? 'Folder' ) . '/';
		$list['folders'][] = $folder_path;

		$account_id = $file['accountId'] ?? Account::instance()->get_active_account()['id'];

		// Fetch child files
		$data = App::instance( $account_id )->get_files( [ 'folder' => $file ] );

		if ( ! empty( $data['files'] ) && is_array( $data['files'] ) ) {
			foreach ( $data['files'] as $child_file ) {
				igd_get_files_recursive( $child_file, $folder_path, $list );
			}
		}
	} else {
		// Process file
		$file_path = $current_path . ( $file['name'] ?? 'File' );

		if ( empty( $file['webContentLink'] ) ) {
			$export_formats = igd_get_export_as( $file['type'] ?? '' );

			if ( ! empty( $export_formats ) ) {
				$format        = reset( $export_formats );
				$download_link = add_query_arg(
					[
						'mimeType' => $format['mimetype'],
						'alt'      => 'media',
					],
					'https://www.googleapis.com/drive/v3/files/' . esc_attr( $file['id'] ) . '/export'
				);
				$file_path     .= '.' . sanitize_file_name( $format['extension'] );
			} else {
				return $list;
			}
		} else {
			$download_link = esc_url( $file['webContentLink'] );
		}

		$file['downloadLink'] = $download_link;
		$file['path']         = $file_path;
		$list['files'][]      = $file;
		$list['size']         += intval( $file['size'] ?? 0 );
	}

	return $list;
}

/**
 * Map Google Drive file object to array format.
 *
 * Transforms Google Drive API file response into standardized array
 * with permissions, metadata, and capabilities.
 *
 * @param object $item Google Drive file object.
 * @param string $account_id Account ID for this file.
 *
 * @return array Formatted file data.
 */
function igd_file_map( $item, $account_id = null ) {
	// Validate input object
	if ( ! is_object( $item ) || ! method_exists( $item, 'getId' ) ) {
		return [];
	}

	// Get active account if not provided
	if ( empty( $account_id ) ) {
		$active_account = Account::instance()->get_active_account();
		$account_id     = $active_account['id'] ?? '';
	}

	// Build basic file data
	$file = [
		'id'                           => $item->getId(),
		'name'                         => $item->getName() ?? '',
		'type'                         => $item->getMimeType() ?? '',
		'size'                         => $item->getSize() ?? 0,
		'iconLink'                     => $item->getIconLink() ?? '',
		'thumbnailLink'                => $item->getThumbnailLink() ?? '',
		'webViewLink'                  => $item->getWebViewLink() ?? '',
		'webContentLink'               => $item->getWebContentLink() ?? '',
		'created'                      => $item->getCreatedTime() ?? '',
		'updated'                      => $item->getModifiedTime() ?? '',
		'description'                  => $item->getDescription() ?? '',
		'shared'                       => $item->getShared() ?? false,
		'sharedWithMeTime'             => $item->getSharedWithMeTime() ?? '',
		'extension'                    => $item->getFileExtension() ?? '',
		'resourceKey'                  => $item->getResourceKey() ?? '',
		'copyRequiresWriterPermission' => $item->getCopyRequiresWriterPermission() ?? false,
		'starred'                      => $item->getStarred() ?? false,
		'exportLinks'                  => $item->getExportLinks() ?? [],
		'accountId'                    => $account_id,
	];

	// Handle "My Drive" special case
	if ( 'My Drive' === $file['name'] ) {
		$file['id'] = 'root';
	}

	// Process parent folders
	$parents = $item->getParents() ?? [];
	if ( ! empty( $parents ) ) {
		$root_id = Account::instance()->get_root_id( $account_id );

		foreach ( $parents as $key => $parent ) {
			if ( $root_id === $parent ) {
				$parents[ $key ] = 'root';
			}
		}
	}
	$file['parents'] = $parents;

	// Set permissions and capabilities
	$file['permissions'] = igd_get_file_permissions( $item );

	// Set owner information
	$owners = $item->getOwners() ?? [];
	if ( ! empty( $owners ) && is_array( $owners ) && ! empty( $owners[0]['displayName'] ) ) {
		$file['owner'] = $owners[0]['displayName'];
	}

	// Get export formats
	$file['exportAs'] = igd_get_export_as( $file['type'] );

	// Handle shortcut details
	$shortcut_details = $item->getShortcutDetails();
	if ( ! empty( $shortcut_details ) ) {
		$file['shortcutDetails'] = [
			'targetId'       => $shortcut_details->getTargetId() ?? '',
			'targetMimeType' => $shortcut_details->getTargetMimeType() ?? '',
		];

		// Fetch and merge original file metadata
		$original_file = App::instance( $account_id )->get_file_by_id( $file['shortcutDetails']['targetId'] );
		if ( ! empty( $original_file ) && is_array( $original_file ) ) {
			$file['thumbnailLink'] = $original_file['thumbnailLink'] ?? $file['thumbnailLink'];
			$file['iconLink']      = $original_file['iconLink'] ?? $file['iconLink'];
			$file['extension']     = $original_file['extension'] ?? $file['extension'];
			$file['exportAs']      = $original_file['exportAs'] ?? $file['exportAs'];
		}
	}

	// Add media metadata for images and videos
	$image_metadata = $item->getImageMediaMetadata();
	$video_metadata = $item->getVideoMediaMetadata();

	if ( ! empty( $image_metadata ) ) {
		$file['metaData'] = [
			'width'  => $image_metadata->getWidth() ?? 0,
			'height' => $image_metadata->getHeight() ?? 0,
		];
	} elseif ( ! empty( $video_metadata ) ) {
		$file['metaData'] = [
			'width'    => $video_metadata->getWidth() ?? 0,
			'height'   => $video_metadata->getHeight() ?? 0,
			'duration' => $video_metadata->getDurationMillis() ?? 0,
		];
	}

	return $file;
}

/**
 * Get file permissions and capabilities.
 *
 * @param object $item Google Drive file object.
 *
 * @return array Permissions array.
 */
function igd_get_file_permissions( $item ) {
	// Default permissions
	$permissions = [
		'canPreview'                            => true,
		'canDownload'                           => ! empty( $item->getWebContentLink() ) || ! empty( $item->getExportLinks() ),
		'canEdit'                               => false,
		'canDelete'                             => $item->getOwnedByMe() ?? false,
		'canTrash'                              => $item->getOwnedByMe() ?? false,
		'canMove'                               => $item->getOwnedByMe() ?? false,
		'canRename'                             => $item->getOwnedByMe() ?? false,
		'canShare'                              => false,
		'copyRequiresWriterPermission'          => $item->getCopyRequiresWriterPermission() ?? false,
		'canChangeCopyRequiresWriterPermission' => true,
		'users'                                 => [],
	];

	// Get capabilities from API
	$capabilities = $item->getCapabilities();
	if ( ! empty( $capabilities ) ) {
		$permissions['canEdit']                               = $capabilities->getCanEdit() && igd_is_editable( $item->getMimeType() ?? '' );
		$permissions['canShare']                              = $capabilities->getCanShare() ?? false;
		$permissions['canRename']                             = $capabilities->getCanRename() ?? false;
		$permissions['canDelete']                             = $capabilities->getCanDelete() ?? false;
		$permissions['canTrash']                              = $capabilities->getCanTrash() ?? false;
		$permissions['canMove']                               = $capabilities->getCanMoveItemWithinDrive() ?? false;
		$permissions['canChangeCopyRequiresWriterPermission'] = $capabilities->getCanChangeCopyRequiresWriterPermission() ?? true;
	}

	// Get permission users
	$permissions_list = $item->getPermissions() ?? [];
	if ( ! empty( $permissions_list ) && is_array( $permissions_list ) ) {
		foreach ( $permissions_list as $permission ) {
			if ( method_exists( $permission, 'getId' ) ) {
				$permissions['users'][ $permission->getId() ] = [
					'type'   => $permission->getType() ?? '',
					'role'   => $permission->getRole() ?? '',
					'domain' => $permission->getDomain() ?? '',
				];
			}
		}
	}

	return $permissions;
}

/**
 * Map Google Shared Drive to file array format.
 *
 * @param object $drive Shared Drive object from API.
 * @param string $account_id Account ID.
 *
 * @return array Formatted drive data.
 */
function igd_drive_map( $drive, $account_id ) {
	// Validate inputs
	if ( ! is_object( $drive ) ) {
		return [];
	}

	if ( empty( $account_id ) ) {
		$active_account = Account::instance()->get_active_account();
		$account_id     = $active_account['id'] ?? '';
	}

	// Convert to simple object
	$drive = method_exists( $drive, 'toSimpleObject' ) ? $drive->toSimpleObject() : $drive;

	$file = [
		'id'            => $drive->id ?? '',
		'name'          => $drive->name ?? '',
		'iconLink'      => $drive->backgroundImageLink ?? '',
		'thumbnailLink' => $drive->backgroundImageLink ?? '',
		'created'       => $drive->createdTime ?? '',
		'updated'       => $drive->createdTime ?? '',
		'hidden'        => $drive->hidden ?? false,
		'shared-drives' => true,
		'accountId'     => $account_id,
		'type'          => 'application/vnd.google-apps.folder',
		'parents'       => [ 'shared-drives' ],
		'permissions'   => $drive->capabilities ?? [],
	];

	return $file;
}

/**
 * Check if file type is editable.
 *
 * @param string $type File MIME type.
 *
 * @return bool True if file type can be edited.
 */
function igd_is_editable( $type ) {
	$editable_types = [
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.google-apps.document',
		'application/vnd.ms-excel',
		'application/vnd.ms-excel.sheet.macroenabled.12',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.google-apps.spreadsheet',
		'application/vnd.ms-powerpoint',
		'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
		'application/vnd.google-apps.presentation',
		'application/vnd.google-apps.drawing',
	];

	return in_array( $type, $editable_types, true );
}

/**
 * Get export formats for a file type.
 *
 * @param string $type File MIME type.
 *
 * @return array Export formats with mimetype and extension.
 */
function igd_get_export_as( $type ) {
	// Sanitize input
	$type = sanitize_text_field( $type );

	if ( 'application/vnd.google-apps.document' === $type ) {
		return [
			'MS Word document'     => [
				'mimetype'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'extension' => 'docx',
			],
			'HTML'                 => [
				'mimetype'  => 'text/html',
				'extension' => 'html',
			],
			'Text'                 => [
				'mimetype'  => 'text/plain',
				'extension' => 'txt',
			],
			'Open Office document' => [
				'mimetype'  => 'application/vnd.oasis.opendocument.text',
				'extension' => 'odt',
			],
			'PDF'                  => [
				'mimetype'  => 'application/pdf',
				'extension' => 'pdf',
			],
			'ZIP'                  => [
				'mimetype'  => 'application/zip',
				'extension' => 'zip',
			],
		];
	} elseif ( 'application/vnd.google-apps.spreadsheet' === $type ) {
		return [
			'MS Excel document'      => [
				'mimetype'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'extension' => 'xlsx',
			],
			'Open Office sheet'      => [
				'mimetype'  => 'application/x-vnd.oasis.opendocument.spreadsheet',
				'extension' => 'ods',
			],
			'PDF'                    => [
				'mimetype'  => 'application/pdf',
				'extension' => 'pdf',
			],
			'CSV (first sheet only)' => [
				'mimetype'  => 'text/csv',
				'extension' => 'csv',
			],
			'ZIP'                    => [
				'mimetype'  => 'application/zip',
				'extension' => 'zip',
			],
		];
	} elseif ( 'application/vnd.google-apps.drawing' === $type ) {
		return [
			'JPEG' => [ 'mimetype' => 'image/jpeg', 'extension' => 'jpeg' ],
			'PNG'  => [ 'mimetype' => 'image/png', 'extension' => 'png' ],
			'SVG'  => [ 'mimetype' => 'image/svg+xml', 'extension' => 'svg' ],
			'PDF'  => [ 'mimetype' => 'application/pdf', 'extension' => 'pdf' ],
		];
	} elseif ( 'application/vnd.google-apps.presentation' === $type ) {
		return [
			'MS PowerPoint document' => [
				'mimetype'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'extension' => 'pptx',
			],
			'PDF'                    => [
				'mimetype'  => 'application/pdf',
				'extension' => 'pdf',
			],
			'Text'                   => [
				'mimetype'  => 'text/plain',
				'extension' => 'txt',
			],
		];
	} elseif ( 'application/vnd.google-apps.script' === $type ) {
		return [
			'JSON' => [
				'mimetype'  => 'application/vnd.google-apps.script+json',
				'extension' => 'json',
			],
		];
	} elseif ( 'application/vnd.google-apps.form' === $type ) {
		return [
			'ZIP' => [ 'mimetype' => 'application/zip', 'extension' => 'zip' ],
		];
	}

	return [];
}

/**
 * Generate embed content HTML for files.
 *
 * Creates embeds for images, videos, audio, and documents
 * with proper security escaping and sandbox attributes.
 *
 * @param array $data Shortcode/embed configuration data.
 *
 * @return string HTML content for embedding files.
 */
function igd_get_embed_content( $data ) {
	// Validate and extract data
	$items          = $data['folders'] ?? [];
	$embed_type     = $data['embedType'] ?? 'readOnly';
	$embed_width    = $data['embedWidth'] ?? '100%';
	$embed_height   = $data['embedHeight'] ?? 'auto';
	$show_file_name = filter_var( $data['showFileName'] ?? false, FILTER_VALIDATE_BOOLEAN );
	$direct_image   = filter_var( $data['directImage'] ?? false, FILTER_VALIDATE_BOOLEAN );
	$allow_popout   = ! isset( $data['allowEmbedPopout'] ) || filter_var( $data['allowEmbedPopout'], FILTER_VALIDATE_BOOLEAN );

	// Validate dimensions
	$embed_width  = sanitize_text_field( $embed_width );
	$embed_height = sanitize_text_field( $embed_height );

	$files = [];

	// Extract files from items
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || empty( $item['id'] ) ) {
			continue;
		}

		if ( ! igd_is_dir( $item ) ) {
			$files[] = $item;
		} else {
			// Fetch files from folder
			$args        = [ 'folder' => $item ];
			$data_result = App::instance( $item['accountId'] ?? '' )->get_files( $args );

			if ( ! empty( $data_result['files'] ) && is_array( $data_result['files'] ) ) {
				foreach ( $data_result['files'] as $file ) {
					if ( ! igd_is_dir( $file ) ) {
						$files[] = $file;
					}
				}
			}
		}
	}

	ob_start();

	// Generate embed HTML for each file
	if ( ! empty( $files ) ) {
		foreach ( $files as $file ) {
			$type = $file['type'] ?? '';
			$name = $file['name'] ?? '';

			// Detect file type
			$is_image = 0 === strpos( $type, 'image/' );
			$is_video = 0 === strpos( $type, 'video/' );
			$is_audio = 0 === strpos( $type, 'audio/' );

			// Display file name if requested
			if ( $show_file_name ) {
				echo '<h4 class="igd-embed-name">' . esc_html( $name ) . '</h4>';
			}

			// Force read-only for images
			if ( $is_image ) {
				$embed_type = 'readOnly';
			}

			// Force read-only if no edit permission
			if ( empty( $file['permissions']['canEdit'] ) ) {
				$embed_type = 'readOnly';
			}

			// Get embed URL
			$url = igd_get_embed_url( $file, $embed_type, $direct_image );

			if ( empty( $url ) ) {
				continue;
			}

			// Render direct media or embedded
			if ( $direct_image && ( $is_image || $is_audio || $is_video ) ) {
				igd_render_direct_media( $url, $name, $embed_width, $embed_height, $is_image, $is_video, $is_audio, $file );
			} else {
				igd_render_iframe_embed( $url, $embed_width, $embed_height, $allow_popout, $embed_type );
			}
		}
	}

	$content = ob_get_clean();

	return sprintf( '<div class="igd-embed-wrap">%s</div>', $content );
}

/**
 * Render direct media (image, video, or audio).
 *
 * @param string $url Media URL.
 * @param string $name Media name/title.
 * @param string $width Width dimension.
 * @param string $height Height dimension.
 * @param bool $is_image Whether media is an image.
 * @param bool $is_video Whether media is a video.
 * @param bool $is_audio Whether media is audio.
 * @param array $file File data for poster image.
 */
function igd_render_direct_media( $url, $name, $width, $height, $is_image, $is_video, $is_audio, $file ) {
	$url    = esc_url( $url );
	$name   = esc_attr( $name );
	$width  = esc_attr( $width );
	$height = esc_attr( $height );

	if ( $is_image ) {
		printf( '<img src="%s" alt="%s" width="%s" height="%s" />', $url, $name, $width, $height );
	} elseif ( $is_video ) {
		$poster = ! empty( $file['thumbnailLink'] ) ? esc_url( $file['thumbnailLink'] ) : '';
		wp_video_shortcode( [
			'src'    => $url,
			'poster' => $poster,
		] );
	} elseif ( $is_audio ) {
		wp_audio_shortcode( [ 'src' => $url ] );
	}
}

/**
 * Render a secure iframe embed for Google Drive files.
 *
 * @param string $url          The iframe source URL.
 * @param string|int $width    The iframe width.
 * @param string|int $height   The iframe height.
 * @param bool $allow_popout   Whether to allow popouts.
 * @param string $embed_type   The embed type (e.g., 'readOnly').
 */
function igd_render_iframe_embed( $url, $width, $height, $allow_popout, $embed_type ) {
	// Sanitize input parameters.
	$url        = esc_url_raw( $url );
	$width      = preg_replace( '/[^0-9.%]/', '', (string) $width );
	$height     = preg_replace( '/[^0-9.%]/', '', (string) $height );
	$allow_popout = (bool) $allow_popout;
	$embed_type   = sanitize_key( $embed_type );

	// Base sandbox attributes — restrict as much as possible.
	$sandbox_attrs = [
		'allow-same-origin',
		'allow-scripts',
		'allow-popups',
		'allow-forms',
	];

	// Remove "allow-popups" for read-only embeds without popout permission.
	if ( ! $allow_popout && 'readonly' === strtolower( $embed_type ) ) {
		$sandbox_attrs = array_diff( $sandbox_attrs, [ 'allow-popups' ] );
	}

	// Build final sandbox attribute string safely.
	$sandbox_attr_string = sprintf(
		'sandbox="%s"',
		esc_attr( implode( ' ', $sandbox_attrs ) )
	);

	// Output iframe with proper escaping.
	printf(
		'<iframe class="igd-embed" src="%1$s" frameborder="0" width="%2$s" height="%3$s" referrerpolicy="no-referrer" allow="autoplay" allowfullscreen %4$s></iframe>',
		esc_url( $url ),
		esc_attr( $width ),
		esc_attr( $height ),
		$sandbox_attr_string
	);
}


/**
 * Check if folder is cached.
 *
 * @param string $folder_id Folder ID.
 * @param string $account_id Account ID (optional).
 *
 * @return bool True if folder is cached, false otherwise.
 */
function igd_is_cached_folder( $folder_id, $account_id = null ) {
	$folder_id      = sanitize_text_field( $folder_id );
	$cached_folders = get_option( 'igd_cached_folders', [] );

	if ( ! isset( $cached_folders[ $folder_id ] ) ) {
		return false;
	}

	// For special folders, verify account ID matches
	$special_folders = [ 'root', 'shared-drives', 'computers', 'shared', 'starred' ];
	if ( in_array( $folder_id, $special_folders, true ) && ! empty( $account_id ) ) {
		$account_id = sanitize_text_field( $account_id );

		return $cached_folders[ $folder_id ]['accountId'] === $account_id;
	}

	return true;
}

/**
 * Update cached folders list.
 *
 * @param string $folder_id Folder ID.
 * @param string $account_id Account ID.
 */
function igd_update_cached_folders( $folder_id, $account_id ) {
	$folder_id  = sanitize_text_field( $folder_id );
	$account_id = sanitize_text_field( $account_id );

	$cached_folders = get_option( 'igd_cached_folders', [] );

	$cached_folders[ $folder_id ] = [
		'id'        => $folder_id,
		'accountId' => $account_id,
	];

	update_option( 'igd_cached_folders', $cached_folders );
}

/**
 * Convert MIME type to file extension.
 *
 * @param string $mime MIME type.
 *
 * @return string File extension or empty string.
 */
function igd_mime_to_ext( $mime ) {
	$mime = sanitize_text_field( $mime );

	$mime_map = [
		'video/3gpp2'                                                               => '3g2',
		'video/3gp'                                                                 => '3gp',
		'video/3gpp'                                                                => '3gp',
		'application/x-compressed'                                                  => '7zip',
		'audio/x-acc'                                                               => 'aac',
		'audio/ac3'                                                                 => 'ac3',
		'application/postscript'                                                    => 'ai',
		'audio/x-aiff'                                                              => 'aif',
		'audio/aiff'                                                                => 'aif',
		'audio/x-au'                                                                => 'au',
		'video/x-msvideo'                                                           => 'avi',
		'video/msvideo'                                                             => 'avi',
		'video/avi'                                                                 => 'avi',
		'application/x-troff-msvideo'                                               => 'avi',
		'application/macbinary'                                                     => 'bin',
		'application/mac-binary'                                                    => 'bin',
		'application/x-binary'                                                      => 'bin',
		'application/x-macbinary'                                                   => 'bin',
		'image/bmp'                                                                 => 'bmp',
		'image/x-bmp'                                                               => 'bmp',
		'image/x-bitmap'                                                            => 'bmp',
		'image/x-xbitmap'                                                           => 'bmp',
		'image/x-win-bitmap'                                                        => 'bmp',
		'image/x-windows-bmp'                                                       => 'bmp',
		'image/ms-bmp'                                                              => 'bmp',
		'image/x-ms-bmp'                                                            => 'bmp',
		'application/bmp'                                                           => 'bmp',
		'application/x-bmp'                                                         => 'bmp',
		'application/x-win-bitmap'                                                  => 'bmp',
		'application/cdr'                                                           => 'cdr',
		'application/coreldraw'                                                     => 'cdr',
		'application/x-cdr'                                                         => 'cdr',
		'application/x-coreldraw'                                                   => 'cdr',
		'image/cdr'                                                                 => 'cdr',
		'image/x-cdr'                                                               => 'cdr',
		'zz-application/zz-winassoc-cdr'                                            => 'cdr',
		'application/mac-compactpro'                                                => 'cpt',
		'application/pkix-crl'                                                      => 'crl',
		'application/pkcs-crl'                                                      => 'crl',
		'application/x-x509-ca-cert'                                                => 'crt',
		'application/pkix-cert'                                                     => 'crt',
		'text/css'                                                                  => 'css',
		'text/x-comma-separated-values'                                             => 'csv',
		'text/comma-separated-values'                                               => 'csv',
		'application/vnd.msexcel'                                                   => 'csv',
		'application/x-director'                                                    => 'dcr',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
		'application/x-dvi'                                                         => 'dvi',
		'message/rfc822'                                                            => 'eml',
		'application/x-msdownload'                                                  => 'exe',
		'video/x-f4v'                                                               => 'f4v',
		'audio/x-flac'                                                              => 'flac',
		'video/x-flv'                                                               => 'flv',
		'image/gif'                                                                 => 'gif',
		'application/gpg-keys'                                                      => 'gpg',
		'application/x-gtar'                                                        => 'gtar',
		'application/x-gzip'                                                        => 'gzip',
		'application/mac-binhex40'                                                  => 'hqx',
		'application/mac-binhex'                                                    => 'hqx',
		'application/x-binhex40'                                                    => 'hqx',
		'application/x-mac-binhex40'                                                => 'hqx',
		'text/html'                                                                 => 'html',
		'image/x-icon'                                                              => 'ico',
		'image/x-ico'                                                               => 'ico',
		'image/vnd.microsoft.icon'                                                  => 'ico',
		'text/calendar'                                                             => 'ics',
		'application/java-archive'                                                  => 'jar',
		'application/x-java-application'                                            => 'jar',
		'application/x-jar'                                                         => 'jar',
		'image/jp2'                                                                 => 'jp2',
		'video/mj2'                                                                 => 'jp2',
		'image/jpx'                                                                 => 'jp2',
		'image/jpm'                                                                 => 'jp2',
		'image/jpeg'                                                                => 'jpeg',
		'image/pjpeg'                                                               => 'jpeg',
		'application/x-javascript'                                                  => 'js',
		'application/json'                                                          => 'json',
		'text/json'                                                                 => 'json',
		'application/vnd.google-earth.kml+xml'                                      => 'kml',
		'application/vnd.google-earth.kmz'                                          => 'kmz',
		'text/x-log'                                                                => 'log',
		'audio/x-m4a'                                                               => 'm4a',
		'audio/mp4'                                                                 => 'm4a',
		'application/vnd.mpegurl'                                                   => 'm4u',
		'audio/midi'                                                                => 'mid',
		'application/vnd.mif'                                                       => 'mif',
		'video/quicktime'                                                           => 'mov',
		'video/x-sgi-movie'                                                         => 'movie',
		'audio/mpeg'                                                                => 'mp3',
		'audio/mpg'                                                                 => 'mp3',
		'audio/mpeg3'                                                               => 'mp3',
		'audio/mp3'                                                                 => 'mp3',
		'video/mp4'                                                                 => 'mp4',
		'video/mpeg'                                                                => 'mpeg',
		'application/oda'                                                           => 'oda',
		'audio/ogg'                                                                 => 'ogg',
		'video/ogg'                                                                 => 'ogg',
		'application/ogg'                                                           => 'ogg',
		'font/otf'                                                                  => 'otf',
		'application/x-pkcs10'                                                      => 'p10',
		'application/pkcs10'                                                        => 'p10',
		'application/x-pkcs12'                                                      => 'p12',
		'application/x-pkcs7-signature'                                             => 'p7a',
		'application/pkcs7-mime'                                                    => 'p7c',
		'application/x-pkcs7-mime'                                                  => 'p7c',
		'application/x-pkcs7-certreqresp'                                           => 'p7r',
		'application/pkcs7-signature'                                               => 'p7s',
		'application/pdf'                                                           => 'pdf',
		'application/octet-stream'                                                  => 'pdf',
		'application/x-x509-user-cert'                                              => 'pem',
		'application/x-pem-file'                                                    => 'pem',
		'application/pgp'                                                           => 'pgp',
		'application/x-httpd-php'                                                   => 'php',
		'application/php'                                                           => 'php',
		'application/x-php'                                                         => 'php',
		'text/php'                                                                  => 'php',
		'text/x-php'                                                                => 'php',
		'application/x-httpd-php-source'                                            => 'php',
		'image/png'                                                                 => 'png',
		'image/x-png'                                                               => 'png',
		'application/powerpoint'                                                    => 'ppt',
		'application/vnd.ms-powerpoint'                                             => 'ppt',
		'application/vnd.ms-office'                                                 => 'ppt',
		'application/msword'                                                        => 'doc',
		'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
		'application/x-photoshop'                                                   => 'psd',
		'image/vnd.adobe.photoshop'                                                 => 'psd',
		'audio/x-realaudio'                                                         => 'ra',
		'audio/x-pn-realaudio'                                                      => 'ram',
		'application/x-rar'                                                         => 'rar',
		'application/rar'                                                           => 'rar',
		'application/x-rar-compressed'                                              => 'rar',
		'audio/x-pn-realaudio-plugin'                                               => 'rpm',
		'application/x-pkcs7'                                                       => 'rsa',
		'text/rtf'                                                                  => 'rtf',
		'text/richtext'                                                             => 'rtx',
		'video/vnd.rn-realvideo'                                                    => 'rv',
		'application/x-stuffit'                                                     => 'sit',
		'application/smil'                                                          => 'smil',
		'text/srt'                                                                  => 'srt',
		'image/svg+xml'                                                             => 'svg',
		'application/x-shockwave-flash'                                             => 'swf',
		'application/x-tar'                                                         => 'tar',
		'application/x-gzip-compressed'                                             => 'tgz',
		'image/tiff'                                                                => 'tiff',
		'font/ttf'                                                                  => 'ttf',
		'text/plain'                                                                => 'txt',
		'text/x-vcard'                                                              => 'vcf',
		'application/videolan'                                                      => 'vlc',
		'text/vtt'                                                                  => 'vtt',
		'audio/x-wav'                                                               => 'wav',
		'audio/wave'                                                                => 'wav',
		'audio/wav'                                                                 => 'wav',
		'application/wbxml'                                                         => 'wbxml',
		'video/webm'                                                                => 'webm',
		'image/webp'                                                                => 'webp',
		'audio/x-ms-wma'                                                            => 'wma',
		'application/wmlc'                                                          => 'wmlc',
		'video/x-ms-wmv'                                                            => 'wmv',
		'video/x-ms-asf'                                                            => 'wmv',
		'font/woff'                                                                 => 'woff',
		'font/woff2'                                                                => 'woff2',
		'application/xhtml+xml'                                                     => 'xhtml',
		'application/excel'                                                         => 'xl',
		'application/msexcel'                                                       => 'xls',
		'application/x-msexcel'                                                     => 'xls',
		'application/x-ms-excel'                                                    => 'xls',
		'application/x-excel'                                                       => 'xls',
		'application/x-dos_ms_excel'                                                => 'xls',
		'application/xls'                                                           => 'xls',
		'application/x-xls'                                                         => 'xls',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
		'application/vnd.ms-excel'                                                  => 'xlsx',
		'application/xml'                                                           => 'xml',
		'text/xml'                                                                  => 'xml',
		'text/xsl'                                                                  => 'xsl',
		'application/xspf+xml'                                                      => 'xspf',
		'application/x-compress'                                                    => 'z',
		'application/x-zip'                                                         => 'zip',
		'application/zip'                                                           => 'zip',
		'application/x-zip-compressed'                                              => 'zip',
		'application/s-compressed'                                                  => 'zip',
		'multipart/x-zip'                                                           => 'zip',
		'text/x-scriptzsh'                                                          => 'zsh',
	];

	return isset( $mime_map[ $mime ] ) ? $mime_map[ $mime ] : '';
}

function igd_get_child_items( $folder ) {
	$args = [
		'folder' => $folder,
	];

	$app = App::instance( $folder['accountId'] );

	$data = $app->get_files( $args );

	if ( ! empty( $data['error'] ) ) {
		error_log( 'Integrate Google Drive - Error: ' . $data['error'] );

		return [];
	}

	return ! empty( $data['files'] ) ? $data['files'] : [];
}

function igd_get_all_child_folders( $folder ) {

	$folders = array_filter( igd_get_child_items( $folder ), function ( $file ) {
		return igd_is_dir( $file );
	} );

	$list = [];

	if ( ! empty( $folders ) ) {
		foreach ( $folders as $folder_item ) {
			$list[]        = $folder_item;
			$child_folders = igd_get_all_child_folders( $folder_item );
			$list          = array_merge( $list, $child_folders );
		}
	}

	return $list;
}

function igd_get_all_child_files( $folder ) {

	$items = igd_get_child_items( $folder );

	$list = [];

	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {

			if ( igd_is_dir( $item ) ) {
				$child_files = igd_get_all_child_files( $item );
				$list        = array_merge( $list, $child_files );
				continue;
			}

			$list[] = $item;
		}
	}

	return $list;
}

function igd_download_zip( $file_ids, $request_id = '', $account_id = '' ) {

	$files = [];

	if ( ! empty( $file_ids ) ) {
		$app = App::instance( $account_id );

		foreach ( $file_ids as $file_id ) {
			$file = $app->get_file_by_id( $file_id );

			do_action( 'igd_insert_log', [
				'type'       => 'download',
				'file_id'    => $file_id,
				'account_id' => $account_id,
				'file_name'  => $file['name'],
				'file_type'  => $file['type'],
			] );

			$files[] = $file;
		}
	}

	Zip::instance( $files, $request_id )->do_zip();
	exit();
}

function igd_get_free_memory_available() {
	$memory_limit = igd_return_bytes( ini_get( 'memory_limit' ) );

	if ( $memory_limit < 0 ) {
		if ( defined( 'WP_MEMORY_LIMIT' ) ) {
			$memory_limit = igd_return_bytes( WP_MEMORY_LIMIT );
		} else {
			$memory_limit = 1024 * 1024 * 92; // Return 92MB if we can't get any reading on memory limits
		}
	}

	$memory_usage = memory_get_usage( true );

	$free_memory = $memory_limit - $memory_usage;

	if ( $free_memory < ( 1024 * 1024 * 10 ) ) {
		// Return a minimum of 10MB available
		return 1024 * 1024 * 10;
	}

	return $free_memory;
}

function igd_return_bytes( $size_str ) {
	if ( empty( $size_str ) ) {
		return $size_str;
	}

	$unit = substr( $size_str, - 1 );
	if ( ( 'B' === $unit || 'b' === $unit ) && ( ! ctype_digit( substr( $size_str, - 2 ) ) ) ) {
		$unit = substr( $size_str, - 2, 1 );
	}

	switch ( $unit ) {
		case 'M':
		case 'm':
			return (int) $size_str * 1048576;

		case 'K':
		case 'k':
			return (int) $size_str * 1024;

		case 'G':
		case 'g':
			return (int) $size_str * 1073741824;

		default:
			return $size_str;
	}
}

function igd_get_settings( $key = null, $default = null ) {
	$settings = (array) get_option( 'igd_settings', [] );

	if ( ! isset( $settings['emailReportRecipients'] ) ) {
		$settings['emailReportRecipients'] = get_option( 'admin_email' );
	}

	if ( empty( $settings ) && ! empty( $default ) ) {
		return $default;
	}

	// if user has no access remove clientId and clientSecret from settings
	if ( ! igd_user_can_access( 'settings' ) ) {
		unset( $settings['clientID'] );
		unset( $settings['clientSecret'] );
		unset( $settings['emailReportRecipients'] );
	}

	if ( empty( $key ) ) {
		return ! empty( $settings ) ? $settings : [];
	}

	return $settings[ $key ] ?? $default;
}

function igd_get_embed_url( $file, $embed_type = 'readOnly', $direct_image = false, $is_preview = false, $popout = false, $download = true ) {
	$id         = $file['id'];
	$account_id = $file['accountId'];
	$type       = $file['type'] ?? '';

	$is_editable        = in_array( $embed_type, [ 'editable', 'fullEditable' ] );
	$editable_arguments = $is_editable && $embed_type === 'fullEditable' ? 'edit?usp=drivesdk&rm=embedded&embedded=true' : 'edit?usp=drivesdk&rm=minimal&embedded=true';

	$permissions = Permissions::instance( $account_id );

	if ( ! $permissions->has_permission( $file ) ) {
		$permissions->set_permission( $file );
	}

	if ( $is_preview || $popout ) {
		$url = "https://drive.google.com/file/d/{$id}/preview?rm=minimal";
	} else {
		$doc_types = [
			'doc'          => [
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.google-apps.document'
			],
			'sheet'        => [
				'application/vnd.ms-excel',
				'application/vnd.ms-excel.sheet.macroenabled.12',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.google-apps.spreadsheet'
			],
			'presentation' => [
				'application/vnd.ms-powerpoint',
				'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
				'application/vnd.google-apps.presentation'
			],
			'drawing'      => [ 'application/vnd.google-apps.drawing' ],
			'form'         => [ 'application/vnd.google-apps.form' ],
		];

		$type_to_path = [
			'doc'          => 'document',
			'sheet'        => 'spreadsheets',
			'presentation' => 'presentation',
			'drawing'      => 'drawings',
			'form'         => 'forms'
		];

		$found = false;
		foreach ( $doc_types as $doc_type => $types ) {
			if ( in_array( $type, $types ) ) {
				$found = true;

				// Set the correct arguments based on type
				if ( $doc_type === 'form' ) {
					$arguments = 'viewform';
				} else {
					$arguments = $is_editable ? $editable_arguments : 'preview?rm=minimal';
				}

				$url = "https://docs.google.com/{$type_to_path[$doc_type]}/d/{$id}/{$arguments}";

				if ( $doc_type === 'doc' || $doc_type === 'sheet' || $doc_type === 'presentation' ) {
					if ( ! $is_editable ) {
						$url = "https://drive.google.com/file/d/{$id}/preview?rm=minimal";
					}
				}

				break;
			}
		}

		if ( ! $found ) {
			$is_media = strpos( $type, 'image/' ) === 0 || preg_match( '/^(audio|video)\//', $type );

			if ( $type === 'application/vnd.google-apps.folder' ) {
				$url = "https://drive.google.com/open?id={$id}";
			} elseif ( $direct_image && $is_media ) {
				if ( strpos( $type, 'image/' ) === 0 ) {
					$url = igd_get_thumbnail_url( $file, 'full' );
				} elseif ( preg_match( '/^(audio|video)\//', $type ) ) {
					$ext = strpos( $type, 'audio/' ) === 0 ? '.mp3' : '.mp4';
					$url = home_url( "?igd_stream=1&id={$id}&account_id={$account_id}&ext={$ext}" );
				}
			} else {
				$arguments = $is_editable ? $editable_arguments : 'preview?rm=minimal';
				$url       = "https://drive.google.com/file/d/$id/$arguments";
			}
		}
	}

	if ( ! empty( $file['resourceKey'] ) ) {
		$url .= "&resourcekey={$file['resourceKey']}";
	}

	return $url;
}

function igd_is_public_file( $file ) {
	if ( isset( $file['permissions'] ) &&
	     isset( $file['permissions']['users'] ) &&
	     isset( $file['permissions']['users']['anyoneWithLink'] ) ) {
		$role = $file['permissions']['users']['anyoneWithLink']['role'];

		return $role === 'reader' || $role === 'writer';
	}

	return false;
}

function igd_get_thumbnail_url( $file, $size, $custom_size = [] ) {

	$id            = $file['id'];
	$iconLink      = $file['iconLink'] ?? '';
	$thumbnailLink = $file['thumbnailLink'] ?? '';
	$account_id    = $file['accountId'] ?? '';

	$w = ! empty( $custom_size['width'] ) ? $custom_size['width'] : 256;
	$h = ! empty( $custom_size['height'] ) ? $custom_size['height'] : 256;

	$thumb = str_replace( '/16/', "/$w/", $iconLink );

	if ( $thumbnailLink ) {
		if ( igd_is_public_file( $file ) ) {
			switch ( $size ) {
				case 'small':
				case 'custom':
					$thumb = "https://drive.google.com/thumbnail?id=$id&sz=w$w-h$h";
					break;
				case 'medium':
					$thumb = "https://drive.google.com/thumbnail?id=$id&sz=w600-h400";
					break;
				case 'large':
					$thumb = "https://drive.google.com/thumbnail?id=$id&sz=w1024-h768";
					break;
				case 'full':
					$thumb = "https://drive.google.com/thumbnail?id=$id&sz=w2048";
					break;
				default:
					$thumb = "https://drive.google.com/thumbnail?id=$id&sz=w300-h300";
			}
		} else {

			// get new thumbnail link for non-public files
			if ( str_contains( $thumbnailLink, 'google.com' ) ) {

				$thumb_data = [
					'igd_preview_image' => 1,
					'id'                => $id,
					'size'              => $size,
					'account_id'        => $account_id
				];

				if ( ! empty( $custom_size ) ) {
					$thumb_data['width']  = $w;
					$thumb_data['height'] = $h;
				}

				$thumb = add_query_arg( $thumb_data, home_url() );

			} else {

				switch ( $size ) {
					case 'custom':
						$thumb = str_replace( '=s220', "={$w}-h{$h}", $thumbnailLink );
						break;
					case 'small':
						$thumb = str_replace( '=s220', '=w300-h300', $thumbnailLink );
						break;
					case 'medium':
						$thumb = str_replace( '=s220', '=h600-nu', $thumbnailLink );
						break;
					case 'large':
						$thumb = str_replace( '=s220', '=w1024-h768-p-k-nu', $thumbnailLink );
						break;
					case 'full':
						$thumb = str_replace( '=s220', '', $thumbnailLink );
						break;
					default:
						$thumb = str_replace( '=s220', '=w200-h190-p-k-nu', $thumbnailLink );
				}

			}
		}
	}

	return $thumb;
}

function igd_should_allow( $item, $filters = [] ) {
	$is_dir = igd_is_dir( $item );

	if ( ! $is_dir && isset( $filters['showFiles'] ) && empty( $filters['showFiles'] ) ) {
		return false;
	}

	if ( $is_dir && isset( $filters['showFolders'] ) && empty( $filters['showFolders'] ) ) {
		return false;
	}

	$extension = ! empty( $item['extension'] ) ? $item['extension'] : '';
	$name      = ! empty( $item['name'] ) ? $item['name'] : '';

	// Extensions
	if ( ! $is_dir ) {
		if ( ! empty( $filters['allowAllExtensions'] ) ) {
			if ( ! empty( $filters['allowExceptExtensions'] ) ) {
				$exceptExtensions = array_map( 'trim', explode( ',', $filters['allowExceptExtensions'] ) );

				if ( in_array( $extension, $exceptExtensions ) ) {
					return false;
				}
			}
		} else {
			if ( ! empty( $filters['allowExtensions'] ) ) {
				$allowedExtensions = array_map( 'trim', explode( ',', $filters['allowExtensions'] ) );

				if ( ! in_array( $extension, $allowedExtensions ) ) {
					return false;
				}
			}
		}
	}

	// Names
	if ( ! empty( $filters['nameFilterOptions'] ) ) {
		$nameFilterOptions = $filters['nameFilterOptions'];

		if ( in_array( 'files', $nameFilterOptions ) && ! in_array( 'folders', $nameFilterOptions ) && $is_dir ) {
			return true;
		}

		if ( in_array( 'folders', $nameFilterOptions ) && ! in_array( 'files', $nameFilterOptions ) && ! $is_dir ) {
			return true;
		}
	}

	if ( ! empty( $filters['allowAllNames'] ) ) {
		if ( ! empty( $filters['allowExceptNames'] ) ) {
			$exceptPatterns = array_map( 'trim', explode( ',', $filters['allowExceptNames'] ) );

			$match = false;
			foreach ( $exceptPatterns as $pattern ) {
				if ( fnmatch( strtolower( $pattern ), strtolower( $name ) ) ) {
					$match = true;
					break;
				}
			}

			if ( $match ) {
				return false;
			}
		}
	} else {
		if ( ! empty( $filters['allowNames'] ) ) {
			$allowedPatterns = array_map( 'trim', explode( ',', $filters['allowNames'] ) );

			foreach ( $allowedPatterns as $pattern ) {
				if ( ! fnmatch( strtolower( $pattern ), strtolower( $name ) ) ) {
					return false;
				}
			}
		}
	}


	if ( ! empty( $filters['isMedia'] ) ) {
		return $is_dir || preg_match( '/^(audio|video)\//', $item['type'] );
	}


	if ( ! empty( $filters['isGallery'] ) ) {
		return $is_dir || preg_match( '/^(image|video)\//', $item['type'] );
	}

	if ( ! empty( $filters['onlyFolders'] ) ) {
		return $is_dir;
	}

	if ( ! empty( $filters['onlyVideo'] ) ) {
		return $is_dir || preg_match( '/^video\//', $item['type'] );
	}

	if ( ! empty( $filters['onlyTables'] ) ) {
		$table_types = [
			'application/vnd.google-apps.spreadsheet',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.ms-excel',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
		];

		return $is_dir || in_array( $item['type'], $table_types );
	}

	return true;

}

function igd_delete_cache( $folder_ids = [], $account_id = false ) {

	// Check if running on cron
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		$interval = igd_get_settings( 'syncInterval', '3600' );

		if ( 'never' != $interval ) {
			$syncType   = igd_get_settings( 'syncType', 'all' );
			$folder_ids = [];

			if ( $syncType == 'selected' ) {
				$folders    = igd_get_settings( 'syncFolders', [] );
				$folder_ids = array_column( $folders, 'id' );
			}
		}
	}

	if ( ! empty( $folder_ids ) ) {
		$cached_folders    = get_option( 'igd_cached_folders', [] );
		$folders_to_delete = [];

		foreach ( $folder_ids as $folder_id ) {
			if ( ! empty( $cached_folders[ $folder_id ] ) ) {
				unset( $cached_folders[ $folder_id ] );
				$folders_to_delete[] = $folder_id;
			}
		}

		// Update the option after the loop
		update_option( 'igd_cached_folders', $cached_folders );

		// Delete files of all folders in a single operation
		if ( ! empty( $folders_to_delete ) ) {
			Files::delete_folder_files( $folders_to_delete );
		}
	} else {
		delete_option( 'igd_cached_folders' );
		Files::delete_account_files( $account_id );
		igd_delete_thumbnail_cache();
	}

}

function igd_color_brightness( $hex, $steps ) {

	// return if not hex color
	if ( ! preg_match( '/^#([a-f0-9]{3}){1,2}$/i', $hex ) ) {
		return $hex;
	}

	// Steps should be between -255 and 255. Negative = darker, positive = lighter
	$steps = max( - 255, min( 255, $steps ) );

	// Normalize into a six character long hex string
	$hex = str_replace( '#', '', $hex );
	if ( strlen( $hex ) == 3 ) {
		$hex = str_repeat( substr( $hex, 0, 1 ), 2 ) . str_repeat( substr( $hex, 1, 1 ), 2 ) . str_repeat( substr( $hex, 2, 1 ), 2 );
	}

	// Split into three parts: R, G and B
	$color_parts = str_split( $hex, 2 );
	$return      = '#';

	foreach ( $color_parts as $color ) {
		$color  = hexdec( $color ); // Convert to decimal
		$color  = max( 0, min( 255, $color + $steps ) ); // Adjust color
		$return .= str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT ); // Make two char hex code
	}

	return $return;
}

function igd_hex2rgba( $color, $opacity = false ) {

	$default = 'rgb(0,0,0)';

	//Return default if no color provided
	if ( empty( $color ) ) {
		return $default;
	}

	//Sanitize $color if "#" is provided
	if ( $color[0] == '#' ) {
		$color = substr( $color, 1 );
	}

	//Check if color has 6 or 3 characters and get values
	if ( strlen( $color ) == 6 ) {
		$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
	} elseif ( strlen( $color ) == 3 ) {
		$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
	} else {
		return $default;
	}

	//Convert hexadec to rgb
	$rgb = array_map( 'hexdec', $hex );

	//Check if opacity is set(rgba or rgb)
	if ( $opacity ) {
		if ( abs( $opacity ) > 1 ) {
			$opacity = 1.0;
		}
		$output = 'rgba(' . implode( ",", $rgb ) . ',' . $opacity . ')';
	} else {
		$output = 'rgb(' . implode( ",", $rgb ) . ')';
	}

	//Return rgb(a) color string
	return $output;
}

function igd_get_user_gravatar( $user_id, $size = 32 ) {

	$user = get_user_by( 'id', $user_id );

	if ( ! $user ) {
		return '';
	}

	if ( function_exists( 'get_wp_user_avatar' ) ) {
		$gravatar = get_wp_user_avatar( $user->user_email, $size );
	} else {
		$gravatar = get_avatar( $user->user_email, $size );
	}

	if ( empty( $gravatar ) ) {
		$gravatar = sprintf( '<img src="%s/images/user-icon.png" height="%s" />', IGD_ASSETS, $size );
	}

	return $gravatar;
}

function igd_get_user_ip() {
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] );
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] );
	} else {
		$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
	}

	return $ip;
}

function igd_sanitize_array( $array ) {

	if ( ! is_array( $array ) ) {
		return $array;
	}

	foreach ( $array as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = igd_sanitize_array( $value );
		} else {
			if ( in_array( $value, [ 'true', 'false' ] ) ) {
				$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
			} elseif ( is_numeric( $value ) ) {
				if ( strpos( $value, '.' ) !== false ) {
					$value = floatval( $value );
				} elseif ( filter_var( $value, FILTER_VALIDATE_INT ) !== false && $value <= PHP_INT_MAX ) {
					$value = intval( $value );
				} else {
					// Keep large integers or non-integer values as string
					$value = $value;
				}
			} else {
				$value = wp_kses_post( $value );
			}
		}
	}

	return $array;
}

function igd_should_filter_files( $filters ) {

	return ( ! empty( $filters['allowExtensions'] ) && empty( $filters['allowAllExtensions'] ) )
	       || ( ! empty( $filters['allowExceptExtensions'] ) && ! empty( $filters['allowAllExtensions'] ) )
	       || ( ! empty( $filters['nameFilterOptions'] ) && ( ! empty( $filters['allowNames'] ) && empty( $filters['allowAllNames'] ) ) || ( ! empty( $filters['allowExceptNames'] ) && ! empty( $filters['allowAllNames'] ) ) )
	       || ( isset( $filters['showFiles'] ) && empty( $filters['showFiles'] ) )
	       || ( isset( $filters['showFolders'] ) && empty( $filters['showFolders'] ) )
	       || ! empty( $filters['isGallery'] )
	       || ! empty( $filters['onlyFolders'] )
	       || ! empty( $filters['onlyTables'] )
	       || ! empty( $filters['isMedia'] );
}

/**
 * The function checks if the current user has the required role or if the user is in the list of users with access
 * The access rights are defined in the settings page
 *
 * @param $access_right
 *
 * @return bool
 */
function igd_user_can_access( $access_right ) {

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include_once( ABSPATH . "wp-includes/pluggable.php" );
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	$current_user = wp_get_current_user();

	if ( ! is_object( $current_user ) ) {
		return false;
	}

	$settings = (array) get_option( 'igd_settings', [] );
	$key      = "access" . str_replace( ' ', '', ucwords( str_replace( '_', ' ', $access_right ) ) ) . "Users";

	$access_users = $settings[ $key ] ?? [ 'administrator' ];

	$can_access = ! empty( array_intersect( $current_user->roles, $access_users ) ) || in_array( $current_user->ID, $access_users ) || ( is_multisite() && is_super_admin() );

	// Check if privateFoldersInAdminDashboard is enabled
	if ( 'file_browser' == $access_right ) {
		$private_folders_in_admin_dashboard = igd_get_settings( 'privateFoldersInAdminDashboard', false );

		if ( $private_folders_in_admin_dashboard ) {
			$folders = get_user_meta( get_current_user_id(), 'igd_folders', true );

			$can_access = $can_access || ! empty( $folders );
		}
	}

	// Check if media library integration is enabled
	if ( $can_access && 'media_library' == $access_right ) {
		$can_access = Integration::instance()->is_active( 'media-library' );
	}

	return apply_filters( 'igd_can_access', $can_access, $access_right );
}

function igd_get_user_access_data() {
	$access_users = igd_get_settings( "accessFileBrowserUsers", [] );
	$data         = [];

	if ( in_array( 'administrator', $access_users ) ) {
		$key = array_search( 'administrator', $access_users );
		unset( $access_users[ $key ] );
	}

	if ( ! empty( $access_users ) ) {

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include_once( ABSPATH . "wp-includes/pluggable.php" );
		}

		$current_user = wp_get_current_user();

		// Get assigned folders for current user
		if ( $current_user ) {
			$user_folders = igd_get_settings( 'userFolders', [] );

			if ( in_array( $current_user->ID, $access_users ) && isset( $user_folders[ $current_user->ID ] ) ) {
				$folders = $user_folders[ $current_user->ID ];
			} elseif ( ! empty( array_intersect( $current_user->roles, $access_users ) ) ) {
				$folders = [];
				foreach ( $access_users as $role ) {
					if ( in_array( $role, $current_user->roles ) ) {
						$u_folders = ! empty( $user_folders[ $role ] ) ? $user_folders[ $role ] : [];
						$folders   = array_merge( $folders, $u_folders );
					}
				}
			}
		}
	}


	// Check if privateFoldersInAdminDashboard is enabled
	if ( ! current_user_can( 'administrator' ) ) {
		$private_folders_in_admin_dashboard = igd_get_settings( 'privateFoldersInAdminDashboard', false );

		if ( $private_folders_in_admin_dashboard ) {
			$private_folders = get_user_meta( get_current_user_id(), 'igd_folders', true );

			// if not empty $private_folders, merge private folders with assigned folders
			if ( ! empty( $private_folders ) ) {
				$folders = ! empty( $folders ) ? array_merge( $folders, $private_folders ) : $private_folders;
			}
		}
	}


	if ( ! empty( $folders ) ) {

		$is_single_folder = count( array_unique( wp_list_pluck( $folders, 'id' ) ) ) == 1;

		// Get files from the single folder
		if ( $is_single_folder ) {

			$folder    = $folders[0];
			$folder_id = $folder['id'];

			$data['initParentFolder'] = $folder;

			$args = [
				'folder'      => $folder,
				'from_server' => true,
				'limit'       => 100,
			];

			$transient = get_transient( 'igd_latest_fetch_' . $folder_id );
			if ( $transient ) {
				$args['from_server'] = false;
			} else {
				set_transient( 'igd_latest_fetch_' . $folder_id, true, 60 * MINUTE_IN_SECONDS );
			}

			// Fetch files
			$account_id = ! empty( $folder['accountId'] ) ? $folder['accountId'] : '';
			$files_data = App::instance( $account_id )->get_files( $args );

			if ( isset( $files_data['files'] ) ) {
				$data['initFolders'] = array_values( $files_data['files'] );
			}

			// Update the arguments for the next iteration
			$pageNumber                             = ! empty( $files_data['nextPageNumber'] ) ? $files_data['nextPageNumber'] : 0;
			$data['initParentFolder']['pageNumber'] = $pageNumber;

		} else {
			$data['initFolders'] = array_values( $folders );
		}
	}

	return ! empty( $data ) ? $data : false;
}

function igd_contains_tags( $type = '', $template = '' ) {
	// Define tags
	$user_tags = [
		'%user_login%',
		'%user_email%',
		'%first_name%',
		'%last_name%',
		'%display_name%',
		'%user_id%',
		'%user_role%',
		'%user_meta_{key}%'
	];

	$post_tags = [
		'%post_id%',
		'%post_title%',
		'%post_slug%',
		'%post_author%',
		'%post_date%',
		'%post_modified%',
		'%post_type%',
		'%post_status%',
		'%post_category%',
		'%post_tags%',
		'%post_meta_{key}%'
	];

	$woocommerce_tags = [
		'%wc_product_name%',
		'%wc_product_id%',
		'%wc_product_sku%',
		'%wc_product_slug%',
		'%wc_product_price%',
		'%wc_product_sale_price%',
		'%wc_product_regular_price%',
		'%wc_product_tags%',
		'%wc_product_type%',
		'%wc_product_status%',
		'%wc_product_meta_{key}%',
	];

	$post_tags = array_merge( $post_tags, $woocommerce_tags );  // Merge post tags and WooCommerce tags

	if ( $type == 'user' ) {
		return array_reduce( $user_tags, function ( $carry, $item ) use ( $template ) {
				return $carry || strpos( $template, $item ) !== false;
			}, false ) || strpos( $template, '%user_meta_' ) !== false;
	} elseif ( $type == 'post' ) {
		return array_reduce( $post_tags, function ( $carry, $item ) use ( $template ) {
				return $carry || strpos( $template, $item ) !== false;
			}, false ) || strpos( $template, '%post_meta_' ) !== false;
	} elseif ( $type == 'woocommerce' ) {
		return array_reduce( $woocommerce_tags, function ( $carry, $item ) use ( $template ) {
			return $carry || strpos( $template, $item ) !== false;
		}, false );
	} elseif ( $type == 'field' ) {
		return strpos( $template, '%field_' ) !== false;
	} elseif ( $type == 'field_id' ) {
		return strpos( $template, '%field_id_' ) !== false;
	}

	return false;

}

function igd_replace_template_tags( $data, $extra_tag_values = [] ) {

	$name_template = ! empty( $data['name'] ) ? $data['name'] : '%user_login% (%user_email%)';

	$date      = current_time( 'Y-m-d' );
	$time      = current_time( 'H:i:s' );
	$unique_id = uniqid();

	$search = [
		'%date%',
		'%time%',
		'%unique_id%',
	];

	$replace = [
		$date,
		$time,
		$unique_id,
	];

	$name = str_replace( $search, $replace, $name_template );

	// Handle form data
	if ( ! empty( $data['form'] ) ) {
		$form = $data['form'];

		$search = array_merge( $search, [
			'%form_title%',
			'%form_id%',
			'%entry_id%',
		] );

		$replace = array_merge( $replace, [
			$form['form_title'],
			$form['form_id'] ?? '',
			! empty( $form['entry_id'] ) ? $form['entry_id'] : '',
		] );

		$name = str_replace( $search, $replace, $name );

	}

	// Handle file data
	if ( ! empty( $data['file'] ) ) {
		$file = $data['file'];

		$search = array_merge( $search, [
			'%file_name%',
			'%file_extension%',
			'%queue_index%',
		] );

		$replace = array_merge( $replace, [
			$file['file_name'],
			$file['file_extension'],
			$file['queue_index'],
		] );

		$name = str_replace( $search, $replace, $name );

	}

	// Handle post data
	if ( ! empty( $data['post'] ) ) {
		$post = $data['post'];

		$post_id       = $post->ID;
		$post_title    = $post->post_title;
		$post_slug     = $post->post_name;
		$post_author   = get_the_author_meta( 'display_name', $post->post_author );
		$post_date     = $post->post_date;
		$post_modified = $post->post_modified;
		$post_type     = $post->post_type;
		$post_status   = $post->post_status;

		$post_categories = get_the_category( $post_id );
		if ( ! is_wp_error( $post_categories ) && ! empty( $post_categories ) ) {
			$post_categories = implode( ', ', wp_list_pluck( $post_categories, 'name' ) );
		} else {
			$post_categories = '';
		}

		$post_tags = get_the_tags( $post_id );
		if ( ! is_wp_error( $post_tags ) && ! empty( $post_tags ) ) {
			$post_tags = implode( ', ', wp_list_pluck( $post_tags, 'name' ) );
		} else {
			$post_tags = '';
		}

		$search = array_merge( $search, [
			'%post_id%',
			'%post_title%',
			'%post_slug%',
			'%post_author%',
			'%post_date%',
			'%post_modified%',
			'%post_type%',
			'%post_status%',
			'%post_category%',
			'%post_tags%',
		] );

		$replace = array_merge( $replace, [
			$post_id,
			$post_title,
			$post_slug,
			$post_author,
			$post_date,
			$post_modified,
			$post_type,
			$post_status,
			$post_categories,
			$post_tags,
		] );

		$name = str_replace( $search, $replace, $name );

		//Check if %post_meta_{key}% is in the name template
		if ( preg_match_all( '/%post_meta_(.*?)%/', $name, $matches ) ) {
			foreach ( $matches[1] as $meta_key ) {
				$meta_key_trimmed = trim( $meta_key );
				$meta_value       = get_post_meta( $post_id, $meta_key_trimmed, true );

				$name = str_replace( '%post_meta_' . $meta_key_trimmed . '%', $meta_value, $name );
			}
		}

	}

	// Handle user data
	if ( ! empty( $data['user'] ) ) {
		$user = $data['user'];


		$user_login   = $user->user_login;
		$user_email   = $user->user_email;
		$display_name = $user->display_name;
		$first_name   = $user->first_name;
		$last_name    = $user->last_name;
		$user_role    = ! empty( $user->roles ) ? implode( ', ', $user->roles ) : '';

		$search = array_merge( $search, [
			'%user_id%',
			'%user_login%',
			'%user_email%',
			'%display_name%',
			'%first_name%',
			'%last_name%',
			'%user_role%',
		] );

		$replace = array_merge( $replace, [
			$user->ID,
			$user_login,
			$user_email,
			$display_name,
			$first_name,
			$last_name,
			$user_role,
		] );

		$name = str_replace( $search, $replace, $name );

		$user_id = $user->ID;

		//Check if %user_meta_{key}% is in the name template
		if ( preg_match_all( '/%user_meta_(.*?)%/', $name_template, $matches ) ) {
			foreach ( $matches[1] as $meta_key ) {
				$meta_key_trimmed = trim( $meta_key );
				$meta_value       = get_user_meta( $user_id, $meta_key_trimmed, true );

				$name_template = str_replace( '%user_meta_' . $meta_key_trimmed . '%', $meta_value, $name_template );
			}

			$name = $name_template;
		}


	}

	// Handle wc order data
	if ( ! empty( $data['wc_order'] ) ) {
		$order = $data['wc_order'];

		$order_id = $order->get_id();

		$order_date = $order->get_date_created()->date( 'Y-m-d' );

		$search = array_merge( $search, [
			'%wc_order_id%',
			'%wc_order_date%',
		] );

		$replace = array_merge( $replace, [
			$order_id,
			$order_date,
		] );

		$name = str_replace( $search, $replace, $name );

		//Check if %wc_order_meta_{key}% is in the name template
		if ( preg_match_all( '/%wc_order_meta_(.*?)%/', $name, $matches ) ) {

			foreach ( $matches[1] as $meta_key ) {
				$meta_key_trimmed = trim( $meta_key );
				$meta_value       = get_post_meta( $order_id, $meta_key_trimmed, true );

				$name = str_replace( '%wc_order_meta_' . $meta_key_trimmed . '%', $meta_value, $name );
			}

		}
	}

	// Handle wc product data
	if ( ! empty( $data['wc_product'] ) ) {
		$product = $data['wc_product'];

		$product_id            = $product->get_id();
		$product_name          = $product->get_name();
		$product_sku           = $product->get_sku();
		$product_slug          = $product->get_slug();
		$product_price         = $product->get_price();
		$product_sale_price    = $product->get_sale_price();
		$product_regular_price = $product->get_regular_price();
		$product_type          = $product->get_type();
		$product_status        = $product->get_status();

		$product_category_ids = $product->get_category_ids();
		$product_tag_ids      = $product->get_tag_ids();

		$product_categories = array();
		foreach ( $product_category_ids as $category_id ) {
			$term = get_term( $category_id, 'product_cat' );
			if ( ! is_wp_error( $term ) && $term ) {
				$product_categories[] = $term->name;
			}
		}

		$product_tags = array();
		foreach ( $product_tag_ids as $tag_id ) {
			$term = get_term( $tag_id, 'product_tag' );
			if ( ! is_wp_error( $term ) && $term ) {
				$product_tags[] = $term->name;
			}
		}

		$product_categories = implode( ', ', $product_categories );
		$product_tags       = implode( ', ', $product_tags );

		$search = array_merge( $search, [
			'%wc_product_id%',
			'%wc_product_name%',
			'%wc_product_sku%',
			'%wc_product_slug%',
			'%wc_product_price%',
			'%wc_product_sale_price%',
			'%wc_product_regular_price%',
			'%wc_product_categories%',
			'%wc_product_tags%',
			'%wc_product_type%',
			'%wc_product_status%',
		] );

		$replace = array_merge( $replace, [
			$product_id,
			$product_name,
			$product_sku,
			$product_slug,
			$product_price,
			$product_sale_price,
			$product_regular_price,
			$product_categories,
			$product_tags,
			$product_type,
			$product_status,
		] );

		$name = str_replace( $search, $replace, $name );

		//Check if %wc_product_meta_{key}% is in the name template
		if ( preg_match_all( '/%wc_product_meta_(.*?)%/', $name, $matches ) ) {
			foreach ( $matches[1] as $meta_key ) {
				$meta_key_trimmed = trim( $meta_key );
				$meta_value       = get_post_meta( $product_id, $meta_key_trimmed, true );

				$name = str_replace( '%wc_product_meta_' . $meta_key_trimmed . '%', $meta_value, $name );
			}

		}

	}

	// Handle extra tag values
	if ( ! empty( $extra_tag_values ) ) {
		$name = str_replace( array_keys( $extra_tag_values ), array_values( $extra_tag_values ), $name );
	}

	return $name;
}

function igd_get_active_account_id( $user_id = null ) {
	$active_account = Account::instance( $user_id )->get_active_account();

	return ! empty( $active_account ) ? $active_account['id'] : null;
}

function igd_server_throttle( $value = '' ) {
	$value = $value ?: igd_get_settings( 'serverThrottle', 'off' );

	if ( $value === 'off' ) {
		return;
	}

	$throttle_times = [
		'low'    => 50 * 1000,
		'medium' => 200 * 1000,
		'high'   => 1000 * 1000,
	];

	usleep( $throttle_times[ $value ] ?? 0 );
}

function igd_get_transient_keys_with_prefix( $prefix ) {
	global $wpdb;

	$prefix = $wpdb->esc_like( '_transient_' . $prefix );
	$sql    = "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '%s'";
	$keys   = $wpdb->get_results( $wpdb->prepare( $sql, $prefix . '%' ), ARRAY_A );

	if ( is_wp_error( $keys ) ) {
		return [];
	}

	return array_map( function ( $key ) {
		// Remove '_transient_' from the option name.
		return substr( $key['option_name'], strlen( '_transient_' ) );
	}, $keys );
}

function igd_delete_transients_with_prefix( $prefix ) {
	foreach ( igd_get_transient_keys_with_prefix( $prefix ) as $key ) {
		delete_transient( $key );
	}
}

function igd_get_grouped_parent_folders( $file, &$groupedFolders = [] ) {

	if ( empty( $file ) ) {
		return $groupedFolders;
	}

	$app = App::instance( $file['accountId'] );

	// Check if file has parents
	if ( ! empty( $file['parents'] ) ) {
		foreach ( $file['parents'] as $parent_id ) {
			$parent_folder = $app->get_file_by_id( $parent_id );

			// Check if retrieved parent folder is indeed a directory
			if ( igd_is_dir( $parent_folder ) ) {

				// Initialize group for this parent if it doesn't exist
				if ( ! isset( $groupedFolders[ $parent_id ] ) ) {
					$groupedFolders[ $parent_id ] = [];
				}

				// Add to group if not already added
				if ( ! in_array( $parent_folder, $groupedFolders[ $parent_id ] ) ) {
					$groupedFolders[ $parent_id ]['folder']   = $parent_folder;
					$groupedFolders[ $parent_id ]['children'] = array_filter( igd_get_child_items( $parent_folder ), 'igd_is_dir' );
				}

				// Recursively get parents of the parent folder
				igd_get_grouped_parent_folders( $parent_folder, $groupedFolders );

			}
		}
	}

	return $groupedFolders;
}

function igd_delete_thumbnail_cache() {

	if ( is_dir( IGD_CACHE_DIR ) ) {
		array_map( 'unlink', glob( "IGD_CACHE_DIR/*.*" ) );
	}
}

function igd_sort_files( $files, $sort = [] ) {
	if ( empty( $files ) || ! is_array( $files ) ) {
		return [];
	}

	// Default sort settings
	$sort = wp_parse_args( $sort, [
		'sortBy'        => 'name',
		'sortDirection' => 'desc',
	] );

	$sort_by        = $sort['sortBy'];
	$sort_direction = strtolower( $sort['sortDirection'] ) === 'asc' ? SORT_ASC : SORT_DESC;
	$is_random      = ( $sort_by === 'random' );

	// Random sort (simple and fast)
	if ( $is_random ) {
		shuffle( $files );

		return $files;
	}

	// Normalize file attributes and prepare for sorting
	foreach ( $files as $key => &$file ) {
		$file['isFolder'] = igd_is_dir( $file );

		// Normalize missing fields
		if ( empty( $file[ $sort_by ] ) ) {
			$file[ $sort_by ] = '';
		}

		// Convert dates to timestamps for comparison
		if ( in_array( $sort_by, [ 'created', 'updated' ], true ) ) {
			$file[ $sort_by ] = strtotime( $file[ $sort_by ] );
		}
	}
	unset( $file );

	// Separate folders from files so folders always come first
	usort( $files, function ( $a, $b ) use ( $sort_by, $sort_direction ) {
		// Folders always come before files
		if ( $a['isFolder'] && ! $b['isFolder'] ) {
			return - 1;
		}
		if ( ! $a['isFolder'] && $b['isFolder'] ) {
			return 1;
		}

		// Compare values naturally (case-insensitive)
		$result = strnatcasecmp( (string) $a[ $sort_by ], (string) $b[ $sort_by ] );

		return $sort_direction === SORT_ASC ? $result : - $result;
	} );

	return array_values( $files );
}

function igd_get_referrer() {
	$url = '';

	if ( isset( $_REQUEST['page_url'] ) ) {
		$url = filter_var( $_REQUEST['page_url'], FILTER_SANITIZE_URL );
	} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$url = $_SERVER['HTTP_REFERER'];
	}

	$sanitizedUrl = preg_replace( '/[^\P{C}\n\t ]+/u', '', $url ); // Remove non-printable chars
	$sanitizedUrl = str_replace( [ "\r", "\n" ], [ '\r', '\n' ], $sanitizedUrl ); // Escape newlines

	return esc_url( $sanitizedUrl, null, 'db' );
}

function igd_get_module_types( $type = '' ) {
	$types = [
		'browser' => [
			'title'       => __( 'File Browser', 'integrate-google-drive' ),
			'description' => __( 'Allow users to browse selected Google Drive files and folders directly on your site.', 'integrate-google-drive' ),
			'isPro'       => true,
		],

		'gallery' => [
			'title'       => __( 'Gallery', 'integrate-google-drive' ),
			'description' => __( 'Showcase images and videos in a responsive masonry grid with lightbox previews.', 'integrate-google-drive' ),
		],

		'review' => [
			'title'       => __( 'Review & Approve', 'integrate-google-drive' ),
			'description' => __( 'Allow users to review, select, and confirm their Google Drive file choices.', 'integrate-google-drive' ),
			'isPro'       => true,
		],

		'uploader' => [
			'title'       => __( 'File Uploader', 'integrate-google-drive' ),
			'description' => __( 'Let users upload files directly to a specific Google Drive folder.', 'integrate-google-drive' ),
			'isPro'       => true,
		],

		'media' => [
			'title'       => __( 'Media Player', 'integrate-google-drive' ),
			'description' => __( 'Stream audio and video files from Google Drive using a built-in media player.', 'integrate-google-drive' ),
			'isPro'       => true,
		],

		'search' => [
			'title'       => __( 'Search Box', 'integrate-google-drive' ),
			'description' => __( 'Enable users to quickly search files and folders within your connected Google Drive.', 'integrate-google-drive' ),
			'isPro'       => true,
		],

		'embed' => [
			'title'       => __( 'Embed Documents', 'integrate-google-drive' ),
			'description' => __( 'Easily embed Google Drive documents into your content.', 'integrate-google-drive' ),
		],

		'list' => [
			'title'       => __( 'List', 'integrate-google-drive' ),
			'description' => __( 'List the Google Drive files with view and download options', 'integrate-google-drive' ),
		],

		'slider' => [
			'title'       => __( 'Slider', 'integrate-google-drive' ),
			'description' => __( 'Display images, videos, and documents in a smooth, touch-friendly carousel slider.', 'integrate-google-drive' ),
			'isPro'       => true,
		],
	];

	if ( $type ) {
		return isset( $types[ $type ] ) ? $types[ $type ] : [];
	}

	return $types;
}


