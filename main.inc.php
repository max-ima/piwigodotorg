<?php
/*
Plugin Name: piwigo-piwigodotorg
Version: 0.1
Description: Piwigo.org website
Plugin URI: http://www.phpwebgallery.net
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

/* DEFINE */
define('PORG_ID', basename(dirname(__FILE__)));
define('PORG_PATH', PHPWG_PLUGINS_PATH . PORG_ID . '/');

// we put these handlers "before" the test on index page (and the return) because
// whatever the page, we want to execute them
add_event_handler('ws_add_methods', 'porg_add_methods');
add_event_handler('user_init', 'porg_user_init');

include(PORG_PATH . 'include/functions_piwigodotorg.php');
include(PORG_PATH . 'include/functions_ws_porg.php');

 if (script_basename() != 'index') {
    return;
 }

// adapt language depending on url
function porg_user_init()
{
    global $user;

    $user['language'] = 'en_UK';

    $raw_url = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];

    // what is the subdomain, if any?
    if (preg_match('#([a-z]{2,3})\.piwigo\.org#', $raw_url, $matches))
    {
        $subdomain = $matches[1];

        $subdomain_to_language = array(
            'en' => 'en_UK',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'es' => 'es_ES',
            );

        if (isset($subdomain_to_language[$subdomain]))
        {
            $user['language'] = $subdomain_to_language[$subdomain];
        }
    }
}

/* Load Piwigo.org language */
add_event_handler('init', 'porg_lang_init');
function porg_lang_init() {
    load_language('plugin.lang', PORG_PATH);
}

/* Add ws_methods */
function porg_add_methods($arr)
{
    $service = &$arr[0];

    $service->addMethod(
        'porg.newsletters.seemore',
        'ws_porg_newsletters_seemore',
        array(
            'start' =>  array(),
            'count' =>  array(),
        ),
        'Show more newsletters'
    );
    $service->addMethod(
        'porg.home.refresh_showcases',
        'ws_porg_home_refresh_showcases',
        null,
        'Refresh showcases thumbnail'
    );
}

/* Load Piwigo.org header */
add_event_handler('init', 'porg_load_header');
function porg_load_header()
{
    global $template;

    $porg_root_url = get_absolute_root_url();
    $porg_root_url_piwigodotorg = get_absolute_root_url() . PORG_PATH;
    $url = set_porg_url();
    $template->set_template_dir(PORG_PATH);
    $template->set_filenames(array('header_porg' => realpath(PORG_PATH .'template/header.tpl')));
    $template->assign(
        array(
            'PORG_ROOT_URL' => $porg_root_url,
            'PORG_ROOT_URL_PLUGINS' => $porg_root_url_piwigodotorg,
            'URL' => $url,
        )
    );

    /* Display header */
    $template->parse('header_porg');
}

add_event_handler('init', 'porg_load_content');
function porg_load_content()
{
    global $template;

    $porg_root_url = get_absolute_root_url();
    if (isset($_GET['porg']))
    {
        $porg_page = $_GET['porg'];
        $porg_page = str_replace('-', '_', $porg_page);
        $porg_pages = set_porg_url();
        if (isset($porg_pages[$porg_page]))
        {
            $template->set_filenames(array('porg_page' => realpath(PORG_PATH . 'template/' . $porg_page . '.tpl')));
            load_language($porg_page . '.lang', PORG_PATH);
            if (file_exists(PORG_PATH . '/include/' . $porg_page . '.inc.php'))
            {
                include(PORG_PATH . '/include/' . $porg_page . '.inc.php');
            }
        }
        else
        {
            $template->set_filenames(array('porg_page' => realpath(PORG_PATH . 'template/404.tpl')));
        }
    }
    else
    {
        load_language('home.lang', PORG_PATH);
        $template->set_filenames(array('porg_page' => realpath(PORG_PATH . 'template/' . 'home.tpl')));
        $image = get_showcases();
    }
    $template->assign(array(
        'PORG_ROOT_URL' => $porg_root_url . PORG_PATH,
        'SHOWCASES' => isset($image) ? $image : null,
    ));
    $template->parse('porg_page');
}

add_event_handler('init', 'porg_load_footer');
function porg_load_footer()
{
    global $template;

    $porg_root_url = get_absolute_root_url();
    $template->set_filenames(array('footer_porg' => realpath(PORG_PATH .'template/footer.tpl')));
    $template->assign(array(
        'PORG_ROOT_URL' => $porg_root_url . PORG_PATH,
    ));
    $template->parse('footer_porg');
    $template->p();
    exit();
}

?>
