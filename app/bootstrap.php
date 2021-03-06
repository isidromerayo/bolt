<?php

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "<p>The file <code>vendor/autoload.php</code> doesn't exist. Make sure you've installed the Silex/Bolt components with Composer. See the README.md file.</p>";
    die();
}

$bolt_version = "0.7";
$bolt_buildnumber = "";
$bolt_name = "First beta";

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/classes/lib.php';
require_once __DIR__.'/classes/storage.php';
require_once __DIR__.'/classes/content.php';
require_once __DIR__.'/classes/users.php';
require_once __DIR__.'/classes/util.php';
require_once __DIR__.'/classes/cache.php';
require_once __DIR__.'/classes/log.php';

$config = getConfig();
$dboptions = getDBOptions($config);



// Start the timer:
$starttime=getMicrotime();

$app = new Silex\Application();

$app['debug'] = (!empty($config['general']['debug'])) ? $config['general']['debug'] : false;

$app['config'] = $config;

$app->register(new Silex\Provider\SessionServiceProvider());

/*
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/cache/debug.log',
    'monolog.name' => "Bolt"
));
*/

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => $config['twigpath'],
    'twig.options' => array(
        'debug'=>true, 
        'cache' => __DIR__.'/cache/', 
        'strict_variables' => $config['general']['strict_variables'],
        'autoescape' => false )
));


$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dboptions
));



$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/cache/',
));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


use Silex\Provider\FormServiceProvider;
$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

// Loading stub functions for when intl / IntlDateFormatter isn't available.
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/Locale/Symfony/Component/Locale/Resources/stubs/functions.php';
    require_once __DIR__.'/../vendor/symfony/Locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
}




$app['storage'] = new Storage($app);

$app['users'] = new Users($app);
$app['paths'] = getPaths($config);

$app['editlink'] = "";

// Add the Bolt Twig functions, filters and tags.
require_once __DIR__.'/classes/twig_bolt.php';
$app['twig']->addExtension(new Bolt_Twig_Extension());

require_once __DIR__.'/classes/twig_setcontent.php';
$app['twig']->addTokenParser(new Bolt_Setcontent_TokenParser());


$app['cache'] = new Cache();
$app['log'] = new Log($app);


// If debug is set, we set up the custom error handler..
if ($app['debug']) {
    ini_set("display_errors", "1");
    error_reporting (E_ALL );
    $old_error_handler = set_error_handler("userErrorHandler");
} else {
    error_reporting(E_ALL ^ E_NOTICE);
    // error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
}


require_once __DIR__.'/app_backend.php';
require_once __DIR__.'/app_frontend.php';
