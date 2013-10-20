<?php
namespace Spika\Middleware;

use Spika\Db\DbInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

function abortManually($errMessage){
    $arr = array('message' => $errMessage, 'error' => 'logout');
    header("HTTP/1.0 403 Forbidden");
    echo json_encode($arr);
    die();
}

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
	    // pass token check when unit testing
	    if(!function_exists("getallheaders"))
	    	return;
	    	
        $headers = getallheaders();
        $tokenReceived = $headers['token'];
        $useridReceived = $headers['user_id'];
        $isCreateUserRequest = false;

        $this->logger->addDebug("token : {$tokenReceived}");
        $this->logger->addDebug("medhod : " . $request->getMethod());
        $this->logger->addDebug("user id : {$useridReceived}");
        $this->logger->addDebug(print_r($_SERVER,true));

        if($request->getMethod() == "POST" && $useridReceived == "create_user"){
            $isCreateUserRequest = true;
            return;
        }

        if(empty($tokenReceived) || empty($useridReceived)){
            abortManually("No token sent");
        }

        $query = "?key=" . urlencode('"' . $useridReceived . '"');
        $result = $this->db->doGetRequest("/_design/app/_view/find_user_by_id{$query}",false);
        $userData = json_decode($result, true);

        if(!isset($userData['rows'][0]['value']['_id']) || $userData['rows'][0]['value']['_id'] != $useridReceived){
            abortManually("No token sent");
        }

        if($tokenReceived != $userData['rows'][0]['value']['token']){
            abortManually("Invalid token");
        }

        $tokenTimestamp = $userData['rows'][0]['value']['token_timestamp'];
        $currentTimestamp = time();
        $tokenTime = $tokenTimestamp + TokenValidTime;

        if ($tokenTime < $currentTimestamp) {
            abortManually("Token expired");
        }


        //$this->logger->addDebug("check token user id : " . $userid);
        //$this->logger->addDebug("check token user : " . print_r($userData,true));

    }
}
