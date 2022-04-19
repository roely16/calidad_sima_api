<?php

	//include $_SERVER['DOCUMENT_ROOT'] . '/ave_api/Dbconfig.php';

	class Db{

		protected $connection;

		protected $user;
		protected $password;
		protected $dbName;

		function connect(){

			$ini = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/GestionServicios/calidad_sima_api/app.ini');

			if ($ini['env'] == 'production') {

				$this->user = 'catastrousr';
	            $this->password = 'k4t4str03d';
	            $this->dbName = '   (DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 172.23.50.95)(PORT = 1521)))(CONNECT_DATA = (SERVICE_NAME = CATGIS)))';

			}else{

				$this->user = 'catastro';
	            $this->password = 'pruebas';
	            $this->dbName = '   (DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 172.23.25.27)(PORT = 1521)))(CONNECT_DATA = (SERVICE_NAME = PRUEBAS)))';

			}

			//Creacion de nuevo objetopara obtener los parametros de la bd
			//$dbPara = new Dbconfig();

			$this->connection = oci_connect($this->user, $this->password, $this->dbName, 'UTF8');

			if (!$this->connection) {

		    	$e = oci_error();
		    	trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);

			}else{

				return $this->connection;

			}

		}

		function disconnect($conn){

			oci_close($conn);

		}

	}

?>
