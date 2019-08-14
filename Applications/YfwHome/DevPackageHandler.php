<?php
/*
  �豸����������ݰ�Э���ҵ���߼�
*/

use \GatewayWorker\Lib\Gateway;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/../../vendor/autoload.php';

class DevPackageHandler
{
	public static function handlePackage($client_id, $package_data, $db)
	{

		//���豸�����ݰ����з�����������Ӧ�Ķ���
    	switch ($package_data['type']) {
            case 'Utils::PING':
                if(!empty($_SESSION['PID'])){
                    //debug
				 	LOG::OutLog("[DevPackHandler_Msg]:","Dev". $_SESSION['PID'] ." ,ping...\n"); //
    
                }else{
                    //debug               
				  LOG::OutLog("[DevPackHandler_Msg]:","Dev[unknown]: ,ping...\n"); //
                }
            break;
    		case Utils::CONNECT:
			   LOG::OutLog("[DevPackHandler_Msg]:","connect...\n"); //
    			self::checkConnect($client_id, $package_data);
				
    		break;
    		case Utils::DISCONNECT:
			 LOG::OutLog("[DevPackHandler_Msg]:","disconnect...\n"); //
      			if(Gateway::isOnline($client_id)){
       				if(!empty($_SESSION['PID'])){                        
                        //info
						LOG::OutLog("[DevPackHandler_Msg]:", "Dev[". $_SESSION['PID'] ."]: disconnecting...\n"); //                    
						
       				}
        			Gateway::closeClient($client_id);
      			}
     		break;
     		case Utils::DEVSTAT:
      			self::SendDevStat($client_id, $package_data);
					 LOG::OutLog("[DevPackHandler_Msg]:","devstat...\n"); //
      		break;
      		case Utils::DONE:
      			self::sendDone($client_id, $package_data, $db);
				 LOG::OutLog("[DevPackHandler_Msg]:","done...\n"); //
      		break;
      		case Utils::DEV_ERROR:
      			//self::sendUndone($client_id, $package_data);
				 LOG::OutLog("[DevPackHandler_Msg]:","error...\n"); //
      		break;
    	}
    }
	
	 /**
   	* ����豸�Ƿ����ӳɹ������������ӽ��
   	* @param string $package_data
   	* @return bool
   	*/
	private static function checkConnect($client_id, $package_data)
 	{
        //info
     
	  LOG::OutLog("[DevPackHandler_Msg]:","checkConnect...\n"); //
    
    	if (!empty($_SESSION['PID'])) {
            //error
           
		   LOG::OutLog("[DevPackHandler_Msg]:","Server: PID is existence....\n"); //
            return false;
        }
     	//���PID
    	if(empty($package_data['PID'])){               
        	//PID+passwordΪ�գ�������¼ʧ�ܷ�����Ϣrejected
     		$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      		Gateway::sendToCurrentClient($new_package);
            //error
           
			  LOG::OutLog("[DevPackHandler_Msg]:","Dev[unknown]: connect failed! PID is null.....\n"); //
     		Gateway::closeCurrentClient();
     		return false;
   		}
   		if (strlen($package_data['PID']) != 6){

   			$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      		Gateway::sendToCurrentClient($new_package);
            //error
          
		    LOG::OutLog("[DevPackHandler_Msg]:","Dev[unknown]: Length of PID is not 6 byte.....\n"); //
     		return false;
   		}

     	//�豸PID
   		$PID = trim($package_data['PID']);

      	//�豸��session
   		$dev_sessions = Gateway::getAllClientSessions();
      	
      	//���PID�Ƿ��ظ���¼
   		foreach ($dev_sessions as $temp_client_id => $temp_sessions) 
   		{

    		if(!empty($temp_sessions['PID']) && $temp_sessions['PID'] == $PID){
          		//�û����ظ���������¼ʧ�ܷ�����Ϣ
      			$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_FAIL);
      			Gateway::sendToCurrentClient($new_package);
                //error
            
			  LOG::OutLog("[DevPackHandler_Msg]:","Dev[". $PID ."]:connect failed! PID is repeated.....\n"); //
      			//Gateway::closeCurrentClient();

                //Ϊ��ֹ�豸1s�������
                Timer::add(5, array('\GatewayWorker\Lib\Gateway', 'closeClient'), array($client_id), false);
      			return false;
    		}
  		}

		//û�з�������
  		//��PID��password��session��
  		$_SESSION['PID'] = $PID;
      	//TODO: �������
  		//$_SESSION['password'] = $password;

  		//�������ӳɹ�������Ϣ
  		$new_package = array('length' => 1, 'type' => Utils::SERVER_FEEDBACK_SUCCESS);
      	Gateway::sendToCurrentClient($new_package);
  	
        //info
       
	     LOG::OutLog("[DevPackHandler_Msg]:","Dev[". $PID ."]:connect successful!.....\n"); //
		 
				//֪ͨ�û��豸������
					$new_message = array('type' => 'UP_LINE', 'from' => 'SERVER', 'content' => $PID);					
    				Gateway::sendToUid($_SESSION['PID'], json_encode($new_message));
					
	   
		 
  		return true;
	}
	
	
	
	 /**
     * ���û������豸״̬��Ϣ
     */
	private static function SendDevStat($client_id, $package_data)
	{
        //info
       // LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]:send posture info to users...\n");  
	     LOG::OutLog("[DevPackHandler_Msg]:","Dev[". $_SESSION['PID'] ."]:send devstat info to users!.....\n"); //
  		if(empty($_SESSION['PID'])){
            //error
          //  LoggerServer::log(Utils::ERROR, "Server: Bed session[PID] lost!\n");
		   LOG::OutLog("[DevPackHandler_Msg]:","Error:session[PID] lost!.....\n"); //
    		Gateway::closeClient($client_id);
    		return false;
  		}else{
		//	 LOG::OutLog("[SendDevStat]:","Dev[". $PID ."]:�������ݣ�".$package_data['data'].".....\n"); //
			
          	//��󶨵��û�������̬��Ϣ
          	$new_message = array('type' => 'DevMsg', 'from' => 'SERVER', 'data' =>$package_data['data']);
    		Gateway::sendToUid($_SESSION['PID'], json_encode($new_message,JSON_UNESCAPED_UNICODE));
    		return true;
  		}
	}
	 /**
     * ���û����������Ϣ
     */
	private static function sendDone($client_id, $package_data)
	{
        //info
       // LoggerServer::log(Utils::INFO, "Bed[". $_SESSION['PID'] ."]:send posture info to users...\n");  
	     LOG::OutLog("[DevPackHandler_Msg]:","Dev[". $_SESSION['PID'] ."]:send devstat info to users!.....\n"); //
  		if(empty($_SESSION['PID'])){
            //error
          //  LoggerServer::log(Utils::ERROR, "Server: Bed session[PID] lost!\n");
		   LOG::OutLog("[DevPackHandler_Msg]:","Error:session[PID] lost!.....\n"); //
    		Gateway::closeClient($client_id);
    		return false;
  		}else{
		//	 LOG::OutLog("[SendDevStat]:","Dev[". $PID ."]:�������ݣ�".$package_data['data'].".....\n"); //
			
          	//��󶨵��û�������̬��Ϣ
          	$new_message = array('type' => 'DONE', 'from' => 'DEV', 'data' =>$package_data['info']);
    		Gateway::sendToUid($_SESSION['PID'], json_encode($new_message,JSON_UNESCAPED_UNICODE));
    		return true;
  		}
	}
	

}


?>