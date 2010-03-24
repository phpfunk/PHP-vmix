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
    $ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, 'http://' . $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, TRUE);
    curl_setopt ($ch, CURLOPT_HEADER, FALSE);
    curl_setopt ($ch, CURLOPT_TIMEOUT, FALSE);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		
    //Get the response
    $response = curl_exec($ch);
    if (!$response) {
      $response = curl_error($ch);
    }
    else {
      if ($this->response_type == 'xml') {
        $response = $this->unserialize_data(simplexml_load_string($response));
      }
      else {
        $response = $this->unserialize_data(json_decode($response));
      }
    }
    curl_close($ch);
    return $response;
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