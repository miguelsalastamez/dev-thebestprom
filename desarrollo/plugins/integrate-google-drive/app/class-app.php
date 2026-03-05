<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

/**
 * Class App
 *
 * Handles Google Drive API interactions and file operations.
 * Manages file retrieval, search, CRUD operations, and caching.
 *
 * @package IGD
 */
class App {

    /**
     * Singleton instance
     *
     * @var App|null
     */
    public static $instance = null;

    /**
     * Google API client
     *
     * @var object
     */
    public $client;

    /**
     * Google Drive service instance
     *
     * @var \IGDGoogle_Service_Drive
     */
    public $service;

    /**
     * Current account ID
     *
     * @var string|null
     */
    public $account_id = null;

    /**
     * Fields to retrieve for single file
     *
     * @var string
     */
    public $file_fields = 'capabilities(canEdit,canRename,canDelete,canShare,canTrash,canMoveItemWithinDrive),shared,starred,sharedWithMeTime,description,fileExtension,iconLink,id,driveId,imageMediaMetadata(height,rotation,width,time),mimeType,createdTime,modifiedTime,name,ownedByMe,parents,size,thumbnailLink,trashed,videoMediaMetadata(height,width,durationMillis),webContentLink,webViewLink,exportLinks,permissions(id,type,role,domain),copyRequiresWriterPermission,shortcutDetails,resourceKey';

    /**
     * Fields to retrieve for file lists
     *
     * @var string
     */
    public $list_fields = 'files(capabilities(canEdit,canRename,canDelete,canShare,canTrash,canMoveItemWithinDrive),shared,starred,sharedWithMeTime,description,fileExtension,iconLink,id,driveId,imageMediaMetadata(height,rotation,width,time),mimeType,createdTime,modifiedTime,name,ownedByMe,parents,size,thumbnailLink,trashed,videoMediaMetadata(height,width,durationMillis),webContentLink,webViewLink,exportLinks,permissions(id,type,role,domain),copyRequiresWriterPermission,shortcutDetails,resourceKey),nextPageToken';

    /**
     * Constructor - Initialize Google Drive service.
     *
     * @param string|null $account_id The account ID to use.
     */
    public function __construct( $account_id = null ) {
        // Get active account if no account ID provided
        if ( empty( $account_id ) ) {
            $account    = Account::instance()->get_active_account();
            $account_id = ! empty( $account ) ? $account['id'] : $account_id;
        }

        $this->account_id = $account_id;

        // Initialize Google API client
        $this->client = Client::instance( $this->account_id )->get_client();

        // Load Drive service if not already loaded
        if ( ! class_exists( 'IGDGoogle_Service_Drive' ) ) {
            require_once IGD_PATH . '/vendors/Google-sdk/src/Google/Service/Drive.php';
        }

        $this->service = new \IGDGoogle_Service_Drive( $this->client );
    }

