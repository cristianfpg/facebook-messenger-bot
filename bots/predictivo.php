<?php
  /* 
    ----------- BOT UNIFRANZ V2 - PREDICTIVO ----------------
    
    ESTA ES LA SEGUNDA VERSION DEL BOT DE UNIFRANZ, CONSISTE EN 2 PARTES:

    1. FILTAR POR MEDIO DE PREGUNTAS CON OPCION MULTIPLE PARA QUE EL LEAD QUE FINALMENTE VIAJE A HUBSPOT SEA UNA PERSONA QUE QUIERA ESTUDIAR UN PREGRADO
    2. POR MEDIO DE UNA LOGICA SIMPLE DETECTANDO 2 KEYWORDS, PODER DETECTAR LA NECESIDAD DEL USUARIO Y ARROGARLE UNA POSIBLE RESPUESTA QUE RESUELVA ESA DUDA

    PARA ESE ENTENDIMIENTO DE KEYWORDS USE LA FUNCION similar_text QUE CON 2 PALABRAS, QUE DETECTO CON UNA BASE DE DATOS PREVIAMENTE SUBIDA EN coloralcuadrado.com SI CUMPLE CON UN ALTO PORCENTAJE DE SIMILITUD, LE ASIGNO LA RESPUESTA INDICADA EN LA MATRIZ QUE LOS ANALISTAS SUMINISTRAN

    https://www.php.net/manual/es/function.similar-text.php

    ME BASE EN EL CURSO DE PLATZI DE CREACION DE BOTS EN FACEBOOK, EN EL CURSO LO HACEN CON NODEJS Y YO LE HICE LA TRADUCCION A PHP, PERO VIENE SIENDO LO MISMO
  */

  /* 
  
  ESTAS LINEAS SON PARA LA CONFIGURACION INICIAL DEL WEBHOOK, AL CREAR LA APLICACION EN developers.facebook.com ES NECESARIO ACTIVAR TANTO EL FACEBOOK MESSENGER COMO EL WEBHOOK, QUE ES EL QUE VA A DETECTAR LOS MENSAJES Y ENVIARLOS AL SERVIDOR DE PHP

  LA VARIABLE $hubVerifyToken TIENE LA PALABRA CLAVE QUE PIDE FACEBOOK PARA CREAR EL WEBHOOK
  */
  $hubVerifyToken = ""; 
  if ($_REQUEST["hub_verify_token"] === $hubVerifyToken) {
    echo $_REQUEST["hub_challenge"];
    exit;
  }

  /*

  ---------------- INICIO DEL PROYECTO -------------------

  ESTAS LINEAS SON PARA UNA VALIDACION SIMPLE DE INFORMACION VACIA, SI NO TRAE NADA, TERMINA EL SCRIPT

  */
  $input = json_decode(file_get_contents("php://input"), true); 
  if(empty($input)) exit;

  require "../includes/functions.php";

  /* 
  
  CONFIGURACION INICIAL Y VARIABLES DE LAS RESPUESTAS POSIBLES DEPENDIENDO AL CASO
  
  */

  $events = $input["entry"][0]["messaging"]; // ESTA ES LA VARIABLE CLAVE, ACA SE GUARDA TODA LA INTERACCION DEL CHAT DEL USUARIO

  $senderId = $events[0]["sender"]["id"];
  $senderData = connectToDb("read","predictivo","senderid",$senderId,null,null);

  $accessToken = "";
  $keysParaLimpiar = array("chatbot","saludo","necesidad","oferta","interes","nombre","email","sede","carrera","contacto","whatsapp","llamada","pregunta_uno","pregunta_dos","pregunta_tres","bot_activo","intentos","calificacion");
  $saludoParteUno = "¡Hola! Gracias por contactarte con nosotros.";
  $saludoParteDos = "Soy Franz tu asesor virtual y estoy aquí para ayudarte en tu proceso de admisión.";
  $saludoParteTres = "Si en algún momento quieres chatear con un asesor escribe 'chatear'.";
  $pedirNecesidad = "Elige por favor una de las siguientes opciones de abajo para iniciar:";
  $pedirInteres = "¿Cuál es tu interés?";
  $pedirOferta = "¿En que oferta estás interesado?";
  $despedidaSinInteres = "¡Gracias por contactarte con nosotros! Hasta pronto.";
  $pedirNombre = "Para iniciar tu proceso ¿por favor me confirmas tus nombres y apellidos?";
  $pedirCorreo = "¡Gracias! ¿por favor me confirmas tu correo electrónico?";
  $pedirSede = "¿En qué sede te gustaría estudiar?";
  $pedirCarrera = "¿Qué carrera te gustaría estudiar?";
  $pedirContacto = "¡Genial! ¿Por cuál medio te gustaría ser contactado?";
  $pedirWhatsapp = "Confírmanos por favor tu whatsapp";
  $pedirTelefono = "Confírmanos por favor tu número de contacto";
  $preguntaDudasParteUno = "Perfecto, continuemos";
  $preguntaDudasParteDos = "¿Sobre qué tema tienes dudas, para brindarte información más detallada?";
  $otraPregunta = "¿Tienes otra duda para tu proceso de admisión?";
  $sinRespuesta = connectToDb("read","respuestas","id",1,null,null)["respuesta"];
  $despedida = "Me alegró mucho saludarte. Hasta pronto :D";
  $finBot = "Pronto un asesor se comunicará contigo";
  $calificacion = "¿Te pareció una buena experiencia?";
  $iconoCommunity = 👥;

  $sedesData = array(
    [
      "titulo" => "Bolivia",
      "imagen" => "https://coloralcuadrado.com/unifranz/assets/img/sedes/bot-cochabamba.jpg",
      "boton" => [
        "titulo" => "Cochabamba",
        "payload" => "SEDE_PAYLOAD"
      ]
    ],[
      "titulo" => "Bolivia",
      "imagen" => "https://coloralcuadrado.com/unifranz/assets/img/sedes/bot-el-alto.jpg",
      "boton" => [
        "titulo" => "El Alto",
        "payload" => "SEDE_PAYLOAD"
      ]
    ],[
      "titulo" => "Bolivia",
      "imagen" => "https://coloralcuadrado.com/unifranz/assets/img/sedes/bot-la-paz.jpg",
      "boton" => [
        "titulo" => "La Paz",
        "payload" => "SEDE_PAYLOAD"
      ]
    ],[
      "titulo" => "Bolivia",
      "imagen" => "https://coloralcuadrado.com/unifranz/assets/img/sedes/bot-santa-cruz.jpg",
      "boton" => [
        "titulo" => "Santa Cruz",
        "payload" => "SEDE_PAYLOAD"
      ]
    ]
  );
  $carrerasDisponibles = array(
    "Derecho" => "derecho",
    "Psicología" => "psicologia",
    "Periodismo" => 'periodismo',
    "Adm Empresas" => 'administracion_de_empresas',
    "Contaduría" => 'contaduria_publica',
    "Ing Comercial" => 'ingenieria_comercial',
    "Adm Hotel - Turismo" => 'administracion_de_hoteleria_y_turismo',
    "Ing Económ Financie" => 'ingenieria_economica_y_financiera',
    "Ing Económica" => "ingenieria_economica",
    "Publicidad - Market" => "publicidad_y_marketing",
    "Diseño Gráfico" => "diseno_grafico_y_produccion_crossmedia",
    "Arquitectura" => "arquitectura",
    "Medicina" => "medicina",
    "Odontología" => "odontologia",
    "Bioquím y Farmacia" => "bioquimica_y_farmacia",
    "Enfermería" => "enfermeria",
    "Ing Sistemas" => "ingenieria_de_sistemas",
  );

  $formasContacto = array(
    [
      'type' => 'postback',
      'title'=>'Whatsapp',
      'payload'=>'CONTACTO_PAYLOAD'
    ],[
      'type' => 'postback',
      'title'=>'Email',
      'payload'=>'CONTACTO_PAYLOAD'
    ],[
      'type' => 'postback',
      'title'=>'Llamada',
      'payload'=>'CONTACTO_PAYLOAD'
    ]
  );

  $tiposInteres = array(
    [
      'type' => 'postback',
      'title'=>'Estoy curioseando',
      'payload'=>'INTERES_PAYLOAD'
    ],[
      'type' => 'postback',
      'title'=>'Considero opciones',
      'payload'=>'INTERES_PAYLOAD'
    ],[
      'type' => 'postback',
      'title'=>'Quiero estudiar aquí',
      'payload'=>'INTERES_PAYLOAD'
    ]
  );
  
  /* 
  
  ESTE ES UN EJEMPLO DE PETICION CURL PARA EJECUTAR DIRECTAMENTE EN LA CONSOLA, CREA LOS get_started QUE INICIAN EL BOT POR MEDIO DE PAYLOAD Y EL persistent_menu QUE SE MANTIENE SIEMPRE EN LA CONVERSACION EN FORMA DE MENU

  DOCUMENTACION:

  https://developers.facebook.com/docs/messenger-platform/discovery/welcome-screen/
  
  */
  
  // curl -X POST -H "Content-Type: application/json" -d '{
  //   "get_started":{
  //     "payload":"GET_STARTED"
  //   }
  // }' "https://graph.facebook.com/v4.0/me/messenger_profile?access_token=<token>"


  // curl -X DELETE -H "Content-Type: application/json" -d '{
  //   "fields":[
  //     "get_started",
  //     "persistent_menu",
  //   ]
  // }' "https://graph.facebook.com/v4.0/me/messenger_profile?access_token=<token>"

  // curl -X POST -H "Content-Type: application/json" -d '{
  //   "persistent_menu": [
  //       {
  //           "locale": "default",
  //           "composer_input_disabled": false,
  //           "call_to_actions": [
  //               {
  //                   "type": "postback",
  //                   "title": "Quiero chatear con un agente",
  //                   "payload": "FIN_BOT"
  //               }
  //           ]
  //       }
  //   ]
  // }' "https://graph.facebook.com/v4.0/me/messenger_profile?access_token=<token>"
  
  function asignarAccessToken($chatbot){
    switch($chatbot){
      case "General":
      case "La Paz":
      case "Cochabamba":
      case "Santa Cruz":
      case "El Alto":
        return "token";
    }
  }

  /*
  
  DETECCION INICIAL DEL get_started ACA SE DE QUE PAGINA VIENE Y LE ASIGNO SU TOKEN CORRESPONDIENTE, TOKEN QUE CREO EN developers.facebook.com

  */

  foreach($events as $event){
    if($event["postback"]){
      switch($event["postback"]["payload"]){
        case "GET_STARTED_UNIFRANZ":
        case "GET_STARTED_LA_PAZ":
        case "GET_STARTED_COCHABAMBA":
        case "GET_STARTED_SANTA_CRUZ":
        case "GET_STARTED_EL_ALTO":
        case "GET_STARTED":
        case "GET_STARTED_PRUEBAS":
          $chatbot = "";
          switch($event["postback"]["payload"]){
            case "GET_STARTED_UNIFRANZ":
              $chatbot = "General";
              break;
            case "GET_STARTED_LA_PAZ":
              $chatbot = "La Paz";
              break;
            case "GET_STARTED_COCHABAMBA":
              $chatbot = "Cochabamba";
              break;
            case "GET_STARTED_SANTA_CRUZ":
              $chatbot = "Santa Cruz";
              break;
            case "GET_STARTED_EL_ALTO":
              $chatbot = "El Alto";
              break;
            case "GET_STARTED":
              $chatbot = "Prueba v2";
              break;
            case "GET_STARTED_PRUEBAS":
              $chatbot = "Prueba v2 pruebas";
              break;
          }
          if($senderData){
            connectToDb("multiple_clean","predictivo",$keysParaLimpiar,null,"senderid",$senderId);
          }else{
            connectToDb("create","predictivo","senderid",$senderId,null,null);
          }
          $accessToken = asignarAccessToken($chatbot);            
          connectToDb("update","predictivo","chatbot",$chatbot,"senderid",$senderId);
          if($chatbot == "La Paz" || $chatbot == "Cochabamba" || $chatbot == "Santa Cruz" || $chatbot == "El Alto") connectToDb("update","predictivo","sede",$chatbot,"senderid",$senderId);
          break;
      }
    }
  }

  if(!$accessToken){
    $accessToken = asignarAccessToken($senderData["chatbot"]);
  }

  /*
  
  SI LA PERSONA NO ENTIENDE EN CIERTOS PUNTOS EL BOT, YO SUMO EN LA BASE DE DATOS intentos, LOS CUALES SI SUPERAN LOS 3 INTENTOS CIERRA EL BOT Y NO REPITE LA RESPUESTA INFINITAS VECES

  */

  if(intval($senderData["intentos"]) > 1){
    handleText($finBot,$senderId,$accessToken,$senderData);
    handleText($iconoCommunity,$senderId,$accessToken,$senderData);
    connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
    connectToDb("update","predictivo","intentos","","senderid",$senderId);
    die();
  }

  /* LA MAYORIA DE LAS FUNCIONES SE PENSARON POR QUE SE REPETIAN EN EL PROCESO, SE USAN CAMBIANDO LA DATA CORRESPONDIENTE, SOBRETODO PARA LAS PLANTILLAS DE LAS OPCIONES MULTIPLES */

  function welcomeMessage($saludoParteUno,$saludoParteDos,$saludoParteTres,$senderId,$accessToken,$senderData){
    handleText($saludoParteUno,$senderId,$accessToken,$senderData);
    handleText($saludoParteDos,$senderId,$accessToken,$senderData);
    handleText($saludoParteTres,$senderId,$accessToken,$senderData);
  }

  function carrerasPorSede($sedeEscogida){
    switch($sedeEscogida){
      case "La Paz":
        return array("Adm Empresas", "Adm Hotel - Turismo", "Contaduría", "Derecho", "Ing Comercial", "Ing Económica", "Psicología", "Otros La Paz");
      case "Cochabamba":
        return array("Adm Empresas", "Bioquím y Farmacia", "Derecho", "Ing Económ Financie", "Ing Comercial", "Medicina", "Odontología", "Psicología", "Publicidad - Market", "Diseño Gráfico", "Ing Sistemas");
      case "El Alto":
        return array("Adm Empresas","Adm Hotel - Turismo","Contaduría","Derecho","Ing Comercial","Ing Sistemas","Otros El Alto");
      case "Santa Cruz":
        return array("Derecho","Psicología","Adm Empresas","Adm Hotel - Turismo","Contaduría","Ing Comercial","Otros Santa Cruz");
    }
  }

  function otrasCarrerasPorSede($sedeEscogida){
    switch($sedeEscogida){
      case "Otros Santa Cruz":
        return array("Arquitectura","Publicidad - Market","Diseño Gráfico","Bioquím y Farmacia","Medicina","Odontología","Ing Sistemas");
      case "Otros El Alto":
        return array("Psicología","Diseño Gráfico","Medicina","Enfermería","Odontología","Bioquím y Farmacia");
      case "Otros La Paz":
        return array("Periodismo","Publicidad - Market","Ing Sistemas","Diseño Gráfico","Medicina","Odontología","Bioquím y Farmacia");
    }
  }

  function finRecoleccionDatosIniciales($senderData,$carrerasDisponibles,$preguntaDudasParteUno,$preguntaDudasParteDos,$senderId,$accessToken,$llamada,$whatsapp){
    $contacto = $senderData["contacto"] ? $senderData["contacto"] : "Email";

    $dataContact = array(
      array(
        "property"=>"interes",
        "value"=>$senderData["interes"]
      ),array(
        "property"=>"firstname",
        "value"=>$senderData["nombre"]
      ),array(
        "property"=>"sede",
        "value"=>$senderData["sede"]
      ),array(
        "property"=>"carrera",
        "value"=>$carrerasDisponibles[$senderData["carrera"]]
      ),array(
        "property"=>"forma_de_contacto",
        "value"=>$contacto
      ),array(
        "property"=>"c_mo_se_enter_de_nosotros_",
        "value"=>"Bot Facebook"
      ),array(
        "property"=>"hs_analytics_source",
        "value"=>"DIRECT_TRAFFIC"
      )
    );

    if($llamada != ""){
      array_push(
        $dataContact,
        array(
          "property"=>"phone",
          "value"=>$llamada
        )
      );
    }

    if($whatsapp != ""){
      array_push(
        $dataContact,
        array(
          "property"=>"whatsapp",
          "value"=>$whatsapp
        )
      );
    }

    /* 

      FUNCION QUE GUARDA EN HUBSPOT

    */
    sendToHubspot($senderData["email"],$dataContact);

    handleText($preguntaDudasParteUno,$senderId,$accessToken,$senderData);
    handleText($preguntaDudasParteDos,$senderId,$accessToken,$senderData);
  }
  
  $necesidadesUnifranz = array("Soy estudiante","Busco trabajo","Quiero estudiar","Otro");
  $ofertasUnifranz = array("Programa","Inglés","Posgrado");
  $preguntaSoyEstudiante = "pregunta soy estudiante";
  $faqSoyEstudiante = array("prégñ 1?.","preg 2","preg 3");

  function detectarNecesidad($messageText,$pedirNecesidad,$necesidadesUnifranz,$pedirOferta,$ofertasUnifranz,$despedidaSinInteres,$iconoCommunity,$senderId,$accessToken,$senderData,$preguntaSoyEstudiante,$faqSoyEstudiante){
    switch($messageText){
      case "Soy estudiante":
        connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);

        // handleQuickReplies($preguntaSoyEstudiante,$faqSoyEstudiante,$senderId,$accessToken,$senderData);
        handleText("Conoce qué preguntas frecuentes ya tienen solución para tus procesos.",$senderId,$accessToken,$senderData);
        handleText("1. Haz clic al siguiente link para acceder a sus clases virtuales: https://tusasignaturas-unifranz.herokuapp.com/",$senderId,$accessToken,$senderData);
        handleText("2. Si necesitas algún tutorial para el uso de las plataformas, ingresa a este link: www.virtual.unifranz.edu.bo",$senderId,$accessToken,$senderData);
        handleText("3. Para pagos online, debes ingresar a https://sea.unifranz.edu.bo/ y tener una tarjeta de crédito/debito activa.",$senderId,$accessToken,$senderData);
        handleText("4. Recuerda que todos los pagos presenciales estarán habilitados después de la cuarentena.",$senderId,$accessToken,$senderData);
        handleText("5. Los hitos y evaluaciones reprogramadas son: Hito 2: desde el 30 de marzo al 11 de abril de 2020. Primera Evaluación: a partir del 30 de marzo al 11 de abril de 2020. ",$senderId,$accessToken,$senderData);
        handleText("6. Comunícate con los Jefes de Enseñanza Aprendizaje de tu sede si tienes más dudas:",$senderId,$accessToken,$senderData);
        handleText("Cochabamba: 75937304",$senderId,$accessToken,$senderData);
        handleText("El Alto: 72014789",$senderId,$accessToken,$senderData);
        handleText("La Paz: 78880202 ",$senderId,$accessToken,$senderData);
        handleText("Santa Cruz: 76399061",$senderId,$accessToken,$senderData);
        handleText("Para cualquier consulta, recuerda nuestras líneas de Atención al Estudiante están disponibles así:",$senderId,$accessToken,$senderData);
        handleText("Cochabamba: 67401006",$senderId,$accessToken,$senderData);
        handleText("El Alto: 67001007",$senderId,$accessToken,$senderData);
        handleText("La Paz: 67001006",$senderId,$accessToken,$senderData);
        handleText("Santa Cruz: 72049306",$senderId,$accessToken,$senderData);

        handleText($despedidaSinInteres,$senderId,$accessToken,$senderData);
        handleText($iconoCommunity,$senderId,$accessToken,$senderData);
        break;
      case "Busco trabajo":
        connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
        $respuesta = connectToDb("read","respuestas","id",12,null,null)["respuesta"];
        handleText($respuesta,$senderId,$accessToken,$senderData);
        handleText($despedidaSinInteres,$senderId,$accessToken,$senderData);
        handleText($iconoCommunity,$senderId,$accessToken,$senderData);
        break;
      case "Quiero estudiar":
        handleQuickReplies($pedirOferta,$ofertasUnifranz,$senderId,$accessToken,$senderData);
        break;
      case "Otro":
        connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
        $respuesta = connectToDb("read","respuestas","id",3,null,null)["respuesta"];
        handleText($respuesta,$senderId,$accessToken,$senderData);
        handleText($despedidaSinInteres,$senderId,$accessToken,$senderData);
        handleText($iconoCommunity,$senderId,$accessToken,$senderData);
        break;
      default:
        handleQuickReplies($pedirNecesidad,$necesidadesUnifranz,$senderId,$accessToken,$senderData);
        sumarIntento($senderData,"");
        return true;
    }
    connectToDb("update","predictivo","necesidad",$messageText,"senderid",$senderId);
  }
  function detectarOferta($messageText,$pedirOferta,$ofertasUnifranz,$pedirInteres,$tiposInteres,$despedidaSinInteres,$iconoCommunity,$senderId,$accessToken,$senderData){
    $respuesta = connectToDb("read","respuestas","id",7,null,null)["respuesta"];
    switch($messageText){
      case "Inglés":
      // case "Diplomado":
      case "Posgrado":
        connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
        handleText($respuesta,$senderId,$accessToken,$senderData);
        handleText($despedidaSinInteres,$senderId,$accessToken,$senderData);
        handleText($iconoCommunity,$senderId,$accessToken,$senderData);        
        break;
      case "Programa":
        handleTemplate($pedirInteres,$tiposInteres,"button",$senderId,$accessToken,$senderData);
        break;
      default:
        handleQuickReplies($pedirOferta,$ofertasUnifranz,$senderId,$accessToken,$senderData);
        sumarIntento($senderData,"");
        return true;
    }
    connectToDb("update","predictivo","oferta",$messageText,"senderid",$senderId);
  }

  /* 

  ESTE PUNTO ES EL QUE RECIBE LA INTERACCION DEL USUARIO, PUEDE SER TEXTO CRUDO COMO PUEDEN SER PAYLOADS GENERADOS POR LAS PLANTILLAS

  LOS IF FUNCIONAN DE TAL FORMA QUE SI EN LA CONSULTA A LA BASE DE DATOS ESTA VACIA PIDA EL DATO NECESARIO PARA AVANZAR

  SI ESTA LA FUNCION sumarIntento ES EJEMPLO DE QUE EN ESE PUNTO EL USUARIO NO PASO LA DATA QUE PEDIA EL BOT Y POR ENDE CUENTA COMO UN INTENTO FALLIDO POR PARTE DEL USUARIO

  */
  foreach($events as $event){
    if($event["message"]){
      if($senderData["bot_activo"] == "no") break;
      $messageText = $event["message"]["text"];
      if($messageText == "chatear"){
        connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
        handleText($finBot,$senderId,$accessToken,$senderData);
        handleText($iconoCommunity,$senderId,$accessToken,$senderData);
        exit;
      }
      if($senderData){
        if($senderData["saludo"] != ""){
          if($senderData["necesidad"] == ""){
            detectarNecesidad($messageText,$pedirNecesidad,$necesidadesUnifranz,$pedirOferta,$ofertasUnifranz,$despedidaSinInteres,$iconoCommunity,$senderId,$accessToken,$senderData,$preguntaSoyEstudiante,$faqSoyEstudiante);
          }else if($senderData["oferta"] == ""){
            if($senderData["necesidad"] == "Soy estudiante"){
              switch($messageText){
                case "prégñ 1?.":
                  handleText("cierra bot",$senderId,$accessToken,$senderData);
                  break;
                default:
                  handleText("mensaje default",$senderId,$accessToken,$senderData);
              }
              connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
              handleText("cierra bot",$senderId,$accessToken,$senderData);
            }else{
              detectarOferta($messageText,$pedirOferta,$ofertasUnifranz,$pedirInteres,$tiposInteres,$despedidaSinInteres,$iconoCommunity,$senderId,$accessToken,$senderData);
            }
          }else if($senderData["interes"] == ""){
            handleTemplate($pedirInteres,$tiposInteres,"button",$senderId,$accessToken,$senderData);
            sumarIntento($senderData,"");
          }else if($senderData["nombre"] != "" && $senderData["email"] != "" && $senderData["sede"] != "" && $senderData["carrera"] != "" && $senderData["contacto"] != "" && (($senderData["contacto"] == "Whatsapp" && $senderData["whatsapp"] != "") || ($senderData["contacto"] == "Llamada" && $senderData["llamada"] != "") || $senderData["contacto"] == "Email")){
            if($senderData["pregunta_uno"] == ""){
              connectToDb("update","predictivo","pregunta_uno",$messageText,"senderid",$senderId);
              $respuesta = respuestaAutomatica($messageText,$sinRespuesta);
              handleText($respuesta,$senderId,$accessToken,$senderData);
              handleText($otraPregunta,$senderId,$accessToken,$senderData);
            }else if($senderData["pregunta_dos"] == ""){
              connectToDb("update","predictivo","pregunta_dos",$messageText,"senderid",$senderId);

              $respuesta = respuestaAutomatica($messageText,$sinRespuesta);
              handleText($respuesta,$senderId,$accessToken,$senderData);
              handleQuickReplies($calificacion,array(👍,👎),$senderId,$accessToken,$senderData);
            }else{
              if($messageText == 👍){
                connectToDb("update","predictivo","calificacion","Manito arriba","senderid",$senderId);
              }else if($messageText == 👎){
                connectToDb("update","predictivo","calificacion","Manito abajo","senderid",$senderId);
              }else{
                connectToDb("update","predictivo","calificacion",$messageText,"senderid",$senderId);
              }
              connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
              handleText($despedida,$senderId,$accessToken,$senderData);
              handleText($finBot,$senderId,$accessToken,$senderData);
              handleText($iconoCommunity,$senderId,$accessToken,$senderData);
            }
            // }else if($senderData["pregunta_tres"] == ""){
            //   connectToDb("update","predictivo","pregunta_tres",$messageText,"senderid",$senderId);
            //   connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
            //   $respuesta = respuestaAutomatica($messageText,$sinRespuesta);
            //   handleText($respuesta,$senderId,$accessToken,$senderData);
            //   handleText($despedida,$senderId,$accessToken,$senderData);
            //   handleText($iconoCommunity,$senderId,$accessToken,$senderData);
            // }
          }else{
            if($senderData["nombre"] == ""){
              connectToDb("update","predictivo","nombre",$messageText,"senderid",$senderId);
              handleText($pedirCorreo,$senderId,$accessToken,$senderData);
            }else if($senderData["email"] == ""){
              $email = emailValidator($messageText);
              if($email){
                connectToDb("update","predictivo","email",$email,"senderid",$senderId);
                if($senderData["sede"] != ""){
                  handleQuickReplies($pedirCarrera,carrerasPorSede($senderData["sede"]),$senderId,$accessToken,$senderData);
                }else{
                  handleText($pedirSede,$senderId,$accessToken,$senderData);
                  handleGenericTemplate($sedesData,$senderId,$accessToken,$senderData);
                }
              }else{
                handleText("Ingresa porfavor un correo válido para podernos contactar.",$senderId,$accessToken,$senderData);
                sumarIntento($senderData,"");
              }
            }else if($senderData["sede"] == ""){
              handleText($pedirSede,$senderId,$accessToken,$senderData);
              handleGenericTemplate($sedesData,$senderId,$accessToken,$senderData);
              sumarIntento($senderData,"");
            }else if($senderData["carrera"] == ""){
              if(array_key_exists($messageText, $carrerasDisponibles)){
                connectToDb("update","predictivo","carrera",$messageText,"senderid",$senderId);
                handleTemplate($pedirContacto,$formasContacto,"button",$senderId,$accessToken,$senderData);
              }else if($messageText == "Otros Santa Cruz" || $messageText == "Otros La Paz" || $messageText == "Otros El Alto"){
                handleQuickReplies($pedirCarrera,otrasCarrerasPorSede($messageText),$senderId,$accessToken,$senderData);
              }else{
                handleQuickReplies($pedirCarrera,carrerasPorSede($senderData["sede"]),$senderId,$accessToken,$senderData);
                sumarIntento($senderData,"");
              }
            }else if($senderData["contacto"] == ""){
              handleTemplate($pedirContacto,$formasContacto,"button",$senderId,$accessToken,$senderData);
              sumarIntento($senderData,"");
            }else if($senderData["contacto"] == "Llamada" && $senderData["llamada"] == ""){
              connectToDb("update","predictivo","llamada",$messageText,"senderid",$senderId);
              finRecoleccionDatosIniciales($senderData,$carrerasDisponibles,$preguntaDudasParteUno,$preguntaDudasParteDos,$senderId,$accessToken,$messageText,null);
            }else if($senderData["contacto"] == "Whatsapp" && $senderData["whatsapp"] == ""){
              connectToDb("update","predictivo","whatsapp",$messageText,"senderid",$senderId);
              finRecoleccionDatosIniciales($senderData,$carrerasDisponibles,$preguntaDudasParteUno,$preguntaDudasParteDos,$senderId,$accessToken,null,$messageText);
            }
          }
        }else{
          connectToDb("update","predictivo","saludo",$messageText,"senderid",$senderId);
          handleQuickReplies($pedirNecesidad,$necesidadesUnifranz,$senderId,$accessToken,$senderData);
        }
      }else if($senderData["bot_activo"] == "no"){
        
      }else{
        welcomeMessage($saludoParteUno,$saludoParteDos,$saludoParteTres,$senderId,$accessToken,$senderData);
      }
    }else if($event["postback"]){
      $title = $event["postback"]["title"];
      switch($event["postback"]["payload"]){
        case "GET_STARTED_UNIFRANZ":
        case "GET_STARTED_LA_PAZ":
        case "GET_STARTED_COCHABAMBA":
        case "GET_STARTED_SANTA_CRUZ":
        case "GET_STARTED_EL_ALTO":
        case "GET_STARTED":
        case "GET_STARTED_PRUEBAS":
          welcomeMessage($saludoParteUno,$saludoParteDos,$saludoParteTres,$senderId,$accessToken,$senderData);
          break;
        case "SEDE_PAYLOAD":
          if($senderData["bot_activo"] == "no") break;
          connectToDb("update","predictivo","sede",$title,"senderid",$senderId);
          handleQuickReplies($pedirCarrera,carrerasPorSede($title),$senderId,$accessToken,$senderData);
          break;
        case "CONTACTO_PAYLOAD":
          if($senderData["bot_activo"] == "no") break;
          connectToDb("update","predictivo","contacto",$title,"senderid",$senderId);
          if($title == "Llamada" && $senderData["llamada"] == ""){
            handleText($pedirTelefono,$senderId,$accessToken,$senderData);
          }
          if($title == "Whatsapp" && $senderData["whatsapp"] == ""){
            handleText($pedirWhatsapp,$senderId,$accessToken,$senderData);
          }
          if(($title == "Whatsapp" && $senderData["whatsapp"] != "") ||
            ($title == "Llamada" && $senderData["llamada"] != "") ||
            $title == "Email"){
              finRecoleccionDatosIniciales($senderData,$carrerasDisponibles,$preguntaDudasParteUno,$preguntaDudasParteDos,$senderId,$accessToken,null,null);
          }
          break;
        case "INTERES_PAYLOAD":
          if($senderData["bot_activo"] == "no") break;
          $paraLaDB = "";
          switch($title){
            case "Estoy curioseando":
              $paraLaDB = "Estoy curioseando";
              break;
            case "Considero opciones":
              $paraLaDB = "Estoy considerando opciones";
              break;
            case "Quiero estudiar aquí":
              $paraLaDB = "Estoy determinado a estudiar en UNIFRANZ";
              break;
            default:
              return true;
          }
          connectToDb("update","predictivo","interes",$paraLaDB,"senderid",$senderId);
          
          handleText($pedirNombre,$senderId,$accessToken,$senderData); 
          break;
        case "FIN_BOT":
          if($senderData["bot_activo"] == "no") break;
          connectToDb("update","predictivo","bot_activo","no","senderid",$senderId);
          handleText($finBot,$senderId,$accessToken,$senderData);
          handleText($iconoCommunity,$senderId,$accessToken,$senderData);
          break;
        default:
          if($senderData["bot_activo"] == "no") break;
          handleText("Sin payload",$senderId,$accessToken,$senderData);
      }
    }
  }
?>