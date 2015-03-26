<?php

 class SignalFx {

   /**
    * An instance of the SignalFx class (for singleton use)
    * @var Cooladata
    */
   private static $_instance;

   /**
     * @var string a token associated to a SignalFx project
   */
   private $_token;

   /**
    * Creates a new SignalFx, assings SignalFx project token
    * @param $token
    */
   public function __construct($token) {
     // associate token
     $this->_token = $token;
   }

   /**
    * Returns a singleton instance of SignalFx
    * @param $token
    * @return SignalFx
    */
   public static function getInstance($token) {
       if(!isset(self::$_instance)) {
           self::$_instance = new SignalFx($token);
       }
       return self::$_instance;
   }

   public function track($event_name, $array)
   {
    $post_data['metric']      = $event_name;
    $post_data['dimensions']  = $array;
    $post_data['value']       = 1;
    $params = array('counter' => array($post_data));
                                                                     
    $data_string = json_encode($params);    

    $ch = curl_init('https://ingest.signalfx.com/v2/datapoint');                                                                      
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($data_string),
        // 'X-SF-Token: '. $_token)
        'X-SF-Token: tSn033iSoOb1l7NubpR57w')                                                                      
    );
  
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
   }
 }
