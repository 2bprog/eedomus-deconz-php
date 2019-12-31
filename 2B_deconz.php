<?
// -----------------------------------------------------------------------------
// 2B_deconz : interface de convertion avec un serveur deCONZ
// -----------------------------------------------------------------------------
// ?vars=[VAR1]&action=[GET,PUT]&[json=...]&[rgb=r,g,b]&[on=0/1]&$[bri
//
// vars : 
//  VAR1     :  [ip:port,key,type,action,id]
//   - ip:port  : ip + port du serveur deCONZ
//   - key      : clef API du serveur deCONZ
//   - type     : type d'element (lights, groups, sensors, ... )
//   - id       : identifiant de l'element en fonction du type
//
//  exemple  :
//      lights  : 10.66.254.101:8090,FB5A4E6BBF,lights,9 
//      groups  : 10.66.254.101:8090,FB5A4E6BBF,groups,32
//      sensors : 10.66.254.101:8090,FB5A4E6BBF,groups,7
//  
// action :
//
//  PUT
//   - json     : json a envoyer (paremtre possible !XY!, !ON!, !BRI! )
//   - rgb      : valeur r,g,b (0..100,0..100,0..100) sera convertie en xy et utilisÃ©e pour remplacer le marqueur !XY!
//   - on       : valeur 0 ou 1 sera convertie en boolean et utilisÃ©e pour remplacer le marqueur !ON!
//   - bri      : valeur 0 Ã  100 sera convertie de 0 Ã  254 et utilisÃ©e pour remplacer le marqueur !BRI!
//   - newr     :
//   - newg     :
//   - newb     :
//
//  GET
//   - pas de parametre
//
//  NOP         : ne rien faire utiliser pour la gestion des parametre (Ex : transitiontime)
//   - pas de parametre
//
// -----------------------------------------------------------------------------

// rÃ©cupÃ©ration des parametres + die si action  = NOP
$action = getArg("action",false, '');
if ($action=='NOP') die();

$vars = getArg("vars",false, '');
$json = getArg("json",false, '');
$rgb = getArg("rgb",false, '');
$on = getArg("on",false, '');
$bri = getArg("bri",false, '');
$newr = getArg("newr",false, '');
$newg = getArg("newg",false, '');
$newb = getArg("newb",false, '');
$rgbapi = getArg("rgbapi",false, '');
$transapi = getArg("transapi",false, '');
$trans = "";
$debug = 0;

// DEBUG
if ($vars == "")
{
    $debug = 1;
    $vars = "10.66.254.101:8090,FB5A4E6BBF,lights,9";     
    $action = "GET";
}

// Extraction des info
$arVars = explode(",",$vars); 
$dzip =  $arVars[0]; 
$dzkey =  $arVars[1];
$dztype = $arVars[2];
$dzid = $arVars[3];

// gesion de l'action associÃ© au type d'element
$dzaction =  '';
if ($dztype=='lights')
 $dzaction = 'state';
elseif ($dztype=='groups')
 $dzaction = 'action';


// contruction de l'url deconz
$url = "http://".$dzip."/api/".$dzkey."/".$dztype."/".$dzid;
if ($action == "PUT")
 $url = $url."/".$dzaction;
 
if ($rgbapi!="")    
{
    $temp=getValue($rgbapi);
    $rgb=$temp["value"];
}
if ($transapi!="")  
{
    $temp=getValue($transapi);
    $trans=$temp["value"];
}

// conversion de la couleur
if ($rgb != "")
{
    $rgb = explode(",",$rgb); 
    
    // remplacement des valeurs si besoin
    if ($newr != "") $rgb[0] = $newr;
    if ($newg != "") $rgb[1] = $newg;
    if ($newb != "") $rgb[2] = $newb;

    $xy = sdk_tools_RGB_TO_XY($rgb[0],$rgb[1],$rgb[2]);
    $json = str_replace("!XY!", $xy['X'].",".$xy['Y'], $json);
}

// Conversion on / off : true, false;
if ($on != "")
{
	if (abs($on) != 0)
		$on = 'true';
	else
	    $on = 'false';
	$json = str_replace("!ON!", $on, $json);
}
// conversion de la luminositÃ©
if ($bri != "")
{
	if ($bri > 1)
		$bri = round($bri * 2.54);
	$json = str_replace("!BRI!", $bri, $json);
}

