<?php
namespace ECTREG;
class ECT_ApiConf{
    const PLUGIN_NAME = 'The Events Calendar - Shortcode And Templates Pro License';
    const PLUGIN_VERSION = ECT_PRO_VERSION;
    const PLUGIN_PREFIX = 'ect';
    const PLUGIN_AUTH_PAGE = 'cool-events-registration';
    const PLUGIN_URL = ECT_PRO_PLUGIN_URL;
}

    require_once 'class.settings-api.php';
    require_once 'ect-base.php';
    require_once 'api-auth-settings.php';

	new ECT_Settings();