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


?>

<html lang="fr-fr">
<head>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">

<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.3.1.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>


<script>
    var dataSet = [
<?
// [{"id":"00212EFFFF04E314","internalipaddress":"10.66.254.101","macaddress":"00212EFFFF04E314","internalport":8090,"name":"Phoscon-GW","publicipaddress":"37.164.184.160"}]
$nb=count($result);
for ($i = 1; $i <= $nb; $i++) 
{ 
    echo '["'.$result[$i-1]['internalipaddress'].'","'.$result[$i-1]['internalport'].'","'.$result[$i-1]['name'].'"]';
    if ($i!=$n)
        echo ',';
}      
?>

];
 
$(document).ready(function() {
    $('#mytable').DataTable( {
         "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/French.json"
        },
        data: dataSet,
        columns: [
<?
       echo ' { title: "IP" },  { title: "Port" }, { title: "Nom" }';
?>
        ]
    } );
} );
</script>
</head>
<body>
<p></p>
<table id="mytable" class="table table-striped table-bordered" width="100%"></table>


</body>
</html>

