<?php
/**
* Simple PHP Vmix Wrapper Class
*
* @author Jeff Johns <phpfunk@gmail.com>
* @license MIT License
*/
class Vmix {

  public $partner_id    =  NULL;
  public $pass          =  NULL;
  public $response_type = 'json';
  
  protected $action   =   NULL;
  protected $base_url =   'api.vmixcore.com/apis/';
  protected $ch       =   NULL;
  protected $pages    =   array('captcha','collection','comments','crypt','genre','media','ratings','ReportedPost','tags');

  /**
  * Sets object parameters if supplied at time of creation.
  *
  * @param  string  $partner_id      The partner ID you wish to call
  * @param  string  $pass            The partner password
  * @param  string  $response_type   The response type (json, jsonp or xml)
  */
  public function __construct($partner_id=NULL, $pass=NULL, $response_type='json')
  {
    $this->partner_id     = (! empty($partner_id)) ? $partner_id : $this->partner_id;
    $this->pass           = (! empty($pass)) ? $pass : $this->pass;
    $this->response_type  = strtolower($response_type);
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, TRUE);
    curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, FALSE);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($this->ch, CURLOPT_HTTPGET, TRUE);
  }
  
  /**
  * Called when you call a method that doesn't exist.
  * Will call the API action URL.
  *
  * @param  string  $method   method called
  * @param  array   $args     array of arguments
  * @return array
  */
  public function __call($method, $args)
  {
    $this->response_type = (isset($args[0]['output'])) ? strtolower($args[0]['output']) : $this->response_type;
    $query = (isset($args[0])) ? $this->get_query($args[0]) : '';
    $url = $this->partner_id . ':' . $this->pass . '@' . $this->base_url . $this->find_page($method) . '.php?action=' . $method . $query;
		
    //Make it happen
    curl_setopt ($this->ch, CURLOPT_URL, 'http://' . $url);
		
    //Get the response
    $response = curl_exec($this->ch);
    if (!$response) {
      $response = curl_error($this->ch);
    }
    else {
      if ($this->response_type == 'xml') {
        $response = simplexml_load_string($response);
      }
      else {
        $response = json_decode($response);
      }
    }
    return $this->unserialize_data($response);
  }
  
  /**
  * Destroys the cURL connection
  *
  */
  public function __destruct()
  {
    curl_close($this->ch);
  }
  
  /**
  * Returns any children collection of the collection_id passed
  *
  * @param  integer $id   The collection ID you want the children for
  * @param  integer $status   The status the collection must be (-1 = deleted, 0 = disabled, 1 = enabled, defaults to 1)
  * 
  * @return array
  */
  public function getChildren($id, $status=1)
  {
    if (! is_numeric($id)) { return false; }
    return $this->getCollections(array(
        'collection_id' =>  $id,
        'get_children'  =>  1,
        'get_count'     =>  1,
        'status'        =>  $status
    ));
  }
  
  /**
  * NOT PRODUCTION READY OR TESTED
  * Creates the embed code for any movie.
  *
  * @param  array  $arg   An array of all your settings.
  *
  * The $arg param is an array with all the settings you want to set for your embed code.
  * You can set any property in the flash and define and flashVars, flash params, etc
  * by passing them into the method. A simple breakdown is the key of the array should be
  * variable you want to set and the value, it's value to set.
  *
  * The object and embed params should all be set to the key of 'params' and this should be an
  * array full of the keys and values you want to set for each object and embed parameter. You
  * only need to define it once and the method will create the object and embed params. Some
  * defaults are set so that you don't have to pass the same params everytime for things like
  * allowScriptAccess, allowFullScreen, etc. They are described below.
  *
  * Parameters and Defaults:
  * Key                       | Type    | Default
  * --------------------------------------------------------
  * object_id                 | String  | vmix_player
  * vmix_player               | String  | http://cdn-akm.vmixcore.com/core-flash/UnifiedVideoPlayer/UnifiedVideoPlayer.swf
  * callback                  | String  | N/A
  * player_id                 | String  | N/A
  * token                     | String  | N/A
  * services_url              | String  | http://cdn-akm.vmixcore.com/core-flash/UnifiedVideoPlayer/services.xml
  * params                    | Array   | N/A
  * params.allowScreenAccess  | String  | always
  * params.allowFullScreen    | String  | true
  * params.wmode              | String  | transparent
  * params.flashVars          | String  | player_id=$player_id&services_url=$services_url&env=&token=$token . $callback
  * params.*                  | String  | N/A
  *
  * @return string
  */
  public function getEmbed($args)
  {
  
    //If not token or player ID, return false
    if (empty($args['token']) || empty($args['player_id']) || ! isset($args['token']) || ! isset($args['player_id'])) { return false; }

    //Set some defaults
    $object_id = (!isset($args['object_id']) || empty($args['object_id'])) ? 'vmix_player' : $args['object_id'];
    $vmix_player = (!isset($args['vmix_player']) || empty($args['vmix_player'])) ? 'http://cdn-akm.vmixcore.com/core-flash/UnifiedVideoPlayer/UnifiedVideoPlayer.swf' : $args['vmix_player'];
    $callback = (! empty($callback)) ? '&event_handler=' . $callback : '';
    $token = $args['token'];
    $player_id = $args['player_id'];
    $services_url = (!isset($args['services_url']) || empty($args['services_url'])) ? 'http://cdn-akm.vmixcore.com/core-flash/UnifiedVideoPlayer/services.xml' : $args['services_url'];
    
    //Object Parameters
    $params             = array();
    $params['object']   = NULL;
    $params['embed']    = array();
    
    $params['default']  = array(
      'allowScriptAccess' =>  'always',
      'allowFullScreen'   =>  'true',
      'wmode'             =>  'transparent'
    );

    //Loop thru params and set everything straight
    if (isset($args['params']) && @count($args['params']) > 0) {
      foreach ($args['params'] as $name => $value) {
        if (strtolower($name) == 'flashvars') {
          $params['default']['flashVars'] = $value;
        }
        else {
          $params['object'] .= '<param name="' . $name .'" value="' . $value .'" />';
          array_push($params['embed'], $name . '=' . $value);
          $params['default'][$name] = true;
        }
      }
      
      foreach ($params['default'] as $name => $value) {
        if ($value !== true) {
          $params['object'] .= '<param name="' . $name .'" value="' . $value .'" />';
          array_push($params['embed'], $name . '=' . $value);
        }
      }
    }
    
    //Figure if flashVars are coming from user
    if (! isset($params['default']['flashVars'])) {
      $params['default']['flashVars'] = 'player_id=' . $player_id . '&services_url=' . $services_url . '&env=&token=' . $token . $callback;
    }
    
    //Create embed code
    $embed =  '<object id="' . $object_id . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="' . $args['width'] . '" height="' . $args['height'] . '"';
    $embed .= ' codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">';
    $embed .= '<param name="movie" value="' . $vmix_player . '?player_id=' . $player_id . '" />';
    $embed .= $params['object'];
    $embed .= '<param name="flashVars" value="' . $params['default']['flashVars'] . '" />';
    $embed .= '<embed name="' . $object_id . '" src="' . $vmix_player . '?player_id=' . $player_id .'"';
    $embed .= ' width="' . $args['width'] . '" height="' . $args['height'] . '" ' . implode(' ', $params['embed']) . ' type="application/x-shockwave-flash"';
    $embed .= ' swliveconnect="true" pluginspage="http://www.adobe.com/go/getflashplayer" flashVars="' . $params['default']['flashVars'] . '"></embed></object>';
    
    return $embed;
  }
  
  /**
  * Pieces together the query to be used.
  *
  * @param  array   $arr        The array of keys and values to use
  * @return string
  */
  protected function get_query($arr)
  {
    $query = array();
    if (@count($arr) > 0 && is_array($arr)) {
      foreach ($arr as $k => $v) {
        $k = ($k == 'response_type') ? 'output' : $k;
        array_push($query, "$k=$v");
      }
      return '&' . implode('&', $query);
    }
  }
  
  /**
  * Finds the correct page to call in the VMIX API from
  * the action that is being called.
  *
  * @param  string  $action     The action or method that is being called
  * @return string
  */
  protected function find_page($action)
  {
    foreach ($this->pages as $page) {
      if (stristr($action, $page)) {
        return $page;
      }
    }
  }
  
  /**
  * Turns the data returned from VMIX API into an array.
  *
  * @param  string  $string     The string to evaluate
  * @return string
  */
  protected function unserialize_data($data)
  {
    if ($data instanceof SimpleXMLElement || $data instanceof stdClass) $data = (array) $data;
    if (is_array($data)) {
      foreach ($data as &$item) {
        $item = $this->unserialize_data($item);
      }
    }
    return $data;
  }

}
?>