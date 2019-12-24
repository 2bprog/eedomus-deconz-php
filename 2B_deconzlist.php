<?


// ip +port
$ip = getArg('ip');

// keyapi
$key = getArg('key');

// cmd=list?p1=[lights|sensors|groups]&p2=[js|[html]]

$cmd = getArg('cmd');

// p1 .. pn => parametres de cmd	
$p1 = getArg('p1');
$p2 = getArg('p2', false);

$dohtml = false;

$url='http://'.$ip.'/api/'.$key;

// cette commande doit rester a la fin
if ($cmd=="list")
{
    if ($p1!='all')  $url = $url.'/'.$p1;
    $result = httpQuery($url, 'GET');
    if ($p2=='js') echo $result;
    if ($p2=='xml') echo jsonToXML($result);
    $dohtml = ($p2=='html' || $p2=='');
}

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