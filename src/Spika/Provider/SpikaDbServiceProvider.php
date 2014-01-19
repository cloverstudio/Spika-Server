<?php
namespace Spika\Provider;

use Spika\Db\CouchDb;
use Spika\Db\MySQL;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SpikaDbServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
       /* $app['spikadb'] = $app->share(function () use ($app) {
            return new CouchDb(
                $app['couchdb.couchDBURL'],
                $app['logger']
            );
        });
       */
       
        
        $app['spikadb'] = $app->share(function () use ($app) {
            return new MySQL(
                $app['couchdb.couchDBURL'],
                $app['logger'],
                $app['db']
            );
        });
       
    }

    public function boot(Application $app)
    {
    }
}
