<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/24/13
 * Time: 2:27 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class GroupController extends SpikaBaseController
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        $this->setupCreateGroupMethod($self,$app,$controllers);


        return $controllers;
    }

    private function setupCreateGroupMethod($self,$app,$controllers){

        $controllers->post('/CreateGroup',
            function (Request $request) use ($app,$self) {

                $groupData = $request->getContent();

                if(!$self->validateRequestParams($groupData,array(
                    'category_id',
                    'user_id',
                    'description',
                    'name'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $groupData= json_decode($groupData);

                $result = $app['spikadb']->createGroup($groupData);
                $app['monolog']->addDebug("CreateGroup API called by user: \n {$groupData->user_id} \n");

                return json_encode($result);
            }
        );
    }

    private function setupSendGroupMessage($self,$app,$controllers){

        $controllers->post('/SendGroupMessage',
            function (Request $request) use ($app,$self) {

                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'to_group_id',
                    'from_user_id',
                    'body',
                    'type',
                    'message_target_type',
                    'message_type'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);

                //FIRST THING TO DO : GET GROUP DATA BY ID

                /*
                if(!isset($messageDataArray['from_user_name'])){
                    $fromUserData=$app['spikadb']->findUserById($messageDataArray['from_user_id']);
                    $messageDataArray['from_user_name']=$fromUserData['name'];
                }

                if(!isset($messageDataArray['to_group_id'])){
                    //load group data
                    //$groupData=$app['spikadb']->findUserById($messageDataArray['to_user_id']);
                    //$messageDataArray['to_user_name']=$toUserData['name'];
                } */

                echo print_r($messageData);
                die();

            }
        );
    }


}