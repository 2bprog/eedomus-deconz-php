<?
// -----------------------------------------------------------------------------
// #2B_deconzlist# : Gestion des interfaces avec deconz
// #version#
// 2B_deconzlist.php v0.1.2
// -----------------------------------------------------------------------------
// demande clef api a deconz
// &cmd=api 
// save IP ou Key
// &cmd=save&[item=ip|key] 
// &cmd=load&[item=ip|key] // return text 

// &cmd=widget&p1=[[all]|lights|sensors|groups]
// &cmd=list&p1=[[all]|lights|sensors|groups]&p2=[json|xml|[html]|dump]
// &cmd=discover&p2=[json|xml|html]
// http://10.66.254.240/script/?exec=2B_deconzlist.php&ip=10.66.254.101:8090&key=FB5A4E6BBF&cmd=list&p1=lights&p2=html
// http://10.66.254.240/script/?exec=2B_deconzlist.php&ip=10.66.254.101:8090&cmd=api&p2=json

// ip +port
$ip = getArg('ip', false);
$key = getArg('key', false);
$item= getArg('item', false, '');

$cmd = getArg('cmd', false);
if ($cmd == '') $cmd = 'discover';

// p1 .. pn => parametres de cmd	
$p1 = getArg('p1', false);
$p2 = getArg('p2', false);

if ($p1 == '') $p1 = 'all';
if ($p2 == '') $p2 = 'html';

$doapi = false;
$dohtml = false;
$method = "GET";
$params = "";

switch ($cmd) {
	
    case 'save':        
		if ($item=='ip') saveVariable ($item, $ip);
		if ($item=='key') saveVariable ($item, $key);
		
		?>
		<html lang="fr-fr"><head></head>
		<body>	
		<? echo loadVariable ($item); ?>
		<script type="text/javascript">
			setTimeout(function(){window.close();},500);
		</script>
		</body>
		</html>		
		<?		
		
		die();
        break;
		
    case 'load':
		$elid = '';
		$loadret = '';
		if ($item=='ip')  $elid = '"periph_param[`DZIP`]"';
		if ($item=='key')  $elid = '"periph_param[`DZAPIKEY`]"';
        if ($item=='ip' || $item=='key') $loadret = loadVariable ($item);		
		?>
		<html lang="fr-fr"><head></head>
		<body>			
		<script type="text/javascript">

			var opener = window.opener;
			if(opener) {
				var oDom = opener.document;
				var elem = oDom.getElementById(<? echo $elid; ?>);
				if (elem) {
					elem.value = "<? echo $loadret; ?>";					
				}				
			}					
			window.close();				
			
		</script>
		</body>
		</html>		
		<?		
		
		die();
        break;
		
    case 'api':
        $url='http://'.$ip.'/api';
		$method = 'POST';
		$params ='{"devicetype":"eedomus"}';
		$doapi = true;
        break;
		
    case 'list':
        $url='http://'.$ip.'/api/'.$key;
        break;
		
	case 'discover':	
	default:
		  $url = 'https://phoscon.de/discover';
	
}

 
if ($p1!='all')  $url = $url.'/'.$p1;
$result = httpQuery($url, $method, $params);


if ($p2=='json') 
{
    sdk_header("application/json");
    echo $result;
}
if ($p2=='xml')
{
    sdk_header("text/xml");
    echo jsonToXML($result);
}

$result = sdk_json_decode($result, false);
if ($p2=='dump')
{
	header("Content-Type:text/plain;");
    var_dump($result);
}

$dohtml = ($p2=='html');
if (!$dohtml) die();

$dotable = ($cmd != 'api');

