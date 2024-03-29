<?php
/**
 * Dev: Bozhidar Slaveykov
 * Emai: bobi@microweber.com
 * Date: 11/18/2019
 * Time: 10:26 AM
 */

api_expose_admin('browser_redirect_delete_all', function($params) {
    DB::table('browser_redirects')->truncate();
    Cache::tags('browser_redirects')->flush();
});

api_expose_admin('browser_redirect/process_import_file', function($params) {

    $file = media_uploads_path() . '/'. $params['name'];
    $file = normalize_path($file, false);

    $rows = \MicroweberPackages\Backup\Exporters\SpreadsheetHelper::newSpreadsheet($file)->getRows();
    $linksForSave = [];
    if (!empty($rows)) {
        foreach ($rows as $row) {
            if (!isset($row[2]) || empty($row[0]) || empty($row[1]) || empty($row[2])) {
                continue;
            }
            $isValid = false;
            if ((strpos($row[0], 'Redirect') !==false) || (strpos($row[0], 'ErrorDocument') !==false)) {
                $isValid = true;
            }

            if ($isValid) {
                $linksForSave[] = [
                    'redirect_code' => $row[0],
                    'redirect_from_url' => $row[1],
                    'redirect_to_url' => $row[2],
                ];
            }
        }
    }

    if (empty($linksForSave)) {

        // Try to parse httacess
        $content = @file_get_contents($file);
        $expLines = explode(PHP_EOL, $content);
        foreach ($expLines as $line) {
            if (empty($line)) {
                continue;
            }

            $redirectCode = '301';

            $clearRedirectCode = str_replace('Redirect 301',false, $line);
            $clearRedirectCode = str_replace('ErrorDocument',false, $clearRedirectCode);
            $clearRedirectCode = trim($clearRedirectCode);
            $expLine = explode(' ', $clearRedirectCode);

            if (!isset($expLine[1]) || empty($expLine[0]) || empty($expLine[1])) {
                continue;
            }

            $linksForSave[] = [
                'redirect_code' => $redirectCode,
                'redirect_from_url' => $expLine[0],
                'redirect_to_url' => $expLine[1],
            ];
        }
    }

    if (!empty($linksForSave)) {
        $saved = [];
        foreach ($linksForSave as $link) {

            $x_from = str_ireplace(site_url(), '', trim($link['redirect_from_url']));
            $x_from = trim($x_from, '/');

            $x_to = str_ireplace(site_url(), '', trim($link['redirect_to_url']));
            $x_to = trim($x_to, '/');

            $link['redirect_from_url'] = $x_from;
            $link['redirect_to_url'] = $x_to;

            $link['redirect_code'] = str_replace('Redirect', '', $link['redirect_code']);
            $link['active'] = 1;

            $findLink = db_get('browser_redirects', [
                'no_cache'=>1,
                'single'=>1,
                'redirect_from_url_hash'=>md5($link['redirect_from_url']),
                'redirect_to_url_hash'=>md5($link['redirect_to_url']),
            ]);

            if ($findLink) {
                $link['id'] = $findLink['id'];
            }

            $link['redirect_from_url_hash'] = md5($link['redirect_from_url']);
            $link['redirect_to_url_hash'] = md5($link['redirect_to_url']);

            $saved[] = db_save('browser_redirects', $link);
        }

        if (!empty($saved)) {
            return ['success' => count($saved) . ' links are saved success.'];
        }
    }

    return ['error'=>'No data found in this file.'];

});

function get_browsers_options()
{
    $browsers = array();
    $browsers['chrome'] = 'Google Chrome';
    $browsers['safari'] = 'Apple Safari';
    $browsers['opera'] = 'Opera';
    $browsers['firefox'] = 'Mozilla Firefox';
    $browsers['internet_explorer'] = 'Internet Explorer';
    $browsers['microsoft_edge'] = 'Microsoft Edge';

    return $browsers;
}

function get_browser_redirects($onlyActive = false)
{
    $filter = array();
    $filter['no_cache'] = 1;
    $filter['no_limit'] = 1;
    if ($onlyActive) {
        $filter['active'] = 1;
    }

    return db_get('browser_redirects', $filter);
}

function get_active_redirect (string $segment) {
    return db_get('browser_redirects', [
        'redirect_from_url' => $segment,
        'active' => 1,
        'single' => true
    ]);
}

api_expose_admin('browser_redirect_delete', function() {
    if (isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        db_delete('browser_redirects', $id);
    }
});

