<?php 
  require "../includes/functions.php";
  require "../includes/settings.php";
  
  $urlBase = "https://api.hubapi.com/";
  $listaID = "";
  $epGetListContacts = "contacts/v1/lists/$listaID/contacts/all";
  $params = "?count=100&propertyMode=value_only&hapikey=$apiKey";
  $properties = "&property=email";
  $responseGet = json_decode(apiCall($urlBase.$epGetListContacts.$params.$properties,"GET",""));

  foreach($responseGet->contacts as $key => $contacto){
    $email = $contacto->properties->email->value;
    $vid = $contacto->vid;
    $whatsapp = connectToDb("read","predictivo","email",$email,null,null)["whatsapp"];

    if($whatsapp){
      $epContactByVid = "https://api.hubapi.com/contacts/v1/contact/vid/$vid/profile?hapikey=$apiKey";

      $dataContact = array(
        array(
          "property"=>"whatsapp",
          "value"=>$whatsapp
        )
      );

      $payload = json_encode(array("properties" => $dataContact));

      $responseUpdateContact = json_decode(apiCall($epContactByVid.$properties,"POST",$payload));
      echo "ok";
    }
  }
?>