echo '<html lang="fr-fr">';
echo '<head>';
if ($dotable)
{
    echo '<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">';
    echo '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">';
    echo '<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.3.1.js"></script>';
    echo '<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>';
    echo '<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>';
    echo '<script>';
    echo 'var dataSet = [';
    $nb=count($result);
    $i=0;
    foreach ($result as $key => $value){
        //commandes
    	if ($cmd == 'discover')
    	{
    		echo '["'.$value['internalipaddress'].'","'.$value['internalport'].'","'.$value['name'].'"]';
    	}
    	else if ($cmd == 'list' && $p1 == 'lights')
    	{
    		$typear =  array("On/Off plug-in unit" => "Prise electrique", "Dimmable light" => "Ampoule standard", "Color temperature light" => "Ampoule spectre blanc", "Color light" => "Ampoule RGBW");
    		$typeorg = $value['type'];
    		if (isset( $typear[$typeorg]))
    			$typedisplay = $typear[$typeorg];
    		else 
    			$typedisplay = "Inconnu";
    		
    		echo '["'.$key.'","'.$value['name'].'","'.$typedisplay.'","'.$value['modelid'].'"]';
    	}
    	else if ($cmd == 'list' && $p1 == 'sensors')
    	{
    	    $typedisplay = '';
    	    if (isset( $value['config']['reachable']))  sdk_appendcomma($typedisplay, 'Communication');
    	    if (isset( $value['config']['battery'])) 	sdk_appendcomma($typedisplay, 'Batterie');
    	    if (isset( $value['config']['temperature']) || 
			    isset( $value['state']['temperature'])) sdk_appendcomma($typedisplay, 'Temp�rature'); 
    	    if (isset( $value['state']['humidity'])) 	sdk_appendcomma($typedisplay, 'Humidit�');
    	    if (isset( $value['state']['pressure'])) 	sdk_appendcomma($typedisplay, 'Pression'); 
    	    if (isset( $value['state']['lux'])) 		sdk_appendcomma($typedisplay, 'Luminosit�'); 
    	    if (isset( $value['state']['presence'])) 	sdk_appendcomma($typedisplay, 'Mouvement'); 
    	    if (isset( $value['state']['open'])) 		sdk_appendcomma($typedisplay, 'Ouverture'); 
    	    if (isset( $value['state']['buttonevent'])) sdk_appendcomma($typedisplay, 'T�l�commande'); 
			if (isset( $value['config']['heatsetpoint']) && 
				isset( $value['config']['mode']) && 
				isset( $value['state']['valve'])) sdk_appendcomma($typedisplay, 'T�te Thermostatique'); 
			
            if ($typedisplay == '') $typedisplay = '??? - '.$value['type'];

    	    echo '["'.$key.'","'.$value['name'].'","'.$typedisplay.'","'.$value['modelid'].'"]';
    	}
    	
    	$i++;
    	if ($i!=$n)
            echo ',';
    }
    echo '];';
    echo ' $(document).ready(function() {';
    echo '$("#mytable").DataTable( {';
    echo '"language": {"url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/French.json"},';
    echo 'data: dataSet,';
    echo 'columns: [';


    if ($cmd == 'discover')
       echo ' { title: "IP" },  { title: "Port" }, { title: "Nom" }';
    else if ($cmd == 'list' && $p1 == 'lights')
       echo ' { title: "ID" },  { title: "Nom" }, { title: "Type" }, { title: "Model" }';
    else if ($cmd == 'list' && $p1 == 'sensors')
       echo ' { title: "ID" },  { title: "Nom" }, { title: "Type" }, { title: "Model" }';
	   
    echo ']';
    echo '} );';
    echo '} );';
    echo '</script>';
}
echo '</head>';

echo '<body>';
echo '<div style="margin:15px">';
if ($dotable)
{
    echo '<table id="mytable" class="table table-striped table-bordered" width="100%" ></table>';
}

if ($doapi)
{
    
    if (isset($result[0]['error']))
    {
        echo "L'erreur suivante est survenu : <b>".$result[0]['error']['description'].'</b><br>';
        echo '<br>';
        echo 'Pour activer la cr�ation de la clef API :<br>';
        echo ' - Connectez-vous a <b>Phoscon-GW</b><br>';
        echo ' - Allez dans <b>Settings/Gateway/Advanced</b><br>';
        echo ' - Cliquez sur <b>Athenticate app</b><br>';
    }
    else if (isset($result[0]['success']))
    {
        echo 'Voici votre clef API : <b>'.$result[0]['success']['username'].'</b>';
    }
    else 
    {
        echo 'Le resultat retourn� est inattendu !';
    }
    
}

echo '</div>';
echo '</body>';
echo '</html>';

function sdk_appendcomma(&$ret, $toappend)
{
    if ($ret != '')
        $ret .= ', ';
        
    $ret .= $toappend;
}

?>