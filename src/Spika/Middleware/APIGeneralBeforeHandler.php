<?php
namespace Spika\Middleware;

use Spika\Db\DbInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class APIGeneralBeforeHandler
{
    /**
     * @var Spika\Db\DbInterface
     */
    private $db;

    /**
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(DbInterface $db, LoggerInterface $logger)
    {
        $this->db     = $db;
        $this->logger = $logger;
    }

    public function __invoke(Request $request,\Silex\Application $app)
    {
        
        // maintainance mode
        // return new Response("maintainance mode", 503);

    }

}
