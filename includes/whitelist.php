<?php
/**
 * Sentruo — request allow-lists.
 *
 * Every user-influenced value that selects a file to include or a controller
 * branch to run is validated here against an explicit allow-list. This is the
 * primary fix for the unauthenticated LFI (VAPT F-01): no include path is ever
 * derived from unvalidated input.
 */

// Always loaded via require_once (from includes/functions.php). Do not require()
// this file directly elsewhere.
define('SM_WHITELIST_LOADED', true);

/** Routes that render without the app chrome and skip the auth gate. */
function sm_public_routes() {
    return ['signin', 'forgot', 'publicpage'];
}

/** Routes handled entirely by a controller (no page template of their own). */
function sm_virtual_routes() {
    return ['signout'];
}

/**
 * A route is valid when it is a known virtual/public route, or it maps to a real
 * file under template/pages/ . The regex forbids traversal, absolute paths and
 * unexpected characters; realpath containment is the backstop.
 */
function sm_valid_route($route, $appRoot) {
    if (!is_string($route) || $route === '') return false;
    if (in_array($route, sm_virtual_routes(), true)) return true;
    if (in_array($route, sm_public_routes(), true)) return true;
    if (!preg_match('#^[a-z][a-z0-9]*(?:/[a-z0-9][a-z0-9-]*){0,2}$#i', $route)) return false;

    $base = realpath($appRoot . '/template/pages');
    $real = realpath($appRoot . '/template/pages/' . $route . '.php');
    return $base !== false && $real !== false
        && strncmp($real, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) === 0;
}

/**
 * A modal id is "section/name" mapping to a real file under template/modals/ .
 */
function sm_valid_modal($modal, $appRoot) {
    if (!is_string($modal) || $modal === '') return false;
    if (!preg_match('#^[a-z][a-z0-9]*/[a-zA-Z][a-zA-Z0-9-]*$#', $modal)) return false;

    $base = realpath($appRoot . '/template/modals');
    if ($base === false) return false;
    foreach (['.php', '.html'] as $ext) {
        $real = realpath($appRoot . '/template/modals/' . $modal . $ext);
        if ($real !== false && strncmp($real, $base . DIRECTORY_SEPARATOR, strlen($base) + 1) === 0) {
            return $ext;
        }
    }
    return false;
}

/** Quick-action ids accepted by includes/controllers/quickactions.php. */
function sm_valid_qa($qa) {
    return in_array($qa, ['setAutorefresh', 'removeAvatar', 'removeLogo', 'removeFavicon', 'verifyLicense'], true);
}

/** JSON datasource ids accepted by includes/controllers/json.php. */
function sm_valid_json($json) {
    return in_array($json, [
        'servers', 'websites', 'checks', 'domains', 'ssl',
        'alertinglog', 'activitylog', 'emaillog', 'smslog', 'cronlog',
        'logsources', 'logsearch', 'logtail', 'loghistogram',
    ], true);
}

/** POST action ids accepted by includes/controllers/actions.php. */
function sm_valid_action($action) {
    static $actions = [
        'setRange', 'resetRange',
        'addServer', 'editServer', 'deleteServer', 'addServerAlert', 'editServerAlert',
        'deleteServerAlert', 'markServerIncident', 'editServerIncidentComment',
        'addWebsite', 'editWebsite', 'deleteWebsite', 'addWebsiteAlert', 'editWebsiteAlert',
        'deleteWebsiteAlert', 'markWebsiteIncident', 'editWebsiteIncidentComment',
        'addCheck', 'editCheck', 'deleteCheck', 'addCheckAlert', 'editCheckAlert',
        'deleteCheckAlert', 'markCheckIncident', 'editCheckIncidentComment',
        'addDomain', 'editDomain', 'deleteDomain', 'addDomainAlert', 'editDomainAlert',
        'deleteDomainAlert', 'markDomainIncident', 'editDomainIncidentComment',
        'addSsl', 'editSsl', 'deleteSsl', 'addSslAlert', 'editSslAlert',
        'deleteSslAlert', 'markSslIncident', 'editSslIncidentComment',
        'addContact', 'editContact', 'deleteContact',
        'addPage', 'editPage', 'deletePage',
        'addUser', 'editUser', 'deleteUser',
        'addRole', 'editRole', 'deleteRole',
        'addGroup', 'editGroup', 'deleteGroup',
        'addLanguage', 'deleteLanguage',
        'editProfile',
        'generalSettings', 'monitoringSettings', 'localisationSettings',
        'emailSettings', 'smsSettings', 'twitterSettings', 'pushoverSettings',
        'licenseSettings',
        'editNotification', 'testEmailSettings',
        'addLogSource', 'editLogSource', 'deleteLogSource',
        'addLogAlert', 'editLogAlert', 'deleteLogAlert',
        'markLogIncident', 'editLogIncidentComment',
    ];
    return in_array($action, $actions, true);
}