    /**
     * Get files from Google Drive folder.
     *
     * @param array $args Arguments for file retrieval (folder, sort, filters, etc).
     *
     * @return array Array containing files data and pagination info.
     */
    public function get_files( $args = [] ) {

        // Parse and validate arguments
        $args = $this->parse_get_files_args( $args );

        // Set default folder if needed
        if ( empty( $args['folder'] ) && ! $this->is_search_query( $args['q'] ) ) {
            $args['folder'] = [
                    'id'         => 'root',
                    'accountId'  => $this->account_id,
                    'pageNumber' => 1,
            ];
        }

        // Extract arguments
        $folder_id         = ! empty( $args['folder'] ) ? $args['folder']['id'] : '';
        $folder_account_id = ! empty( $args['folder']['accountId'] ) ? $args['folder']['accountId'] : $this->account_id;
        $limit             = ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 0;
        $page_number       = ! empty( $args['folder']['pageNumber'] ) ? intval( $args['folder']['pageNumber'] ) : 1;
        $filters           = ! empty( $args['filters'] ) ? $args['filters'] : [];
        $sort              = $args['sort'];

        $data = [ 'nextPageNumber' => 0 ];

        // Handle shortcut resolution
        if ( ! empty( $args['folder']['shortcutDetails'] ) ) {
            $folder_id = $args['folder']['shortcutDetails']['targetId'];
        }

        // Calculate pagination
        $start_index          = $page_number > 0 ? ( $page_number - 1 ) * $limit : 0;
        $files_number_to_show = ! empty( $args['fileNumbers'] ) ? $args['fileNumbers'] : 0;

        // Fetch or retrieve files from cache
        if ( $args['from_server'] || ! igd_is_cached_folder( $folder_id, $folder_account_id ) ) {
            $files = $this->fetch_files_from_server( $folder_id, $folder_account_id, $args );

            if ( empty( $files ) ) {
                $data['files'] = [];

                return $data;
            }

            // Process and cache files
            $this->insert_files( $files, $folder_id, $folder_account_id );
        }

        // Retrieve from cache
        list( $files, $count ) = Files::get( $folder_id, $folder_account_id, $start_index, $limit, $filters, $sort );
        $data['count'] = $count;

        // Apply pagination
        if ( ! empty( $limit ) && ! empty( $data['count'] ) ) {
            if ( $data['count'] > $limit ) {
                $files                  = array_slice( $files, $start_index, $limit );
                $data['nextPageNumber'] = $page_number + 1;
            }
        }

        // Apply file number limit
        if ( $files_number_to_show > 0 ) {
            $data = $this->apply_file_number_limit( $data, $files, $page_number, $limit, $files_number_to_show );
        }

        $data['files'] = array_values( $files );

        return $data;
    }

    /**
     * Parse and validate get_files arguments.
     *
     * @param array $args Raw arguments.
     *
     * @return array Validated arguments.
     */
    private function parse_get_files_args( $args ) {
        $default_args = [
                'folder'      => [],
                'sort'        => [ 'sortBy' => 'name', 'sortDirection' => 'asc' ],
                'from_server' => false,
                'orderBy'     => 'folder,name',
                'filters'     => [],
                'q'           => '',
        ];

        return wp_parse_args( $args, $default_args );
    }

    /**
     * Fetch files from Google Drive server.
     *
     * @param string $folder_id The folder ID to fetch from.
     * @param string $folder_account_id The account ID for the folder.
     * @param array $args Request arguments.
     *
     * @return array Array of files from server.
     */
    private function fetch_files_from_server( $folder_id, $folder_account_id, $args ) {
        // Handle shared drives
        if ( 'shared-drives' === $folder_id ) {
            return $this->get_shared_drives( $folder_account_id );
        }

        // Build query parameters
        $params = $this->build_list_params( $folder_id, $args );
        $files  = [];

        // Paginate through all results
        do {
            try {
                $response            = $this->service->files->listFiles( $params );
                $page_token          = ! empty( $response->getNextPageToken() ) ? $response->getNextPageToken() : '';
                $params['pageToken'] = $page_token;

                $items = $response->getFiles();

                if ( ! empty( $items ) ) {
                    foreach ( $items as $item ) {
                        $files[] = igd_file_map( $item, $folder_account_id );
                    }
                }

            } catch ( \Exception $e ) {
                error_log( 'IGD: Failed to fetch files from server - ' . $e->getMessage() );

                return [];
            }
        } while ( ! empty( $page_token ) );

        return $files;
    }

    /**
     * Build parameters for files list API call.
     *
     * @param string $folder_id The folder ID.
     * @param array $args Request arguments.
     *
     * @return array API parameters.
     */
    private function build_list_params( $folder_id, $args ) {
        $params = [
                'fields'                    => $this->list_fields,
                'pageSize'                  => 300,
                'orderBy'                   => ! empty( $args['orderBy'] ) ? $args['orderBy'] : '',
                'pageToken'                 => '',
                'supportsAllDrives'         => true,
                'includeItemsFromAllDrives' => true,
                'corpora'                   => 'allDrives',
        ];

        // Build query based on folder ID
        $params['q'] = $this->build_query( $folder_id, $args['q'] );

        return $params;
    }

