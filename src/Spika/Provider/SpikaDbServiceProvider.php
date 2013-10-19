<?php
namespace Spika\Provider;

use Spika\Db\CouchDb;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SpikaDbServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['spikadb'] = $app->share(function () use ($app) {
            return new CouchDb(
                $app['couchdb.couchDBURL'],
                $app['logger']
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