// Gestion transition time
if ($trans!="")
{
    $json = str_replace("!TR!", $trans, $json);
}
	
	

// correction du json (pour pouvoir les tester a partir de l'eedomus + appel url
$json = str_replace("\\\"","\"", $json);
$json = str_replace("\\\"","\"", $json);
$jsresult =  utf8_encode(httpQuery($url,$action, $json));

// remplacement de / par _ dans le json pour la convertion XML
// + convertion tableau et xml
$jsresult = str_replace("/", "_",$jsresult);
$arresult = sdk_json_decode($jsresult);
$xmlresult = jsonToXML($jsresult);


// traitement rgbapi
if ($rgbapi!="")    
{
    setValue($rgbapi, $rgb[0].','.$rgb[1].','.$rgb[2] , false, true);

}

// traitement du on et du bri
if (isset($arresult[$dzaction]['on']))
{
  $e_on = abs($arresult[$dzaction]['on']);
  if (isset($arresult[$dzaction]['bri']))
  {
      $e_bri =  floor($arresult[$dzaction]['bri'] / 25.5 + 0.5) * 10;
      $e_onbri = $e_bri * $e_on;
      if ($e_on != 0 && $e_onbri == 0) $e_onbri = 1;
  }
  
}

// traitement de la couleur
if (isset($arresult[$dzaction]['xy']))
{
    $e_colorRGB = sdk_tools_XY_TO_RGB($arresult[$dzaction]['xy'][0] , $arresult[$dzaction]['xy'][1]);
}

// traitment du ct
if (isset($arresult[$dzaction]['ct']))
{
	$e_ct = $arresult[$dzaction]['ct'];
}

// crÃ©ation d'un fichier XML 
sdk_header("text/xml");
echo "<deconz>\r\n";
if ($debug == 1)
{
   echo "<jsraw>".$jsresult."</jsraw>\r\n" ;
   echo "<jsarray>".var_dump($arresult)."</jsarray>\r\n";
}
echo "<xmlraw>\r\n".str_replace('<?xml version="1.0" encoding="ISO-8859-1"?>', '', $xmlresult)."</xmlraw>\r\n" ;
echo "<eedomus>\r\n";
echo "<url>".$url."</url>\r\n";
echo "<action>".$action."</action>\r\n";
echo "<json>".$json."</json>\r\n";


if (isset($e_on)) echo "<e_on>".$e_on."</e_on>\r\n";
if (isset($e_bri)) echo "<e_bri>".$e_bri."</e_bri>\r\n";
if (isset($e_onbri)) echo "<e_onbri>".$e_onbri."</e_onbri>\r\n";
if (isset($e_colorRGB)) 
{
    echo "<e_colorRGB>".$e_colorRGB['R'].",".$e_colorRGB['G'].",".$e_colorRGB['B']."</e_colorRGB>\r\n";
    echo "<e_colorR>".$e_colorRGB['R']."</e_colorR>\r\n";
    echo "<e_colorG>".$e_colorRGB['G']."</e_colorG>\r\n";
    echo "<e_colorB>".$e_colorRGB['B']."</e_colorB>\r\n";
    echo "<e_colorRGB10>".$e_colorRGB['R10'].",".$e_colorRGB['G10'].",".$e_colorRGB['B10']."</e_colorRGB10>\r\n";
    echo "<e_colorR10>".$e_colorRGB['R10']."</e_colorR10>\r\n";
    echo "<e_colorG10>".$e_colorRGB['G10']."</e_colorG10>\r\n";
    echo "<e_colorB10>".$e_colorRGB['B10']."</e_colorB10>\r\n";
}
if (isset($e_ct)) echo "<e_ct>".$e_ct."</e_ct>\r\n";

/*
echo $arresult['state']['alert']."\r\n";
echo $arresult['state']['reachable']."\r\n";
echo $arresult['state']['on']."\r\n";
echo $arresult['state']['bri']."\r\n";
echo $arresult['state']['ct']."\r\n";
echo $arresult['state']['xy'][0]."\r\n";
echo $arresult['state']['xy'][1]."\r\n";
echo $arresult['state']['colormode']."\r\n";
*/

echo "</eedomus>\r\n" ;
echo "</deconz>";

die();