    /**
     * Build Google Drive query string.
     *
     * @param string $folder_id The folder ID.
     * @param string $search_q Optional search query.
     *
     * @return string Query string for API.
     */
    private function build_query( $folder_id, $search_q = '' ) {
        if ( ! empty( $search_q ) ) {
            return $search_q;
        }

        // Query mapping for special folders
        $query_map = [
                'computers' => "'me' in owners and mimeType='application/vnd.google-apps.folder' and trashed=false",
                'shared'    => 'sharedWithMe=true and trashed=false',
                'starred'   => 'starred=true and trashed=false',
        ];

        if ( isset( $query_map[ $folder_id ] ) ) {
            return $query_map[ $folder_id ];
        }

        return "trashed=false and '$folder_id' in parents";
    }

    /**
     * Apply file number limit to results.
     *
     * @param array $data Result data.
     * @param array $files Files array.
     * @param int $page_number Current page number.
     * @param int $limit Results per page.
     * @param int $files_number_to_show Max files to show.
     *
     * @return array Updated data.
     */
    private function apply_file_number_limit( $data, &$files, $page_number, $limit, $files_number_to_show ) {
        if ( ( $page_number * $limit ) > $files_number_to_show ) {
            $files_remaining        = $files_number_to_show - ( ( $page_number - 1 ) * $limit );
            $files                  = array_slice( $files, 0, $files_remaining );
            $data['nextPageNumber'] = 0;
        }

        if ( $data['count'] > $files_number_to_show ) {
            $data['count'] = $files_number_to_show;
        }

        return $data;
    }

    /**
     * Insert and process files for caching.
     *
     * Filters, reformats, and caches files from the server.
     *
     * @param array $files Files to process.
     * @param string $folder_id Parent folder ID.
     * @param string $folder_account_id Account ID for the folder.
     *
     * @return array Processed files.
     */
    public function insert_files( $files, $folder_id, $folder_account_id ) {
        // Filter computers folder files
        $files = $this->filter_computers_folder( $files, $folder_id );

        // Reformat shortcuts
        $files = $this->reformat_shortcuts( $files );

        // Cache files if folder ID exists
        if ( $folder_id ) {
            Files::set( $files, $folder_id );
            igd_update_cached_folders( $folder_id, $folder_account_id );
        }

        return $files;
    }

    /**
     * Filter files for computers folder.
     *
     * @param array $files Files to filter.
     * @param string $folder_id Folder ID.
     *
     * @return array Filtered files.
     */
    private function filter_computers_folder( $files, $folder_id ) {
        if ( 'computers' !== $folder_id ) {
            return $files;
        }

        return array_filter( $files, function ( $file ) {
            return empty( $file['parents'] ) && empty( $file['shared'] );
        } );
    }

    /**
     * Reformat shortcuts with metadata from original files.
     *
     * Adds iconLink, thumbnailLink, and metadata to shortcuts
     * from their target files.
     *
     * @param array $files Files to reformat.
     *
     * @return array Reformatted files.
     */
    public function reformat_shortcuts( $files ) {
        array_walk( $files, function ( &$file ) {
            // Skip if not a shortcut or if it's a directory
            if ( empty( $file['shortcutDetails'] ) || igd_is_dir( $file ) ) {
                return;
            }

            // Get original file data
            $original_file = $this->get_file_by_id( $file['shortcutDetails']['targetId'] );

            if ( ! $original_file ) {
                return;
            }

            // Copy metadata from original file
            $file['iconLink']      = $original_file['iconLink'] ?? null;
            $file['thumbnailLink'] = $original_file['thumbnailLink'] ?? null;

            if ( ! empty( $original_file['metaData'] ) ) {
                $file['metaData'] = $original_file['metaData'];
            }
        } );

        return $files;
    }

