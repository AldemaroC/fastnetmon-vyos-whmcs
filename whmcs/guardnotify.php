<?php
require_once("init.php");
use WHMCS\Database\Capsule;

$pdo = Capsule::connection()->getPdo();
$pdo->beginTransaction();

// IP do servidor que vai chamar o script por curl
$ipspermitido = ["127.0.0.1"];

$ip = $_GET["ip"];

// Pega o IP de quem está fazendo a requisição. Funciona com ou sem cloudflare e proxy
if(!empty($_SERVER["HTTP_CLIENT_IP"])){
    $ipremoto = $_SERVER["HTTP_CLIENT_IP"];
}elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
	$ipremoto = $_SERVER["HTTP_X_FORWARDED_FOR"];
}else{
	$ipremoto = $_SERVER["REMOTE_ADDR"];
}

// Verifica se temos um IP em GET
if(!empty($ip)){
	
	// Segurança mínima: verificar se o IP chamando o script é autorizado
	if(in_array($ipremoto, $ipspermitido)){

		// Pega o IP e procura pelo ID do produto na database
		$sql = $pdo->prepare("SELECT * FROM tblhosting WHERE dedicatedip = :dedicatedip OR assignedips LIKE :assignedips AND domainstatus = 'Active'");
		$sql->execute(array(
			":dedicatedip" => $ip,
			":assignedips" => "%".$ip."%"
		));
		$sql->execute();
		
		$id = $sql->fetch();

		// Se o produto existir, envia o email
		if(!empty($id)){
			$results = localAPI("SendEmail", array(
				"messagename" => "DDOs Guard",
				"id" => $id["id"],
				"customvars" => base64_encode(serialize(array(
					"guardnotify_ip" => $ip
				)))
			));
			echo "Alerta enviado para cliente com sucesso";
		}
	}else{
		echo "IP não permitido";
	}
}
?>