api_expose_admin('browser_redirect_save', function () {

    if (!isset($_POST['redirect_from_url']) || empty(trim($_POST['redirect_from_url']))) {
        return array('error'=>'Redirect from url cannot be empty.');
    }

    if (!isset($_POST['redirect_to_url']) || empty(trim($_POST['redirect_to_url']))) {
        return array('error'=>'Redirect to url cannot be empty.');
    }

    if (!isset($_POST['redirect_code']) || empty(trim($_POST['redirect_code']))) {
        return array('error'=>'Select redirect code.');
    }

    /*
    if (!isset($_POST['redirect_browsers']) || empty($_POST['redirect_browsers'])) {
        return array('error'=>'Please select, redirect browsers.');
    }
    */

    $save = array();
    if (!empty($_POST['redirect_browsers']) && is_array($_POST['redirect_browsers'])) {
        $save['redirect_browsers'] = implode(',', $_POST['redirect_browsers']);
    } else {
        $save['redirect_browsers'] = null;
    }

    if (isset($_POST['active']) && trim($_POST['active']) == 'y') {
        $save['active'] = 1;
    } else {
        $save['active'] = 0;
    }

    $x_from = str_ireplace(site_url(), '', trim($_POST['redirect_from_url']));
    $x_to = str_ireplace(site_url(), '', trim($_POST['redirect_to_url']));
    $x_from = trim($x_from, '/');
    $x_to = trim($x_to, '/');

    $save['redirect_code'] = trim($_POST['redirect_code']);
    $save['redirect_to_url'] = $x_to;
    $save['redirect_to_url_hash'] = md5($save['redirect_to_url']);
    $save['redirect_from_url'] = $x_from;
    $save['redirect_from_url_hash'] = md5($save['redirect_from_url']);

    if (isset($_POST['id'])) {
        $save['id'] = (int) trim($_POST['id']);
    }

    /**
     * ToDo: check for cycling
     */
    try {
        $id = db_save('browser_redirects', $save);
    } catch (\Illuminate\Database\QueryException $exception) {
        DB::table('browser_redirects')
            ->where([ 'redirect_from_url_hash' => $save['redirect_from_url_hash'] ])
            ->update([
                'redirect_to_url' => $save['redirect_to_url'],
                'redirect_to_url_hash' => $save['redirect_to_url_hash'],
                'redirect_code' => $save['redirect_code'],
                'redirect_browsers' => $save['redirect_browsers']
            ]);

        return array('success' => 'Redirect updated.');
    }

    return array('success' => 'The browser redirect is saved.', 'id' => $id);
});


event_bind('mw.controller.index', function () {

    $currentUri = mw()->url_manager->current();
    $currentUri = str_replace(site_url(), '', $currentUri);

/*    // Remove first
    if (substr($currentUri, 0, 1) == '/') {
        $currentUri = substr($currentUri, 1);
    }*/

    $redirectData = get_active_redirect($currentUri);

    $userAgent = false;
    $browserName = false;

    if (is_array($redirectData) && !empty($redirectData)) {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
        }

        if ($userAgent) {
            $browserName = get_browser_name($userAgent);
        }

        if (empty($redirectData['redirect_browsers']) || (!empty($browserName) && in_array($browserName, explode(',', $redirectData['redirect_browsers'])))) {
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            if ($redirectData['redirect_code']) {
                header('HTTP/1.1 ' . $redirectData['redirect_code']);
            }

            if (filter_var($redirectData['redirect_to_url'], FILTER_VALIDATE_URL)) {
                header('Location: ' . $redirectData['redirect_to_url']);
            } else {
                header('Location: ' . site_url() . $redirectData['redirect_to_url']);
            }
            exit;
        }
    }
});

event_bind('mw.pageview', function() {
    $redirectBrowsers = array();
    $redirectCode = false;
    $redirectUrl = false;
    $startRedirecting = false;
    $urlSegment = mw()->url_manager->string();
    $userAgent = false;
    $browserName = false;
    $redirects = get_browser_redirects(true);
    $current = mw()->url_manager->current();

    if (empty($redirects) && !is_array($redirects)) {
        return;
    }

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $userAgent = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
    }
    if ($userAgent) {
        $browserName = get_browser_name($userAgent);
    }

    foreach ($redirects as $redirect) {

        $detectedSegment = false;

        $redirect['redirect_from_url'] = str_replace(site_url(), false, $redirect['redirect_from_url']);
        $redirect['redirect_to_url'] = str_replace(site_url(), false, $redirect['redirect_to_url']);

        if($redirect['redirect_from_url'] == "*" && $urlSegment !== $redirect['redirect_to_url']) {
            $detectedSegment = true;
        }

        if($redirect['redirect_from_url'] == "/" && $urlSegment == '') {
            $detectedSegment = true;
        }

        if("/" .$redirect['redirect_from_url'] == $urlSegment) {
            $detectedSegment = true;
        }

        if($redirect['redirect_from_url'] == $urlSegment) {
            $detectedSegment = true;
        }

        if($redirect['redirect_from_url'] == "/".$urlSegment) {
            $detectedSegment = true;
        }

        if ($detectedSegment) {
            $redirectCode = $redirect['redirect_code'];
            $redirectUrl = $redirect['redirect_to_url'];
            $redirectBrowsers = explode(',', $redirect['redirect_browsers']);
            break;
        }
    }

    if (empty($redirectBrowsers) && !is_array($redirectBrowsers)) {
        return;
    }

    if ($browserName && in_array($browserName, $redirectBrowsers)) {
        $startRedirecting = true;
    }

    if ($startRedirecting && $redirectUrl) {

        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        if ($redirectCode) {
            header('HTTP/1.1 ' . $redirectCode);
        }

        if (filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            header('Location: ' . $redirectUrl);
        } else {
            header('Location: ' . site_url() . $redirectUrl);
        }
        exit;
    }

    return;
});

function get_browser_name($userAgent)
{
    $t = strtolower($userAgent);
    $t = " " . $t;

    if (strpos($t, 'opera') || strpos($t, 'opr/')) return 'opera';
    elseif (strpos($t, 'edge')) return 'microsoft_edge';
    elseif (strpos($t, 'chrome')) return 'chrome';
    elseif (strpos($t, 'safari')) return 'safari';
    elseif (strpos($t, 'firefox')) return 'firefox';
    elseif (strpos($t, 'msie') || strpos($t, 'trident/7')) return 'internet_explorer';

    return 'unknown';
}