    public function get_shared_drives( $folder_account_id = null ) {

        $params = [
                'fields'    => 'kind,nextPageToken,drives(kind,id,name,capabilities,backgroundImageFile,backgroundImageLink,createdTime,hidden)',
                'pageSize'  => 100,
                'pageToken' => '',
        ];

        // Get all files in folder
        $files = [];

        do {
            try {
                $response            = $this->service->drives->listDrives( $params );
                $items               = $response->getDrives();
                $page_token          = ! empty( $response->getNextPageToken() ) ? $response->getNextPageToken() : '';
                $params['pageToken'] = $page_token;

                if ( ! empty( $items ) ) {

                    foreach ( $items as $drive ) {
                        $file = igd_drive_map( $drive, $folder_account_id );

                        $files[] = $file;
                    }
                }

            } catch ( \Exception $ex ) {
                error_log( $ex->getMessage() );

                return [];
            }
        } while ( ! empty( $page_token ) );


        return $files;

    }

    /**
     * Search files across specified folders.
     *
     * @param array $posted Search parameters (folders, keyword, sort, filters).
     *
     * @return array Array of found files.
     */
    public function get_search_files( array $posted = [] ): array {
        // Extract and sanitize posted data
        $folders          = $posted['folders'] ?? [];
        $keyword          = ! empty( $posted['keyword'] ) ? stripslashes( $posted['keyword'] ) : '';
        $sort             = $posted['sort'] ?? [];
        $full_text_search = filter_var( $posted['fullTextSearch'] ?? true, FILTER_VALIDATE_BOOLEAN );
        $filters          = $posted['filters'] ?? [];

        // Get valid search folders
        $look_in_to = $this->get_search_folder_ids( $folders );

        // Log the search
        do_action( 'igd_insert_log', [
                'type'    => 'search',
                'keyword' => $keyword,
                'account' => $this->account_id,
        ] );

        // Build search query
        $query = $this->build_search_query( $keyword, $full_text_search );

        // Build arguments for file search
        $args = [
                'fields'      => $this->list_fields,
                'pageSize'    => 1000,
                'orderBy'     => $full_text_search ? '' : 'folder,name',
                'q'           => $query,
                'from_server' => true,
                'sort'        => $sort ?: [ 'sortBy' => 'name', 'sortDirection' => 'asc' ],
                'filters'     => $filters,
        ];

        // Fetch files
        $data = $this->get_files( $args );

        // Filter files by folder parents if necessary
        if ( ! empty( $look_in_to ) ) {
            $data['files'] = $this->filter_search_results( $data['files'] ?? [], $look_in_to );
        }

        return $data;
    }

    /**
     * Get valid folder IDs for search from provided folders.
     *
     * @param array $folders Folders to search in.
     *
     * @return array Array of valid folder IDs.
     */
    private function get_search_folder_ids( $folders ) {
        $look_in_to = [];

        if ( empty( $folders ) ) {
            return $look_in_to;
        }

        foreach ( $folders as $key => $folder ) {
            // Skip invalid or unsupported folders
            if ( $this->is_invalid_search_folder( $folder ) ) {
                continue;
            }

            // Resolve shortcut folder target ID
            if ( ! empty( $folder['shortcutDetails'] ) ) {
                $folder_id       = $folder['shortcutDetails']['targetId'];
                $folder          = $this->get_file_by_id( $folder_id );
                $folders[ $key ] = $folder;
            }

            if ( ! $folder ) {
                continue;
            }

            $look_in_to[] = $folder['id'];

            // Add child folder IDs
            $child_folders = igd_get_all_child_folders( $folder );
            if ( ! empty( $child_folders ) ) {
                $look_in_to = array_merge( $look_in_to, wp_list_pluck( $child_folders, 'id' ) );
            }
        }

        return $look_in_to;
    }

    /**
     * Check if folder is invalid for search.
     *
     * @param array $folder Folder to check.
     *
     * @return bool True if folder is invalid for search.
     */
    private function is_invalid_search_folder( $folder ) {
        $invalid_ids = [ 'root', 'computers', 'shared-drives', 'shared', 'starred' ];

        if ( in_array( $folder['id'], $invalid_ids, true ) ) {
            return true;
        }

        if ( ! empty( $folder['parents'] ) && in_array( 'shared-drives', $folder['parents'], true ) ) {
            return true;
        }

        if ( ! igd_is_dir( $folder ) ) {
            return true;
        }

        return false;
    }

