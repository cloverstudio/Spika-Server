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

    public function __invoke(Request $request)
    {
        $tokenReceived  = $request->headers->get('token');
        $useridReceived = $request->headers->get('user_id');
        $isCreateUserRequest = false;

        $this->logger->debug("token : {$tokenReceived}");
        $this->logger->debug("medhod : " . $request->getMethod());
        $this->logger->debug("user id : {$useridReceived}");
        $this->logger->debug(print_r($_SERVER, true));

        if ($request->getMethod() === 'POST' && $useridReceived == 'create_user') {
            $isCreateUserRequest = true;
            return;
        }

        if (empty($tokenReceived) || empty($useridReceived)) {
            return $this->abortManually("No token sent");
        }

        $user = $this->db->findUserById($useridReceived);

        if (!isset($user['_id']) || $user['_id'] != $useridReceived) {
            return $this->abortManually("No token sent");
        }

        if ($tokenReceived !== $user['token']) {
            return $this->abortManually("Invalid token");
        }

        $tokenTimestamp = $user['token_timestamp'];
        $currentTimestamp = time();
        $tokenTime = $tokenTimestamp + TOKEN_VALID_TIME;

        if ($tokenTime < $currentTimestamp) {
            return $this->abortManually("Token expired");
        }


        //$this->logger->debug("check token user id : " . $userid);
        //$this->logger->debug("check token user : " . print_r($userData,true));

    }

    private function abortManually($errMessage)
    {
        $arr  = array('message' => $errMessage, 'error' => 'logout');
        $json = json_encode($arr);

        return new Response($json, 403);
    }
}
