<?php

namespace Spika\Provider;

use Spika\Db\CouchDb;
use Spika\Db\MySql;
use Silex\Application;
use Silex\ServiceProviderInterface;

define('SP_TIMEOUT',10);

class PushNotificationProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        
        $self = $this;
        
        $app['sendProdAPN'] = $app->protect(function($tokens,$payload) use ($self,$app) {
            $self->sendAPN($app['pushnotification.options']['APNProdPem'],$tokens,$payload,'ssl://gateway.push.apple.com:2195',$app);
        });
       
        $app['sendDevAPN'] = $app->protect(function($tokens,$payload) use ($self,$app) {           
            $self->sendAPN($app['pushnotification.options']['APNDevPem'],$tokens,$payload,'ssl://gateway.sandbox.push.apple.com:2195',$app);
        });
       
        $app['sendGCM'] = $app->protect(function($payload) use ($self,$app) {           
            $self->sendGCM($app['pushnotification.options']['GCMAPIKey'],$payload,$app);
        });
       
    }

    public function boot(Application $app)
    {
    }
    
    public function connectToAPN($cert,$host,$app){
        
        $app['monolog']->addDebug("connecting to APN cert : {$cert}");
        
        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
        $fp = stream_socket_client($host, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

        if (!$fp) {
            $app['monolog']->addDebug("Failed to connect $err $errstr");
            $app['monolog']->addDebug("Try to recconect");
            return $this->connectToAPN($cert,$host);
        }
        else {
            //stream_set_blocking($fp, 0);
            //stream_set_timeout($fp,SP_TIMEOUT);
        }
        
        return $fp;
    }
    
    public function sendAPN($cert, $deviceTokens, $payload, $host, $app){
                  
        $apn_status = array(
                        '0' => "No errors encountered",
                        '1' => "Processing error",
                        '2' => "Missing device token",
                        '3' => "Missing topic",
                        '4' => "Missing payload",
                        '5' => "Invalid token size",
                        '6' => "Invalid topic size",
                        '7' => "Invalid payload size",
                        '8' => "Invalid token",
                        '255' => "unknown"
                        );

        if(count($deviceTokens) == 0) return;
        
        $fp = $this->connectToAPN($cert,$host,$app);
        $size = 0;
        
        foreach($deviceTokens as $index => $deviceToken){
            
            $identifiers = array();
            for ($i = 0; $i < 4; $i++) {
                $identifiers[$i] = rand(1, 100);
            }
        
            $msg = chr(1) . chr($identifiers[0]) . chr($identifiers[1]) . chr($identifiers[2]) . chr($identifiers[3]) . pack('N', time() + 3600) 
                    . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($payload)) . $payload;
    
            $size += strlen($payload);
            
            $result = fwrite($fp, $msg);
            
            if($size >= 5120){
                // if sent more than 5120B reconnect again
                $fp = $this->connectToAPN($cert,$host,$app);
                sleep(1);
            }
            
            
            if(!$result){
                 $app['monolog']->addDebug("{$deviceToken} succeed");
                 
            }else{
                
                
                $read = array($fp);
                $null = null;
                $changedStreams = stream_select($read, $null, $null, 0, 1000000);
    
                if ($changedStreams === false) {    
                    $app['monolog']->addDebug("Error: Unabled to wait for a stream availability");
    
                } elseif ($changedStreams > 0) {
                    
                    $responseBinary = fread($fp, 6);
    
                    if ($responseBinary !== false || strlen($responseBinary) == 6) {
                        $response = unpack('Ccommand/Cstatus_code/Nidentifier', $responseBinary);
                        $result = $apn_status[$response['status_code']];
                        $app['monolog']->addDebug("{$deviceToken} failed {$result}");
                        
                    } else {
                        
                        $app['monolog']->addDebug("{$deviceToken} failed");
                        
                    }
                    
                } else {
                    
                    $result = 'succeed';
                    $app['monolog']->addDebug("{$deviceToken} succeed");
                    
                }
                
                if($result != 'succeed'){
                    // if failed connect again
                    $fp = $this->connectToAPN($cert,$host,$app);
                    sleep(1);
                }

    
            }

            
        }
        

        fclose($fp);

        return $result;

    }

   function sendGCM($apiKey, $json, $app = null) {
       
        $app['monolog']->addDebug($apiKey);

        // Set POST variables
        $url = 'https://android.googleapis.com/gcm/send';

        $headers = array( 
                        'Authorization: key=' . $apiKey,
                        'Content-Type: application/json'
                        );
        // Open connection
        $ch = curl_init();

        // Set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS,$json);
        curl_setopt( $ch, CURLOPT_TIMEOUT,SP_TIMEOUT);

        // Execute post
        $result = curl_exec($ch);

        curl_close($ch);
        
        $app['monolog']->addDebug($result);


        return $result;

    }
    
}