    /**
     * Build search query based on search parameters.
     *
     * @param string $keyword Search keyword.
     * @param bool $full_text_search Whether to use full text search.
     *
     * @return string Search query for API.
     */
    private function build_search_query( $keyword, $full_text_search ) {
        return $full_text_search
                ? "fullText contains '{$keyword}' and trashed = false"
                : "name contains '{$keyword}' and trashed = false";
    }

    /**
     * Filter search results by folder parent IDs.
     *
     * @param array $files Files to filter.
     * @param array $look_in_to Allowed parent folder IDs.
     *
     * @return array Filtered files.
     */
    private function filter_search_results( $files, $look_in_to ) {
        return array_values( array_filter( $files, function ( $file ) use ( $look_in_to ) {
            return ! empty( $file['parents'] ) && in_array( $file['parents'][0], $look_in_to, true );
        } ) );
    }

    public function is_search_query( $args ) {
        if ( empty( $args['q'] ) ) {
            return false;
        }

        $keyword = $args['q'];

        if ( strpos( $keyword, 'fullText contains' ) !== false ) {
            return true;
        }

        if ( strpos( $keyword, 'name contains' ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Get file by ID from cache or server.
     *
     * @param string $id File ID.
     * @param bool $from_server Force fetching from server.
     *
     * @return array|false File data or false if not found.
     */
    public function get_file_by_id( $id, $from_server = false ) {
        // Get cached file
        if ( ! $from_server ) {
            $file = Files::get_file_by_id( $id );
        }

        // If no cached file or forcing server fetch
        if ( empty( $file ) || $from_server ) {
            $file = $this->fetch_file_from_server( $id );
        }

        return $file;
    }

    /**
     * Fetch a single file from Google Drive server.
     *
     * @param string $id File ID.
     *
     * @return array|false File data or false if error.
     */
    private function fetch_file_from_server( $id ) {
        try {
            $item = $this->service->files->get( $id, [
                    'supportsAllDrives' => true,
                    'fields'            => $this->file_fields,
            ] );

            // Check if file exists and is not trashed
            if ( ! $this->is_valid_file( $item ) ) {
                do_action( 'igd_trash_detected', $id, $this->account_id );

                return false;
            }

            // Map and cache file
            $file = igd_file_map( $item, $this->account_id );
            Files::add_file( $file );

            return $file;

        } catch ( \Exception $e ) {
            error_log( 'IGD SDK ERROR - GET FILE BY ID: ' . $e->getMessage() );

            return false;
        }
    }

    /**
     * Check if file object is valid (exists and not trashed).
     *
     * @param object $item File object from API.
     *
     * @return bool True if file is valid.
     */
    private function is_valid_file( $item ) {
        if ( ! is_object( $item ) || ! method_exists( $item, 'getId' ) ) {
            return false;
        }

        if ( empty( $item->getId() ) ) {
            return false;
        }

        if ( $item->trashed ) {
            return false;
        }

        return true;
    }

    /**
     * Get file by name from cache or server.
     *
     * @param string $name File name.
     * @param string|array $parent_folder Parent folder ID or array.
     * @param bool $from_server Force fetching from server.
     *
     * @return array|false File data or false if not found.
     */
    public function get_file_by_name( $name, $parent_folder = '', $from_server = false ) {
        $folder_id = isset( $parent_folder['id'] ) ? $parent_folder['id'] : $parent_folder;

        $file = ! $from_server ? Files::get_file_by_name( $name, $folder_id ) : null;

        if ( empty( $file ) || $from_server ) {
            $file = $this->fetch_file_by_name_from_server( $name, $folder_id );
        }

        return $file;
    }

    /**
     * Fetch file by name from Google Drive server.
     *
     * @param string $name File name.
     * @param string $folder_id Parent folder ID.
     *
     * @return array|false File data or false if not found.
     */
    private function fetch_file_by_name_from_server( $name, $folder_id ) {
        $args = [
                'fields'            => $this->list_fields,
                'supportsAllDrives' => true,
                'pageSize'          => 1,
                'q'                 => "name = '{$name}' and trashed = false",
        ];

        if ( ! empty( $folder_id ) ) {
            $args['q'] .= " and '{$folder_id}' in parents";
        }

        try {
            $response = $this->service->files->listFiles( $args );

            if ( ! method_exists( $response, 'getFiles' ) ) {
                return false;
            }

            $files = $response->getFiles();

            if ( empty( $files ) ) {
                return false;
            }

            $item = $files[0];

            // Check if file is in trash
            if ( $item->trashed ) {
                do_action( 'igd_trash_detected', $item->id, $this->account_id );

                return false;
            }

            $file = igd_file_map( $item, $this->account_id );
            Files::add_file( $file );

            return $file;

        } catch ( \Exception $e ) {
            error_log( 'IGD SDK ERROR - GET FILE BY NAME: ' . $e->getMessage() );

            return false;
        }
    }

    /**
     * Create a new folder in Google Drive.
     *
     * @param string $folder_name Name for the new folder.
     * @param string $parent_id Parent folder ID (defaults to root).
     *
     * @return array|string Newly created folder data or error message.
     */
    public function new_folder( $folder_name, $parent_id ) {
        if ( empty( $parent_id ) ) {
            $parent_id = 'root';
        }

        try {
            $params = [
                    'fields'            => $this->file_fields,
                    'supportsAllDrives' => true,
            ];

            $folder_metadata = new \IGDGoogle_Service_Drive_DriveFile( [
                    'name'     => $folder_name,
                    'parents'  => [ $parent_id ],
                    'mimeType' => 'application/vnd.google-apps.folder',
            ] );

            $response = $this->service->files->create( $folder_metadata, $params );

            // Map and cache new folder
            $item = igd_file_map( $response, $this->account_id );
            Files::add_file( $item, $parent_id );

            // Insert log
            do_action( 'igd_insert_log', [
                    'type'      => 'folder',
                    'file_id'   => $item['id'],
                    'file_name' => $item['name'],
                    'file_type' => $item['type'],
                    'account'   => $this->account_id,
            ] );

            return $item;

        } catch ( \Exception $e ) {
            error_log( 'IGD SDK ERROR - NEW FOLDER: ' . $e->getMessage() );

            return sprintf( 'An error occurred: %s', $e->getMessage() );
        }
    }

    /**
     * Move files to a new parent folder.
     *
     * @param array $file_ids Array of file IDs to move.
     * @param string $new_parent_id New parent folder ID (defaults to root).
     *
     * @return string|void Error message or void on success.
     */
    public function move_file( $file_ids, $new_parent_id = null ) {
        if ( empty( $new_parent_id ) ) {
            $new_parent_id = 'root';
        }

        if ( empty( $file_ids ) || ! is_array( $file_ids ) ) {
            return 'No files to move.';
        }

        try {
            $empty_metadata = new \IGDGoogle_Service_Drive_DriveFile();

            foreach ( $file_ids as $file_id ) {
                $this->move_single_file( $file_id, $new_parent_id, $empty_metadata );
            }

        } catch ( \Exception $e ) {
            return sprintf( 'An error occurred: %s', $e->getMessage() );
        }
    }

    /**
     * Move a single file to a new parent folder.
     *
     * @param string $file_id File ID.
     * @param string $new_parent_id New parent folder ID.
     * @param \IGDGoogle_Service_Drive_DriveFile $empty_metadata Empty metadata object.
     */
    private function move_single_file( $file_id, $new_parent_id, $empty_metadata ) {
        // Retrieve the existing parents
        $file = $this->get_file_by_id( $file_id );

        if ( ! $file ) {
            return;
        }

        $previous_parents = join( ',', $file['parents'] );

        // Move the file to the new folder
        $response = $this->service->files->update( $file_id, $empty_metadata, [
                'addParents'        => $new_parent_id,
                'supportsAllDrives' => true,
                'removeParents'     => $previous_parents,
                'fields'            => $this->file_fields,
        ] );

        // Update cached file
        if ( method_exists( $response, 'getId' ) ) {
            Files::update_file(
                    [
                            'parent_id' => $new_parent_id,
                            'data'      => serialize( igd_file_map( $response, $this->account_id ) ),
                    ],
                    [ 'id' => $file_id ]
            );

            // Insert log
            do_action( 'igd_insert_log', [
                    'type'      => 'move',
                    'file_id'   => $file_id,
                    'file_name' => $response->getName(),
                    'file_type' => $response->getMimeType(),
                    'account'   => $this->account_id,
            ] );
        }
    }

    /**
     * Rename a file.
     *
     * @param string $name New file name.
     * @param string $file_id File ID to rename.
     *
     * @return \IGDGoogle_Http_Request|\IGDGoogle_Service_Drive_DriveFile|string Updated file or error message.
     */
    public function rename( $name, $file_id ) {
        try {
            $file_metadata = new \IGDGoogle_Service_Drive_DriveFile();
            $file_metadata->setName( $name );

            // Update file name in Google Drive
            $response = $this->service->files->update( $file_id, $file_metadata, [
                    'fields'            => $this->file_fields,
                    'supportsAllDrives' => true,
            ] );

            // Update cached file
            if ( method_exists( $response, 'getId' ) ) {
                Files::update_file( [
                        'name' => $name,
                        'data' => serialize( igd_file_map( $response, $this->account_id ) ),
                ], [ 'id' => $file_id ] );

                // Insert log
                do_action( 'igd_insert_log', [
                        'type'      => 'rename',
                        'file_id'   => $file_id,
                        'file_name' => $name,
                        'file_type' => $response->getMimeType(),
                        'account'   => $this->account_id,
                ] );
            }

            return $response;

        } catch ( \Exception $e ) {
            return sprintf( 'An error occurred: %s', $e->getMessage() );
        }
    }

    /**
     * Rename multiple files on form submit
     *
     * @param $files
     *
     * @return array|string
     */
    public function rename_files( $files ) {

        try {
            $this->client->setUseBatch( true );
            $batch = new \IGDGoogle_Http_Batch( $this->client );

            foreach ( $files as $file ) {
                $name    = $file['name'];
                $file_id = $file['id'];


                $file_met_data = new \IGDGoogle_Service_Drive_DriveFile();
                $file_met_data->setName( $name );

                // Move the file to the new folder
                $batch->add( $this->service->files->update( $file_id, $file_met_data, array(
                        'fields'            => $this->file_fields,
                        'supportsAllDrives' => true,
                ) ) );

            }

            $batch_result = $batch->execute();
            $this->client->setUseBatch( false );

            $renamed_files = [];
            foreach ( $batch_result as $file ) {
                if ( method_exists( $file, 'getId' ) ) {
                    $file = igd_file_map( $file, $this->account_id );

                    Files::update_file( [
                            'name' => $file['name'],
                            'data' => serialize( $file ),
                    ], [ 'id' => $file['id'] ] );

                    $renamed_files[] = $file;
                }
            }

            return $renamed_files;

        } catch ( \Exception $e ) {
            return "An error occurred: " . $e->getMessage();
        }

    }

    public function update_description( $file_id, $description ) {
        try {

            $file = new \IGDGoogle_Service_Drive_DriveFile();
            $file->setDescription( $description );

            // Move the file to the new folder
            $update_file = $this->service->files->update( $file_id, $file, array(
                    'fields'            => $this->file_fields,
                    'supportsAllDrives' => true,
            ) );

            // Insert log
            do_action( 'igd_insert_log', [
                    'type'      => 'description',
                    'file_id'   => $file_id,
                    'file_name' => $update_file->getName(),
                    'file_type' => $update_file->getMimeType(),
                    'account'   => $this->account_id,
            ] );

            // Update cached file
            if ( $update_file->getId() ) {
                $update_file = igd_file_map( $update_file, $this->account_id );

                Files::update_file( [ 'data' => serialize( $update_file ) ], [ 'id' => $file_id ] );

                return $update_file;
            }

        } catch ( \Exception $e ) {
            return "An error occurred: " . $e->getMessage();
        }
    }

    public function copy( $files, $parent_id = null ) {

        try {
            $this->client->setUseBatch( true );

            $batch          = new \IGDGoogle_Http_Batch( $this->client );
            $file_meta_data = new \IGDGoogle_Service_Drive_DriveFile();

            foreach ( $files as $file ) {

                $file_meta_data->setName( 'Copy of ' . $file['name'] );

                if ( ! empty( $parent_id ) ) {
                    $file_meta_data->setParents( [ $parent_id ] );
                }

                $batch->add( $this->service->files->copy( $file['id'], $file_meta_data, [
                        'fields'            => $this->file_fields,
                        'supportsAllDrives' => true,
                ] ) );
            }

            $batch_result = $batch->execute();

            $copied_files = [];
            foreach ( $batch_result as $file ) {
                if ( method_exists( $file, 'getId' ) ) {
                    $file = igd_file_map( $file, $this->account_id );
                    Files::add_file( $file );

                    $copied_files[] = $file;


                    // Insert log
                    do_action( 'igd_insert_log', [
                            'type'      => 'copy',
                            'file_id'   => $file['id'],
                            'file_name' => $file['name'],
                            'file_type' => $file['type'],
                            'account'   => $this->account_id,
                    ] );
                }
            }

            $this->client->setUseBatch( false );

            return $copied_files;

        } catch ( \Exception $e ) {
            $this->client->setUseBatch( false );

            return "An error occurred: " . $e->getMessage();
        }
    }

    /**
     * Delete files (move to trash).
     *
     * @param array $file_ids Array of file IDs to delete.
     *
     * @return string|void Error message or void on success.
     */
    public function delete( $file_ids ) {
        try {
            // Validate input
            if ( empty( $file_ids ) || ! is_array( $file_ids ) ) {
                return 'No files to delete.';
            }

            $this->client->setUseBatch( true );
            $batch = new \IGDGoogle_Http_Batch( $this->client );

            foreach ( $file_ids as $file_id ) {
                // Get file info before deleting
                $file = self::instance( $this->account_id )->get_file_by_id( $file_id );

                // Log the deletion
                do_action( 'igd_insert_log', [
                        'type'      => 'delete',
                        'file_id'   => $file_id,
                        'file_name' => ! empty( $file['name'] ) ? $file['name'] : '',
                        'file_type' => ! empty( $file['type'] ) ? $file['type'] : '',
                        'account'   => $this->account_id,
                ] );

                // Remove from cache
                Files::delete( [ 'id' => $file_id ] );

                // Trigger deletion hook
                do_action( 'igd_delete_file', $file_id, $this->account_id );

                // Mark file as trashed in Google Drive
                $trashed_file = new \IGDGoogle_Service_Drive_DriveFile();
                $trashed_file->setTrashed( true );

                $batch->add( $this->service->files->update( $file_id, $trashed_file, [ 'supportsAllDrives' => true ] ), $file_id );
            }

            $batch->execute();
            $this->client->setUseBatch( false );

        } catch ( \Exception $e ) {
            return sprintf( 'An error occurred: %s', $e->getMessage() );
        }
    }

    /**
     * Get singleton instance of the App class.
     *
     * @param string|null $account_id The account ID to use.
     *
     * @return App The singleton instance.
     */
    public static function instance( $account_id = null ) {
        if ( is_null( self::$instance ) || self::$instance->account_id !== $account_id ) {
            self::$instance = new self( $account_id );
        }

        return self::$instance;
    }

    /**
     * Render the File Browser interface.
     */
    public static function view() {
        ?>
        <div id="igd-app" class="igd-app"></div>
        <?php
    }

    /**
     * Get the Google Drive Service instance.
     *
     * @return \IGDGoogle_Service_Drive
     */
    public function getService() {
        return $this->service;
    }


}