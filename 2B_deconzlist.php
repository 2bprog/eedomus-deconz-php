<?

// ip +port
$ip = getArg('ip', false);
$key = getArg('key', false);

// &cmd=list&p1=[[all]|lights|sensors|groups]&p2=[json|xml|[html]]
// &cmd=discover&p2=[json|xml|html]

$cmd = getArg('cmd', false);
if ($cmd == '') $cmd = 'discover';

// p1 .. pn => parametres de cmd	
$p1 = getArg('p1', false);
$p2 = getArg('p2', false);

if ($p1 == '') $p1 = 'all';
if ($p2 == '') $p2 = 'html';


$dohtml = false;

if ($cmd=='list')
    $url='http://'.$ip.'/api/'.$key;
else // discover
   $url = 'https://phoscon.de/discover';
   
if ($p1!='all')  $url = $url.'/'.$p1;
$result = httpQuery($url, 'GET');
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
$dohtml = ($p2=='html');


if (!$dohtml) die();

$result = sdk_json_decode($result, false);

// ip du serveur
$ipsrv='';
if ($cmd == 'discover')
{
    if (isset($result[0]['internalipaddress']))
        $ipsrv = $result[0]['internalipaddress'];
    if (isset($result[0]['internalport']))
        $ipsrv = $ipsrv.':'.$result[0]['internalport'];
}

?>

<html lang="fr-fr">
<head>
<meta charset="utf-8">
<script>
(function() {
    <?
    if ($cmd == 'discover')
        echo 'window.opener.document.getElementById("periph_param[`DZIP`]").value="'.$ipsrv.'";';
    ?>
   
   window.close();
})();
</script>
</head>
<body>
</body>

<?
die();
?>
