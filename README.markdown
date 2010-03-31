### Description
Lightweight Vmix API Client

### Requirements
PHP 5+, json_encode(), SimpleXML, Vmix Account

### API Documentation
[http://support.vmixcore.com/index.php?CategoryID=65](http://support.vmixcore.com/index.php?CategoryID=65)

### Brief Rundown
This is a very easy-to-use and lightweight Vmix API client written in PHP. It makes use of the __call method so that you can easily call any valid API method without any of the methods actually being defined.

Let's say you want to call the method getMediaList from the Vmix API. Even thought is is not defined you simply:

    $vmix->getMediaList(array('collection_ids'=>$ids));

The wrapper will figure there is not method called getMediaList and call the Vmix API using the REST API. The arguments should be an array with the keys being the argument name and the value being the value you want to set to the key. Super simple.

### Response Type
By default you can get the data back as XML or JSON. Since we are using PHP here I transform both of those types to an array. No objects are returned just an array full of your data.


### Simple Example
    $vmix = new Vmix('ID','PASS');
    $collections = $vmix->getCollections();
    foreach ($collections['collection'] as $id => $arr) {
      //Do something
    }