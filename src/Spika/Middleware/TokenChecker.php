<?php
namespace Spika\Middleware;

use Spika\Db\DbInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenChecker
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
        
        $tokenReceived  = $request->headers->get('token');
        
        $user = $this->db->findUserByToken($tokenReceived);

        if (empty($user['token']) || $tokenReceived !== $user['token']) {
            return $this->abortManually("Invalid token");
        }
        
        $tokenTimestamp = $user['token_timestamp'];
        $currentTimestamp = time();
        $tokenTime = $tokenTimestamp + TOKEN_VALID_TIME;

        if ($tokenTime < $currentTimestamp) {
            return $this->abortManually("Token expired");
        }
        
        $app['currentUser'] = $user;
    }

    private function abortManually($errMessage)
    {
        $arr  = array('message' => $errMessage, 'error' => 'logout');
        $json = json_encode($arr);

        return new Response($json, 403);
    }
}
