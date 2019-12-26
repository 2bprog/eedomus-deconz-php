<?


// ip +port
$ip = getArg('ip', false);
$key = getArg('key', false);

// cmd=list?p1=[[all]|lights|sensors|groups]&p2=[json|xml|[html]]
// cmd=discover?p2=[json|xml|html]

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


if (!$dohtml) die;

$result = sdk_json_decode($result, false);


?>

<html lang="fr-fr">
<head>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4-4.1.1/jq-3.3.1/dt-1.10.18/datatables.min.css"/>
 <script type="text/javascript" src="https://cdn.datatables.net/v/bs4-4.1.1/jq-3.3.1/dt-1.10.18/datatables.min.js"></script>
<script>
$(document).ready( function () {
    $('#table').DataTable({
		"pageLength": 300,
		"lengthChange": false,
		"searching": false,
		"bPaginate": false,
		"bInfo": false,
		"ordering": false
	});
} );
</script>
</head>
<body>
<div style="margin: 0 auto; width:500px;">
<table id="table" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Sc&egrave;ne</th>
			<th>ID</th>
        </tr>
    </thead>
    <tbody>
<? // php
    foreach ($result as $key => $value)
    {
        echo '<tr>';
        echo '<td>'.$key.'</td>';
        echo '<td>'.$value['name'].'</td>';
         echo '</tr>';;
    }

?>  
    </tbody>
</div>
</body>