// function sdk_tools_RGB_TO_XY($R, $G, $B)
// convertion couleur RGB vers xx
// R:(0 Ã Â  100) / G:(0 Ã Â  100) / B:(0 Ã Â  100) 
// retourne un tableau ['X'] ['Y']  pour deconz ( de 0 Ã Â  1)
function sdk_tools_RGB_TO_XY($R, $G, $B)
{
    
    $red = $R / 100;
    $green = $G / 100;
    $blue = $B /  100;
    
    if ($red > 0.04045) 
      $r = pow((($red + 0.055) / (1.0 + 0.055)), 2.4 );
    else              
      $r = ($red / 12.92);
    
    if ($green > 0.04045)   
      $g = pow((($green + 0.055) / (1.0 + 0.055)), 2.4);
     else                   
       $g = ($green / 12.92);
       
    if ($blue > 0.04045)   
      $b = pow((($blue + 0.055) / (1.0 + 0.055)), 2.4);
    else 
      $b = ($blue / 12.92);
      
    $X = $r * 0.664511 + $g * 0.154324 + $b * 0.162028;
    $Y = $r * 0.283881 + $g * 0.668433 + $b * 0.047685;
    $Z = $r * 0.000088 + $g * 0.072310 + $b * 0.986039;
    $cx = 0;
    $cy = 0;
    if (($X + $Y + $Z) != 0)
    {
        $cx = $X / ($X + $Y + $Z);
        $cy = $Y / ($X + $Y + $Z);
    }
    
    $XY['X'] = round($cx * 10000000) / 10000000;
    $XY['Y'] =  round($cy * 10000000) / 10000000;

   return $XY;
}

// function sdk_tools_RGB_TO_XY($X, $Y)
// convertion couleur XY vers RGB
// X:(0 Ã Â  1) / G:(0 Ã Â  100) / B:(0 Ã Â  100) 
// retourne un tableau ['R'] ['G'] ['B'] ['R10'] ['G10'] ['B10'] pour eedomus
//  (de 0 a 100) et par pas de 10 pour R10, G10 et B10
// ##BUG## :  probleme de conversion sur le rouge .! de deconz
function sdk_tools_XY_TO_RGB($X, $Y)
{
    $brightness = 1;
    $Z = 1.0 - $X - $Y;

    $R = 0;
    $G = 0;
    $B = 0;
    
    if (($X != 0) && ($Y != 0))
    {
        $X = ($brightness / $Y) * $X;
        $Z = ($brightness / $Y) * $Z;
        $Y = $brightness;
        
        $R =  $X * 1.656492 - $Y * 0.354851 - $Z * 0.255038;
        $G = -$X * 0.707196 + $Y * 1.655397 + $Z * 0.036152;
        $B =  $X * 0.051713 - $Y * 0.121364 + $Z * 1.011530;
    
    
        $puis = 1.0 / 2.4;
        
        if ($R <= 0.0031308)    $R = 12.92 * $R;
        else                    $R = ((1.0 + 0.055) * pow($R, $puis)) - 0.055;
    
        if ($G <= 0.0031308)    $G = 12.92 * $G;
          else                  $G = (1.0 + 0.055) * pow($G, $puis) - 0.055;
          
        if ($B <= 0.0031308)    $B = 12.92 * $B;
          else                  $B = (1.0 + 0.055) * pow($B, $puis) - 0.055;
             
        
        if (($R > $B) && ($R > $G))
        {
            if ($R > 1.0)
            {
                $G = $G / $R;
                $B = $B / $R;
                $R = 1.0;
            }
        }
        elseif (($G > $B) && ($G > $R))
        {
            if ($G > 1.0)
            {
                $R = $R / $G;
                $B = $B / $G;
                $G = 1.0;
            }
        }
        elseif (($B > $R) && ($B > $G))
        {
            if ($B > 1.0)
            {
                $R = $R / $B;
                $G = $G / $B;
                $B = 1.0;
            }
        }
    }
    
    $RGB['R'] = abs(round($R * 100));
    $RGB['G'] = abs(round($G * 100));
    $RGB['B'] = abs(round($B * 100));
    
    $RGB['R10'] = abs(round($R * 10)) * 10;
    $RGB['G10'] = abs(round($G * 10)) * 10;
    $RGB['B10'] = abs(round($B * 10)) * 10;
    
    return $RGB;
}



?>
