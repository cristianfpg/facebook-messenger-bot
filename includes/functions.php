<?php 
  // no aplica para brasil y peru
  function origenSwitch($pre,$origen){
    switch($origen){
      case "Bachiller": case "Plataforma": case "Extranjero":
        // return $pre." ".$origen;
        return $origen." ".$pre;
      case "Tráfico Directo": case "Google Orgánico": case "AdWords": case "Facebook Pagado": case "Facebook Orgánico": case "Facebook Lead Forms": case "Whatsapp Nacional Web": case "Grupos Whatsapp": case "Digital": case "Web": case "Facebook":
        // return $pre." Digital";
        return "Digital ".$pre;
      case "Ferias":
        // return $pre." Bachiller";
        return "Bachiller ".$pre;
      case "Examen Estatal": case "Intercambio Internacional Entrante": case "Internado Rotatorio y Prácticas": case "Grupos Whatsapp": 
        // return $pre." Plataforma";
        return "Plataforma ".$pre;
      default:
        return "NO APLICA";
    }
  }

  function origenSwitchBrasilPeru($pre,$origen){
    switch($origen){
      case "Ferias": case "Grupos Whatsapp":
        // return $pre." Bachiller";
        return "Bachiller ".$pre;
      case "Digital": case "Bachiller": case "Plataforma": case "Extranjero":
        // return $pre." ".$origen;
        return $origen." ".$pre;
      case "Tráfico Directo": case "Google Orgánico": case "Adwords": case "Facebook Prepagado": case "Facebok Orgánico": case "Facebook Lead Forms": case "Whatsapp Nacional Web": case "Web": case "Facebook":
        // return $pre." Digital";
        return "Digital ".$pre;
      default:
        return "NO APLICA";
    }
  }

  function getDealstage($ds){
    switch($ds){
      case "Plataforma":
      case "Bachiller": 
      case "Whatsapp Nacional Web":
      case "Intercambio Internacional Entrante": 
      case "Internado Rotatorio y Prácticas":
      case "Ferias":
      case "Grupos Whatsapp":
        return "Interesado -Lead Tibio";
      default:
        return "Cliente Potencial - Lead Frio";
    }
  }

  function apiCall($ep,$request,$payload){
    $curl = curl_init($ep);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($payload)));
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $request);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
  
    $response = curl_exec($curl); 
    curl_close($curl);
    return $response;
  }

  function limitDetect($queries){
    $queries++;
    if($queries == 10){
      sleep(1);
      return 0;
    }
    return $queries;
  }

  function writeFile($errorMsg){
    $file = fopen("errores.txt", "a+");
    fwrite($file, $errorMsg);
    fclose($file);
  }

  function handleWriteFile($msg,$filename){
    $file = fopen($filename.".txt", "a+");
    fwrite($file, $msg."\n");
    fclose($file);
  }

  /* FUNCIONES BOT PARA EL MANEJO DE PLANTILLAS */

  // *FUNCION "CRUD" PARA REALIZAR ACCIONES EN LA DB*
  // RECIBE LA ACCION, EL NOMBRE DE LA TABLA Y LOS PARAMETROS QUE DEPENDAN DE LA ACCION
  function connectToDb($crud,$table,$keys,$values,$findKey,$findValue){ 
    try {
      $pdo = new PDO("mysql:host=localhost;dbname=dbname", "root", "root");
      $pdo->exec("SET NAMES 'utf8';");
      $pdoReturn = null;
      switch($crud){
        case "all":
          $sql = $pdo->query("SELECT * FROM $table");
          $pdoReturn = $sql->fetchall();
          break;
        case "read":
          $sql = $pdo->query("SELECT * FROM $table WHERE $keys = '$values'");
          $pdoReturn = $sql->fetch();
          break;
        case "create":
          $sql = "INSERT INTO $table ($keys) VALUES ('$values')";
          $pdo->prepare($sql)->execute();
          break;
        case "update":
          $sql = "UPDATE $table SET $keys='$values' WHERE $findKey='$findValue'"; 
          $pdo->prepare($sql)->execute();
          break;
        case "multiple_clean":
          $query = "";
          foreach($keys as $k => $key){
            if($k != 0) $query .= ",";
            $query .= "$key=''";
          }
          $sql = "UPDATE $table SET $query WHERE $findKey='$findValue'"; 
          $pdo->prepare($sql)->execute();
          break;
        case "get_columns":
          $q = $pdo->prepare("DESCRIBE $table");
          $q->execute();
          $pdoReturn = $q->fetchAll(PDO::FETCH_COLUMN);
          break;
      }
      $pdo = null;
      return $pdoReturn;
    } catch (PDOException $e) {
        print "¡Error!: " . $e->getMessage() . "<br/>";
        die();
    }
  }

  // *PETICION DIRECTA AL ENDPOINT DE FACEBOOK MESSENGER*
  // RECIBE LOS DATOS A ENVIAR AL ENDPOINT Y EL TOKEN DE AUTENTICACION
  function facebookApiCall($response,$accessToken,$senderData){
    $ch = curl_init("https://graph.facebook.com/v3.3/me/messages?access_token=".$accessToken);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    curl_close($ch);
  };

  // *EFECTO DE typing_on* (LOS TRES PUNTICOS QUE SALEN SIMULANDO QUE EL BOT ESTA ESCRIBIENDO ALGO EN ESE MOMENTO)
  // SE QUITAN DEPENDIENDO DEL LARGO DE LA RESPUESTA (CANTIDAD DE CARACTERES)
  function efectoTypingOn($answer,$senderId,$accessToken,$senderData){
    $texto = $answer != "" ? $answer : "vacio";
    handleAction('typing_on',$senderId,$accessToken,$senderData);
    $cantidadcaracteres = strlen($texto);
    usleep($cantidadcaracteres*12000);
  }

  // *PLANTILLA PARA MENSAJE DE TEXTO*
  // RECIBE LA RESPUESTA, EL senderid (EL ID UNICO POR CHAT DE MESSENGER) Y EL accessToken (TOKEN DE AUTENTICACION DEL APP)
  function handleText($answer,$senderId,$accessToken,$senderData){
    efectoTypingOn($answer,$senderId,$accessToken,$senderData);
    $response = [
      'recipient' => ['id' => $senderId],
      'message' => [ 'text' => $answer]
    ];
    facebookApiCall($response,$accessToken,$senderData);
  }

  // *PLANTILLA PARA ACCION DEL CHAT*
  // RECIBE LA ACCION (VISTO, LEIDO, SIMULACION DE ESCRITURA (typing_on)) senderid Y accessToken
  function handleAction($action,$senderId,$accessToken,$senderData){
    $response = [
      'recipient' => ['id' => $senderId],
      'sender_action' => $action
    ];
    facebookApiCall($response,$accessToken,$senderData);
  }

  // *PLANTILLA DE BOTONES*
  // ES UNA PLANTILLA PARA DESPLEGAR BOTONES QUE PERMITE FACEBOOK EN MESSENGER (https://developers.facebook.com/docs/messenger-platform/send-messages/template/button)
  // RECIBE LA PREGUNTA, OPCIONES, TIPO DE PLANTILLA, senderid Y accessToken
  function handleTemplate($pregunta, $opciones, $plantilla,$senderId,$accessToken,$senderData){    
    efectoTypingOn($pregunta,$senderId,$accessToken,$senderData);
    $response = [
      'recipient' => ['id' => $senderId],
      'message' => [
        'attachment' => [
          'type' => 'template',
          'payload' => [
            'template_type' => $plantilla,
            'text' => $pregunta,
            'buttons' => $opciones
          ]
        ]
      ]
    ];
    facebookApiCall($response,$accessToken,$senderData);
  };

  // *PLANTILLA PARA MENSAJES RAPIDOS*
  // LA USO PARA PODER DARLE AL USUARIO VARIAS OPCIONES, ALGUNOS ELEMENTOS QUE TIENE FACEBOOK SOLO PERMITE UN MAXIMO DE 3 OPCIONES
  // RECIBE PREGUNTA, OPCIONES, senderid Y accessToken
  function handleQuickReplies($pregunta,$opciones,$senderId,$accessToken,$senderData){
    if(count($opciones) <= 0) return true;
    efectoTypingOn($pregunta,$senderId,$accessToken,$senderData);
    $replies = array();
    foreach($opciones as $opcion){
      $reply = null;
      $reply["title"] = $opcion;
      $reply["content_type"] = "text";
      $reply["payload"] = "QUICK_PAYLOAD";
      array_push($replies,$reply);
    }

    $response = [
      'recipient' => ['id' => $senderId],
      'message' => [
        'text' => $pregunta,
        'quick_replies' => $replies
      ]
    ];

    facebookApiCall($response,$accessToken,$senderData);
  }

  // *PLANTILLA DE CARTAS*
  // ES UNA PLANTILLA PARA DESPLEGAR CARTAS GENERICAS QUE PERMITE FACEBOOK EN MESSENGER (https://developers.facebook.com/docs/messenger-platform/send-messages/template/generic/)
  // RECIBE OPCIONES, TIPO DE PLANTILLA, senderid Y accessToken
  function handleGenericTemplate($opciones,$senderId,$accessToken,$senderData){
    if(count($opciones) <= 0) return true;

    $elements = array();
    foreach($opciones as $opcion){
      $element = null;
      $element["title"] = $opcion["titulo"];
      $element["subtitle"] = "";
      $element["image_url"] = $opcion["imagen"];
      $element["buttons"] = null;
      $element["buttons"][0]["type"] = 'postback';
      $element["buttons"][0]["title"] = $opcion["boton"]["titulo"];
      $element["buttons"][0]["payload"] = $opcion["boton"]["payload"];
      array_push($elements,$element);
    }

    $response = [
      'recipient' => ['id' => $senderId],
      'message' => [
        'attachment' => [
          'type' => 'template',
          'payload' => [
            'template_type' => 'generic',
            'elements' => $elements
          ]
        ]
      ]
    ];
    facebookApiCall($response,$accessToken,$senderData);
  };

  // FUNCIONES BOT PARA LA MATRIZ DE RESPUESTAS
  
  function stripAccents($str) {
    return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
  }

  function analizarPalabraConKeywords($texto,$keywords){
    $texto = stripAccents($texto);
    $texto = strtolower($texto);
    $masAcertadas = array();
    
    foreach ($keywords as $keyword) {
        $st = similar_text($texto, $keyword, $perc);
        $masAcertadas[$keyword] = $perc;
    }
    
    arsort($masAcertadas);
    $keyMA = key($masAcertadas);
    $valueMA = reset($masAcertadas);

    return array("palabra" => $keyMA, "similitud" => $valueMA);
  }

  function detectarPalabras($mensaje, $keywords){
    $mensaje = explode(" ",$mensaje);
    $palabrasDominantes = array();

    foreach($mensaje as $palabra){
      if($palabra != ""){
        $resultado = analizarPalabraConKeywords($palabra, $keywords);
        if($resultado["similitud"] > 85 && $resultado["similitud"] > $palabrasDominantes["similitud"]){
          // $palabrasDominantes = $resultado;
          array_push($palabrasDominantes, $resultado);
        }
      }
    }

    return $palabrasDominantes;
  }

  function handleMessageByLayers($mensaje, $keywords, $normal){
    $palabrasDetectadas = detectarPalabras($mensaje, $keywords);

    if($normal){
      $valUno = $palabrasDetectadas[0]["palabra"];
      $valDos = $palabrasDetectadas[1]["palabra"] ? $palabrasDetectadas[1]["palabra"] : $valUno;
    }else{
      $valUno = $palabrasDetectadas[1]["palabra"];
      $valDos = $palabrasDetectadas[0]["palabra"];
    }

    
    if($valUno == "" || $valDos == "") return "";

    $respuestaIDQuery = connectToDb("read","keywords_respuestas","keyword",$valUno."-".$valDos,null,null);
    $respuestaQuery = connectToDb("read","respuestas","id",$respuestaIDQuery["respuesta"],null,null);
    
    return $respuestaQuery["respuesta"];
  };

  function respuestaAutomatica($mensaje, $sinRespuesta){
    $kwQuery = connectToDb("all","keywords",null,null,null,null);
    $keywords = array();
  
    foreach($kwQuery as $kw){
      $kwCheck = $kw["keyword"];
      if(!(array_search($kwCheck,$keywords))) array_push($keywords, $kwCheck);
    }
    
    $request = handleMessageByLayers($mensaje, $keywords, 1);
    
    if($request == "") $request = handleMessageByLayers($mensaje, $keywords, 0);
    $respuesta = $request == "" ? $sinRespuesta : $request;

    return $respuesta;
  }

  function sendToHubspot($email,$data){
    $ep = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/$email/?hapikey=$apiKey";

    $payload = json_encode(array("properties" => $data));
    $call = json_decode(apiCall($ep,"POST",$payload));

    if($call->status == "error"){
      $errorMsg = "error -> ".json_encode($call)."\n";
      writeFile($errorMsg);
    }
  }

  function emailValidator($messageText){
    $pattern = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
    preg_match_all($pattern, $messageText, $matches);
    return $matches[0][0];
  }

  function sumarIntento($senderData, $reset){
    $intentos = "0";
    if($reset != "reset") $intentos = intval($senderData["intentos"]) + 1;
    connectToDb("update","predictivo","intentos",$intentos,"senderid",$senderData["senderid"]);
  }
?>