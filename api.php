<?php

    error_reporting(E_ERROR | E_PARSE);

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    header("Allow: GET, POST, OPTIONS, PUT, DELETE");

    class Api extends Rest{

        private $dbConn;

        public function __construct(){

			parent::__construct();

            date_default_timezone_set('Europe/Berlin');

			$db = new Db();
			$this->dbConn = $db->connect();

			$query_sysdate = "ALTER SESSION SET nls_date_format = 'dd/mm/yyyy'";
			$stid = oci_parse($this->dbConn, $query_sysdate);
			oci_execute($stid);

        }

        /** Módulo para control de calidad de Documentos  */

        /** Obtener documentos susceptibles a control de calidad  */
        public function obtener_lotes(){

            try {

                $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                            DOC.PRIMER_NOMBRE, DOC.FOLIOS, DOC.DIR_NOTIFICA, DOC.TELEFONO,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, DOC.USUARIO, DOC.PLAZO_HORAS, NVL(DOC.CALIDAD, 0) AS CALIDAD
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND DOC.FECHA_ISO IS NOT NULL
                            AND DOC.CALIDAD IS NULL
                            ORDER BY DOC.DOCUMENTO DESC";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

					$str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                $lotes = array();

                while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

                    /** Buscar en table de control de aciertos y errores */
                    $documento = $data["DOCUMENTO_"];
                    $anio = $data["ANIO_"];

                    $errores = array();

                    $query = "SELECT * FROM CDO_Q_DOCUMENTO WHERE DOCUMENTO = $documento AND ANIO = $anio AND ERROR = 1";
                    $stid_ = oci_parse($this->dbConn, $query);

                    if (false === oci_execute($stid_)) {

                        $err = oci_error($stid);

                        $str_error = "No se pudo procesar la solicitud";

                        $this->throwError($err["code"], $str_error);

                    }

                    while ($data_ = oci_fetch_array($stid_, OCI_ASSOC)) {

                        $errores [] = $data_;

                    }

                    $data["ERRORES"] = $errores;
                    $lotes [] = $data;

                }

                $this->returnResponse(SUCCESS_RESPONSE, $lotes);

            } catch (\Throwable $th) {

                $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            }

        }
        
        public function documentosControl2(){

            try {

                $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                            DOC.PRIMER_NOMBRE,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, DOC.USUARIO, DOC.PLAZO_HORAS, NVL(DOC.CALIDAD, 0) AS CALIDAD
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND DOC.FECHA_ISO IS NOT NULL
                            AND DOC.CALIDAD = 1
                            ORDER BY DOC.DOCUMENTO DESC";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                $documentos = array();

                while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                    /** Buscar en table de control de aciertos y errores */
                    $documento = $data["DOCUMENTO_"];
                    $anio = $data["ANIO_"];

                    $errores = array();

                    $query = "SELECT * FROM CDO_Q_DOCUMENTO WHERE DOCUMENTO = $documento AND ANIO = $anio AND ERROR = 1";
                    $stid_ = oci_parse($this->dbConn, $query);

                    if (false === oci_execute($stid_)) {

                        $err = oci_error($stid);

                        $str_error = "No se pudo procesar la solicitud";

                        $this->throwError($err["code"], $str_error);

                    }

                    while ($data_ = oci_fetch_array($stid_, OCI_ASSOC)) {

                        $errores [] = $data_;

                    }

                    $data["ERRORES"] = $errores;

                    $documentos [] = $data;

                }

                $this->returnResponse(SUCCESS_RESPONSE, $documentos);

            } catch (\Throwable $th) {

                $this->throwError(JWT_PROCESSING_ERROR, $th->getMessage());

            }

        }

        public function documentosControlFinalizados(){

            try {

                $fecha_inicio = $this->param['fecha_inicio'];
                $fecha_fin = $this->param['fecha_fin'];

                $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                            DOC.PRIMER_NOMBRE,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, DOC.USUARIO, DOC.PLAZO_HORAS, NVL(DOC.CALIDAD, 0) AS CALIDAD
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND DOC.FECHA_ISO IS NOT NULL
                            AND DOC.CALIDAD = 2
                            ORDER BY DOC.DOCUMENTO DESC";

                if ($fecha_inicio && $fecha_fin) {
                    
                    $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                            DOC.PRIMER_NOMBRE,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, DOC.USUARIO, DOC.PLAZO_HORAS, NVL(DOC.CALIDAD, 0) AS CALIDAD
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND DOC.FECHA_ISO IS NOT NULL
                            AND DOC.CALIDAD = 2
                            AND DOC.FECHA BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            ORDER BY DOC.DOCUMENTO DESC";

                }
                

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                $documentos = array();

                while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                    /** Buscar en table de control de aciertos y errores */
                    $documento = $data["DOCUMENTO_"];
                    $anio = $data["ANIO_"];

                    $errores = array();

                    $query = "SELECT * FROM CDO_Q_DOCUMENTO WHERE DOCUMENTO = $documento AND ANIO = $anio AND ERROR = 1";
                    $stid_ = oci_parse($this->dbConn, $query);

                    if (false === oci_execute($stid_)) {

                        $err = oci_error($stid);

                        $str_error = "No se pudo procesar la solicitud";

                        $this->throwError($err["code"], $str_error);

                    }

                    while ($data_ = oci_fetch_array($stid_, OCI_ASSOC)) {

                        $errores [] = $data_;

                    }

                    $data["ERRORES"] = $errores;

                    $documentos [] = $data;

                }

                $this->returnResponse(SUCCESS_RESPONSE, $documentos);

            } catch (\Throwable $th) {

                $this->throwError(JWT_PROCESSING_ERROR, $th->getMessage());

            }

        }

        public function documentoAcierto(){

            $document = $this->validateParameter('document', $this->param['document'], STRING);
            $year = $this->validateParameter('year', $this->param['year'], STRING);
            $calidad = $this->param['calidad'];

            /** Se registra como acierto en la base de datos de calidad */
            $calidad++;

            $query = "UPDATE CDO_DOCUMENTO SET CALIDAD = $calidad WHERE DOCUMENTO = $document AND ANIO = $year AND CODIGOCLASE = 3";
            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            /** Si Calidad = 2 finalizar el documento */
            /* if ($calidad == 2) {
                
                $query = "UPDATE CDO_DOCUMENTO SET STATUS = 2, FECHA_FINALIZACION = SYSDATE WHERE DOCUMENTO = $document AND ANIO = $year AND CODIGOCLASE = 3";
                
                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);
    
                    $str_error = "No se pudo procesar la solicitud";
    
                    $this->throwError($err["code"], $str_error);
    
                }
    

            } */

            /** Registrar en table de calidad */
            $query = "INSERT INTO CDO_Q_DOCUMENTO (DOCUMENTO, ANIO, FECHA, ACIERTO) VALUES ('$document', '$year', SYSDATE, 1)";

            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            // Registro en tabla de Indicadores

            /* Obtener el usuario */
            $query = "	SELECT USUARIO, FECHA
            FROM CDO_DOCUMENTO
            WHERE DOCUMENTO = '$document' AND ANIO = '$year' AND CODIGOCLASE = 3";

            $stid = oci_parse($this->dbConn, $query);
            oci_execute($stid);

            $result = oci_fetch_array($stid, OCI_ASSOC);
            $usuario = $result["USUARIO"];
            $fecha = $result["FECHA"];

            $user = 'rrhh';
            $password = 'rrhhadmin';
            $dbName = '   (DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 172.23.50.95)(PORT = 1521)))(CONNECT_DATA = (SERVICE_NAME = CATGIS)))';

            $connection_rrhh = oci_connect($user, $password, $dbName, 'UTF8');

            $query = "  INSERT INTO PDI_MAESTRO_REGISTROS (ID_REGISTRO,
                                    ID_PROCESO,
                                        ID_CATEGORIA,
                                        REFERENCIA,
                                        ID_ESTADO,
                                        USUARIO,
                                        FECHA,
                                        FECHA_CREACION)
                        VALUES (SQ_PDI_MAESTRO.NEXTVAL,
                                        14,
                                        1,
                                        '".$document."-".$year."',
                                        1,
                                        '$usuario',
                                        '$fecha',
                                        SYSDATE)";

            $stid = oci_parse($connection_rrhh, $query);
            oci_execute($stid);

            $this->returnResponse(SUCCESS_RESPONSE, $calidad);

        }

        public function documentoError(){

            $document = $this->validateParameter('document', $this->param['document'], STRING);
            $year = $this->validateParameter('year', $this->param['year'], STRING);
            $errores = $this->validateParameter('errores', $this->param['errores'], STRING);
			$id_errores = $this->param['id_errores'];

            /** El documento se registra como reproceso y se vuelve a reasignar en workflow */
			$query_update = "UPDATE CDO_DOCUMENTO SET STATUS = '1' WHERE DOCUMENTO = $document AND ANIO = $year AND CODIGOCLASE = 3";
			$stid = oci_parse($this->dbConn, $query_update);
			oci_execute($stid);

			$criterios_usuario_ingreso = array(1, 2, 3, 4, 5, 6, 7, 8 ,9);
			$criterios_usuario_trabajo = array(10, 11, 12, 13, 14, 15, 16, 17, 18, 19);

			$error_ingreso = false;
			$error_trabajo = false;

			$errores_ingreso = array();
			$errores_trabajo = array();

			foreach ($id_errores as $id_error) {

				if (in_array($id_error, $criterios_usuario_ingreso)) {

					/* Tomar el usuario que ingreso */

					// $query = "	SELECT USUARIO
					// 			FROM CDO_DOCUMENTO
					// 			WHERE DOCUMENTO = '$document' AND ANIO = '$year' AND CODIGOCLASE = 3";

					$error_ingreso = true;
					$errores_ingreso [] = $id_error;

				}else if(in_array($id_error, $criterios_usuario_trabajo)){

					$error_trabajo = true;
					$errores_trabajo [] = $id_error;

					/* Tomar el usuario que lo trabajo */

					// $query = "	SELECT USER_APLIC AS USUARIO
					// 			FROM CDO_BANDEJA
					// 			WHERE DOCUMENTO = '$document' AND ANIO = '$year' AND CODTRAMITE = 192
					// 			AND (NUMDOCTOS IS NOT NULL AND FECHA_FINALIZACION IS NOT NULL)
					// 			AND DEPENDENCIA IN (92,94)";

				}

			}

			/* Si existe error de ingreso buscar los errores */

			if ($error_ingreso) {

				$id_errores_ingreso_separados = implode(',', $errores_ingreso);

				$query = "	SELECT ID, CRITERIO
							FROM CDO_CRITERIOS_SIMA WHERE ID IN ($id_errores_ingreso_separados)";

				$stid = oci_parse($this->dbConn, $query);
				oci_execute($stid);

				$errores_ingreso_str = array();

				while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

					$errores_ingreso_str [] = $data["CRITERIO"];

				}

				/* Obtener el usuario */
				$query = "	SELECT USUARIO, FECHA
							FROM CDO_DOCUMENTO
							WHERE DOCUMENTO = '$document' AND ANIO = '$year' AND CODIGOCLASE = 3";

				$stid = oci_parse($this->dbConn, $query);
				oci_execute($stid);

				$result = oci_fetch_array($stid, OCI_ASSOC);
				$usuario = $result["USUARIO"];
                $fecha = $result["FECHA"];

				/** Registrar en table de calidad */

				$explode_errores_ingreso = implode(',', $errores_ingreso_str);

	            $query = "INSERT INTO CDO_Q_DOCUMENTO (DOCUMENTO, ANIO, FECHA, ERROR, ERRORES, USUARIO) VALUES ('$document', '$year', SYSDATE, 1, '$explode_errores_ingreso', '$usuario')";

	            $stid = oci_parse($this->dbConn, $query);

	            if (false === oci_execute($stid)) {

	                $err = oci_error($stid);

	                $str_error = "No se pudo procesar la solicitud";

	                $this->throwError($err["code"], $str_error);

	            }

			}

			/* Si existe error de trabajo buscar los errores */

			if ($error_trabajo) {

				$id_errores_trabajo_separados = implode(',', $errores_trabajo);

				$query = "	SELECT ID, CRITERIO
							FROM CDO_CRITERIOS_SIMA WHERE ID IN ($id_errores_trabajo_separados)";

				$stid = oci_parse($this->dbConn, $query);
				oci_execute($stid);

				$errores_trabajo_str = array();

				while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

					$errores_trabajo_str [] = $data["CRITERIO"];

				}

				/* Obtener el usuario */
				$query = "	SELECT USER_APLIC AS USUARIO, FECHA
							FROM CDO_BANDEJA
							WHERE DOCUMENTO = '$document' AND ANIO = '$year' AND CODTRAMITE = 192
							AND (NUMDOCTOS IS NOT NULL AND FECHA_FINALIZACION IS NOT NULL)
							AND DEPENDENCIA IN (92,94)";

				$stid = oci_parse($this->dbConn, $query);
				oci_execute($stid);

				$result = oci_fetch_array($stid, OCI_ASSOC);
                $usuario = $result["USUARIO"];
                $fecha = $result["FECHA"];

				/** Registrar en table de calidad */

				$explode_errores_trabajo = implode(',', $errores_trabajo_str);

	            $query = "INSERT INTO CDO_Q_DOCUMENTO (DOCUMENTO, ANIO, FECHA, ERROR, ERRORES, USUARIO) VALUES ('$document', '$year', SYSDATE, 1, '$explode_errores_trabajo', '$usuario')";

	            $stid = oci_parse($this->dbConn, $query);

	            if (false === oci_execute($stid)) {

	                $err = oci_error($stid);

	                $str_error = "No se pudo procesar la solicitud";

	                $this->throwError($err["code"], $str_error);

	            }

			}

            // Registro en tabla de Indicadores

            $user = 'rrhh';
            $password = 'rrhhadmin';
            $dbName = '   (DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 172.23.50.95)(PORT = 1521)))(CONNECT_DATA = (SERVICE_NAME = CATGIS)))';

            $connection_rrhh = oci_connect($user, $password, $dbName, 'UTF8');

            $query = "  INSERT INTO PDI_MAESTRO_REGISTROS (ID_REGISTRO,
                                    ID_PROCESO,
                                        ID_CATEGORIA,
                                        REFERENCIA,
                                        ID_ESTADO,
                                        USUARIO,
                                        FECHA,
                                        FECHA_CREACION)
                        VALUES (SQ_PDI_MAESTRO.NEXTVAL,
                                        14,
                                        1,
                                        '".$document."-".$year."',
                                        2,
                                        '$usuario',
                                        '$fecha',
                                        SYSDATE)";

            $stid = oci_parse($connection_rrhh, $query);
            oci_execute($stid);

			$this->returnResponse(SUCCESS_RESPONSE, array($error_ingreso, $error_trabajo, $explode_errores_ingreso, $errores_trabajo, $query_update));

			// $stid = oci_parse($this->dbConn, $query);
			// oci_execute($stid);
			//
			// $result = oci_fetch_array($stid, OCI_ASSOC);
			// $usuario = $result["USUARIO"];
			//
            // /** Registrar en table de calidad */
            // $query = "INSERT INTO CDO_Q_DOCUMENTO (DOCUMENTO, ANIO, FECHA, ERROR, ERRORES, USUARIO) VALUES ('$document', '$year', SYSDATE, 1, '$errores', '$usuario')";
			//
            // $stid = oci_parse($this->dbConn, $query);
			//
            // if (false === oci_execute($stid)) {
			//
            //     $err = oci_error($stid);
			//
            //     $str_error = "No se pudo procesar la solicitud";
			//
            //     $this->throwError($err["code"], $str_error);
			//
            // }

            $this->returnResponse(SUCCESS_RESPONSE, $id_errores);

        }

        public function obtenerCriterios(){

            $fase = $this->validateParameter('fase', $this->param['fase'], STRING);


        }

        /** Detalles de un documento */
        public function detallesDocumento(){

            $documento = $this->validateParameter('id_documento', $this->param['id_documento'], STRING);
            $fase = $this->validateParameter('fase', $this->param['fase'], INTEGER);

            $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                        DOC.CODIGOCLASE, TO_CHAR(DOC.FECHA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, DOC.STATUS, DOC.PRIMER_NOMBRE, DOC.SEGUNDO_NOMBRE, DOC.PRIMER_APELLIDO, DOC.SEGUNDO_APELLIDO, DOC.RAZON_SOCIAL, DOC.PLAZO_HORAS, DOC.USUARIO, NVL(DOC.CALIDAD, 0) AS CALIDAD
                        FROM CDO_DOCUMENTO DOC
                        INNER JOIN CDO_DETDOCUMENTO DET
                        ON DOC.DOCUMENTO = DET.DOCUMENTO
                        AND DOC.ANIO = DET.ANIO
                        AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                        AND DOC.CODIGOCLASE = 3
                        AND DET.CODTRAMITE = 192
                        WHERE CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) = '$documento'";

            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $detalles_documento = oci_fetch_array($stid, OCI_ASSOC);

            /** Buscar si el documento tiene errores */
            $no_documento = $detalles_documento["DOCUMENTO_"];
            $year = $detalles_documento["ANIO_"];

            $query = "	SELECT TO_CHAR(FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, ERRORES, USUARIO
						FROM CDO_Q_DOCUMENTO
						WHERE DOCUMENTO = $no_documento AND ANIO = $year AND ERROR = 1 ORDER BY FECHA DESC";
            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $errores = array();
            while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                $str = $data["ERRORES"];
                $array_str = explode(',', $str);
                $data["LISTA_ERRORES"] = $array_str;

                $errores[] = $data;

            }

            $detalles_documento["ERRORES"] = $errores;

            /** Obtener criterios a calificar */
            $query = "SELECT * FROM CDO_CRITERIOS_SIMA WHERE FASE = $fase ORDER BY ID ASC";
            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $criterios = array();
            while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                $criterios [] = $data;

            }

            $detalles_documento["CRITERIOS_CALIDAD"] = $criterios;

            $this->returnResponse(SUCCESS_RESPONSE, $detalles_documento);

        }

        /** Módulo de traslado de documentos */
        public function documentoParaTraslado(){

            /** Parametros */
            $fecha_inicio = $this->param['fecha_inicio'];
            $fecha_fin = $this->param['fecha_fin'];

            $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
                        DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD') AS FECHA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD') AS FECHA_ENTREGA_CALCULO, DOC.NUMERO_DOCTO_SALIDA
                        FROM CDO_DOCUMENTO DOC
                        INNER JOIN CDO_DETDOCUMENTO DET
                        ON DOC.DOCUMENTO = DET.DOCUMENTO
                        AND DOC.ANIO = DET.ANIO
                        AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                        AND DOC.CODIGOCLASE = 3
                        AND DET.CODTRAMITE = 192
                        AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                        AND DOC.NUMERO_DOCTO_SALIDA IS NOT NULL
                        AND DOC.FECHA_ENTREGA IS NULL
                        ORDER BY DOC.DOCUMENTO ASC";

            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $documentos = array();

            /** Contadores */
            $en_tiempo = 0;
            $en_proceso = 0;
            $fuera_tiempo = 0;

            while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                $fecha_recibido = $data["FECHA_CALCULO"];
                $plazo = $data["PLAZO_HORAS"];

                $plazo = intval($plazo) / 24;
                $str_plazo = "+".$plazo." weekday";

                $now = new DateTime($fecha_recibido); //current date/time
                $now->modify($str_plazo);
                $new_time = $now->format('d/m/Y');
                $calculo_fecha_limite = $now->format('Y/m/d');

                /* Fecha actual */
                $hoy = new DateTime();
                $hoy = $hoy->format('Y/m/d');

                /* La fecha de entrega debe ser menor o igual a la fecha limite */
                /* Si existe fecha de entrega */
                if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

                    if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
                        $data["CUMPLIMIENTO"] = 1;
                        $en_tiempo++;
                    }else{
                        $data["CUMPLIMIENTO"] = 0;
                        $fuera_tiempo++;
                    }

                }else{

                    $en_proceso++;

                }

                $data["FECHA_LIMITE"] = $new_time;

                $documentos [] = $data;

            }

            $this->returnResponse(SUCCESS_RESPONSE, $documentos);

        }

        public function trasladarDocumentos(){

            $documentos = $this->param['documentos'];

            $this->returnResponse(SUCCESS_RESPONSE, $documentos);

        }

        public function documentosTrasladados(){

            /** Parametros */
            $fecha_inicio = $this->param['fecha_inicio'];
            $fecha_fin = $this->param['fecha_fin'];

            $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
                        DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD') AS FECHA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD') AS FECHA_ENTREGA_CALCULO, DOC.NUMERO_DOCTO_SALIDA
                        FROM CDO_DOCUMENTO DOC
                        INNER JOIN CDO_DETDOCUMENTO DET
                        ON DOC.DOCUMENTO = DET.DOCUMENTO
                        AND DOC.ANIO = DET.ANIO
                        AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                        AND DOC.CODIGOCLASE = 3
                        AND DET.CODTRAMITE = 192
                        AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                        AND DOC.NUMERO_DOCTO_SALIDA IS NOT NULL
                        AND DOC.FECHA_ENTREGA IS NOT NULL
                        ORDER BY DOC.DOCUMENTO ASC";

            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $documentos = array();

            while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                $documentos [] = $data;

            }

            $this->returnResponse(SUCCESS_RESPONSE, $documentos);

        }

        /* Módulo de finalización de documentos */
        public function documentos_pendientes_finalizar(){

            $fecha_inicio = $this->param['fecha_inicio'];
            $fecha_fin = $this->param['fecha_fin'];

            $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, 
                        DOC.DOCUMENTO AS DOCUMENTO_, DOC.ANIO AS ANIO_,
                        DOC.PRIMER_NOMBRE, DOC.FOLIOS, DOC.DIR_NOTIFICA, DOC.TELEFONO,
                        DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, 
                        TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA_ENTREGA, DOC.USUARIO, DOC.PLAZO_HORAS, NVL(DOC.CALIDAD, 0) AS CALIDAD
                        FROM CDO_DOCUMENTO DOC
                        INNER JOIN CDO_DETDOCUMENTO DET
                        ON DOC.DOCUMENTO = DET.DOCUMENTO
                        AND DOC.ANIO = DET.ANIO
                        AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                        AND DOC.CODIGOCLASE = 3
                        AND DET.CODTRAMITE = 192
                        AND DOC.FECHA_ISO IS NULL
                        AND DOC.STATUS = 6
                        AND DOC.FECHA_FINALIZACION IS NULL
                        AND DOC.FECHA_ENTREGA IS NOT NULL
                        AND FECHA BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                        ORDER BY DOC.DOCUMENTO DESC";

            $stid = oci_parse($this->dbConn, $query);

            if (false === oci_execute($stid)) {

                $err = oci_error($stid);

                $str_error = "No se pudo procesar la solicitud";

                $this->throwError($err["code"], $str_error);

            }

            $documentos = array();

            while ($data = oci_fetch_array($stid, OCI_ASSOC)) {
               
                $documentos [] = $data;

            }

            $this->returnResponse(SUCCESS_RESPONSE, $documentos);

        }

        public function finalizar_documentos(){

            $documentos = $this->param['documentos'];

            foreach ($documentos as $documento) {
                
                $query = "  UPDATE CDO_DOCUMENTO
                            SET STATUS = 2,
                            FECHA_FINALIZACION = SYSDATE
                            WHERE CODIGOCLASE = 3
                            AND FECHA_ISO IS NULL
                            AND STATUS = 6
                            AND CONCAT(CONCAT(DOCUMENTO , ' - '), ANIO) = '$documento'
                            AND FECHA_FINALIZACION IS NULL";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

            }

            $this->returnResponse(SUCCESS_RESPONSE, $documentos);

        }

        /** Módulo de cumplimientos de plazos */
        public function reporte_cumplimiento_plazos_filtrado(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"https://udicat.muniguate.com/apps/calidad_sima_api/");
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = array(
                "name" => "reporte_cumplimiento_plazos_filtrado",
                "param" => array(
                    "fecha_inicio" => $fecha_inicio,
                    "fecha_fin" => $fecha_fin
                )
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

            $server_output = curl_exec($ch);


            // $fecha_reporte = $fecha_inicio . " - " . $fecha_fin;

            // try {

            //     $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
            //                 DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_CALCULO, TO_CHAR(DOC.FECHA, 'HH24:MI:SS') AS HORA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_ENTREGA_CALCULO
            //                 FROM CDO_DOCUMENTO DOC
            //                 INNER JOIN CDO_DETDOCUMENTO DET
            //                 ON DOC.DOCUMENTO = DET.DOCUMENTO
            //                 AND DOC.ANIO = DET.ANIO
            //                 AND DOC.CODIGOCLASE = DET.CODIGOCLASE
            //                 AND DOC.CODIGOCLASE = 3
            //                 AND DET.CODTRAMITE = 192
            //                 AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
            //                 ORDER BY DOC.DOCUMENTO ASC";

            //     $stid = oci_parse($this->dbConn, $query);

            //     if (false === oci_execute($stid)) {

            //         $err = oci_error($stid);

			// 		$str_error = "No se pudo procesar la solicitud";

            //         $this->throwError($err["code"], $str_error);

            //     }

            //     $lotes = array();

            //     /** Contadores */
            //     $en_tiempo = 0;
            //     $en_proceso = 0;
            //     $fuera_tiempo = 0;

            //     while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

            //         $fecha_recibido = $data["FECHA_CALCULO"];
            //         $hora_recibido = $data["HORA_CALCULO"];
            //         $plazo = $data["PLAZO_HORAS"];

            //         $horas_dias = intval($plazo) / 24;

            //         $dias = intval($horas_dias);
            //         $horas = ($horas_dias - $dias) * 24;

            //         $now = new DateTime($fecha_recibido); 
            //         $str_plazo = "+".$dias." weekday $hora_recibido";
            //         $now->modify($str_plazo);
                    
            //         $str_plazo = "+" .$horas. " hours";
            //         $now->modify($str_plazo);

            //         $new_time = $now->format('d/m/Y H:i:s');
            //         $calculo_fecha_limite = $now->format('Y/m/d H:i:s');

            //         /*
            //         $plazo = ceil(intval($plazo) / 24);
            //         $str_plazo = "+".$plazo." weekday";

            //         $now = new DateTime($fecha_recibido); //current date/time
            //         $now->modify($str_plazo);
            //         $new_time = $now->format('d/m/Y H:i:s');
            //         $calculo_fecha_limite = $now->format('Y/m/d H:i:s');
            //         */

            //         /* Fecha actual */
            //         $hoy = new DateTime();
            //         $hoy = $hoy->format('Y/m/d');

            //         /* La fecha de entrega debe ser menor o igual a la fecha limite */
            //         /* Si existe fecha de entrega */
            //         if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

            //             if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
            //                 $data["CUMPLIMIENTO"] = 1;
            //                 $en_tiempo++;
            //             }else{
            //                 $data["CUMPLIMIENTO"] = 0;
            //                 $fuera_tiempo++;
            //             }

            //         }else{
            //             $en_proceso++;
            //         }

            //         $data["FECHA_LIMITE"] = $new_time;
            //         $fecha_entrega = $data["FECHA_ENTREGA_CALCULO"];
            //         $lotes [] = $data;

            //     }

            //     $this->returnResponse(SUCCESS_RESPONSE, array($lotes, $fecha_reporte, $en_tiempo, $en_proceso, $fuera_tiempo, $fecha_entrega, $calculo_fecha_limite));

            // } catch (\Throwable $th) {

            //     $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            // }

        }

        public function reporte_cumplimiento_plazos(){

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"https://udicat.muniguate.com/apps/calidad_sima_api/");
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = array(
                "name" => "reporte_cumplimiento_plazos",
                "param" => array()
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

            $server_output = curl_exec($ch);

            // $this->returnResponse(SUCCESS_RESPONSE, $server_output);


            // $first_day_this_month = date('01/m/Y');
            // $last_day_this_month  = date('t/m/Y');
            // $month = date('m/Y');

            // $fecha_reporte = $first_day_this_month . " - " . $last_day_this_month;

            // try {

            //     $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
            //                 DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_CALCULO, TO_CHAR(DOC.FECHA, 'HH24:MI:SS') AS HORA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_ENTREGA_CALCULO
            //                 FROM CDO_DOCUMENTO DOC
            //                 INNER JOIN CDO_DETDOCUMENTO DET
            //                 ON DOC.DOCUMENTO = DET.DOCUMENTO
            //                 AND DOC.ANIO = DET.ANIO
            //                 AND DOC.CODIGOCLASE = DET.CODIGOCLASE
            //                 AND DOC.CODIGOCLASE = 3
            //                 AND DET.CODTRAMITE = 192
            //                 AND TO_CHAR(DOC.FECHA, 'MM/YYYY') = '$month'
            //                 ORDER BY DOC.DOCUMENTO ASC";

            //     $stid = oci_parse($this->dbConn, $query);

            //     if (false === oci_execute($stid)) {

            //         $err = oci_error($stid);

			// 		$str_error = "No se pudo procesar la solicitud";

            //         $this->throwError($err["code"], $str_error);

            //     }

            //     $lotes = array();

            //     /** Contadores */
            //     $en_tiempo = 0;
            //     $en_proceso = 0;
            //     $fuera_tiempo = 0;

            //     while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

            //         $fecha_recibido = $data["FECHA_CALCULO"];
            //         $hora_recibido = $data["HORA_CALCULO"];
            //         $plazo = $data["PLAZO_HORAS"];

            //         $horas_dias = intval($plazo) / 24;

            //         $dias = intval($horas_dias);
            //         $horas = ($horas_dias - $dias) * 24;

            //         $str_plazo = "+".$dias." weekday $hora_recibido";
            //         $now = new DateTime($fecha_recibido); 
            //         $now->modify($str_plazo);
                    
            //         $str_plazo = "+" .$horas. " hours";
            //         $now->modify($str_plazo);

            //         $new_time = $now->format('d/m/Y H:i:s');

            //         //$new_time = date("d/m/Y H:i:s", strtotime('+5 hours', $now));

            //         $calculo_fecha_limite = $now->format('Y/m/d H:i:s');

            //         /* Fecha actual */
            //         $hoy = new DateTime();
            //         $hoy = $hoy->format('Y/m/d');

            //         /* La fecha de entrega debe ser menor o igual a la fecha limite */
            //         /* Si existe fecha de entrega */
            //         if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

            //             if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
            //                 $data["CUMPLIMIENTO"] = 1;
            //                 $en_tiempo++;
            //             }else{
            //                 $data["CUMPLIMIENTO"] = 0;
            //                 $fuera_tiempo++;
            //             }

            //         }else{

            //             $en_proceso++;

            //         }

            //         $data["FECHA_LIMITE"] = $new_time;
            //         $lotes [] = $data;

            //     }

            //     // $this->returnResponse(SUCCESS_RESPONSE, array($lotes, $fecha_reporte, $en_tiempo, $en_proceso, $fuera_tiempo, $first_day_this_month, $last_day_this_month));

            // } catch (\Throwable $e) {

            //     $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            // }

        }

        public function documentosEnTiempo(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"https://udicat.muniguate.com/apps/calidad_sima_api/");
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = array(
                "name" => "documentosEnTiempo",
                "param" => array(
                    "fecha_inicio" => $fecha_inicio,
                    "fecha_fin" => $fecha_fin
                )
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

            $server_output = curl_exec($ch);

            // try {

            //     $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
            //                 DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD') AS FECHA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD') AS FECHA_ENTREGA_CALCULO
            //                 FROM CDO_DOCUMENTO DOC
            //                 INNER JOIN CDO_DETDOCUMENTO DET
            //                 ON DOC.DOCUMENTO = DET.DOCUMENTO
            //                 AND DOC.ANIO = DET.ANIO
            //                 AND DOC.CODIGOCLASE = DET.CODIGOCLASE
            //                 AND DOC.CODIGOCLASE = 3
            //                 AND DET.CODTRAMITE = 192
            //                 AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
            //                 AND DOC.FECHA_ENTREGA IS NOT NULL
            //                 ORDER BY DOC.DOCUMENTO ASC";

            //     $stid = oci_parse($this->dbConn, $query);

            //     if (false === oci_execute($stid)) {

            //         $err = oci_error($stid);

            //         $str_error = "No se pudo procesar la solicitud";

            //         $this->throwError($err["code"], $str_error);

            //     }

            //     $lotes = array();

            //      /** Contadores */
            //      $en_tiempo = 0;
            //      $en_proceso = 0;
            //      $fuera_tiempo = 0;

            //     while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

            //         $fecha_recibido = $data["FECHA_CALCULO"];
            //         $plazo = $data["PLAZO_HORAS"];

            //         $plazo = intval($plazo) / 24;
            //         $str_plazo = "+".$plazo." weekday";

            //         $now = new DateTime($fecha_recibido); //current date/time
            //         $now->modify($str_plazo);
            //         $new_time = $now->format('d/m/Y');
            //         $calculo_fecha_limite = $now->format('Y/m/d');

            //         /* Fecha actual */
            //         $hoy = new DateTime();
            //         $hoy = $hoy->format('Y/m/d');

            //         /* La fecha de entrega debe ser menor o igual a la fecha limite */
            //         /* Si existe fecha de entrega */
            //         if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

            //             if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
            //                 $data["CUMPLIMIENTO"] = 1;
            //                 $en_tiempo++;
            //             }else{
            //                 $data["CUMPLIMIENTO"] = 0;
            //                 $fuera_tiempo++;
            //             }

            //         }else{

            //             $en_proceso++;

            //         }

            //         $data["FECHA_LIMITE"] = $new_time;
            //         $lotes [] = $data;

            //     }

            //     $this->returnResponse(SUCCESS_RESPONSE, $lotes);

            // } catch (\Throwable $e) {

            //     $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            // }

        }

        public function documentosEnProceso(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"https://udicat.muniguate.com/apps/calidad_sima_api/");
            curl_setopt($ch, CURLOPT_POST, 1);

            $data = array(
                "name" => "documentosEnProceso",
                "param" => array(
                    "fecha_inicio" => $fecha_inicio,
                    "fecha_fin" => $fecha_fin
                )
            );

            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

            $server_output = curl_exec($ch);

            // try {

            //     $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
            //                 DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD') AS FECHA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA_CALCULO
            //                 FROM CDO_DOCUMENTO DOC
            //                 INNER JOIN CDO_DETDOCUMENTO DET
            //                 ON DOC.DOCUMENTO = DET.DOCUMENTO
            //                 AND DOC.ANIO = DET.ANIO
            //                 AND DOC.CODIGOCLASE = DET.CODIGOCLASE
            //                 AND DOC.CODIGOCLASE = 3
            //                 AND DET.CODTRAMITE = 192
            //                 AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
            //                 AND DOC.FECHA_ENTREGA IS NULL
            //                 ORDER BY DOC.DOCUMENTO ASC";

            //     $stid = oci_parse($this->dbConn, $query);

            //     if (false === oci_execute($stid)) {

            //         $err = oci_error($stid);

            //         $str_error = "No se pudo procesar la solicitud";

            //         $this->throwError($err["code"], $str_error);

            //     }

            //     $lotes = array();

            //      /** Contadores */
            //      $en_tiempo = 0;
            //      $en_proceso = 0;
            //      $fuera_tiempo = 0;

            //     while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

            //         $fecha_recibido = $data["FECHA_CALCULO"];
            //         $plazo = $data["PLAZO_HORAS"];

            //         $plazo = intval($plazo) / 24;
            //         $str_plazo = "+".$plazo." weekday";

            //         $now = new DateTime($fecha_recibido); //current date/time
            //         $now->modify($str_plazo);
            //         $new_time = $now->format('d/m/Y');
            //         $calculo_fecha_limite = $now->format('Y/m/d');

            //         /* Fecha actual */
            //         $hoy = new DateTime();
            //         $hoy = $hoy->format('Y/m/d');

            //         /* La fecha de entrega debe ser menor o igual a la fecha limite */
            //         /* Si existe fecha de entrega */
            //         if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

            //             if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
            //                 $data["CUMPLIMIENTO"] = 1;
            //                 $en_tiempo++;
            //             }else{
            //                 $data["CUMPLIMIENTO"] = 0;
            //                 $fuera_tiempo++;
            //             }

            //         }else{

            //             $en_proceso++;

            //         }

            //         $data["FECHA_LIMITE"] = $new_time;
            //         $lotes [] = $data;

            //     }

            //     $this->returnResponse(SUCCESS_RESPONSE, $lotes);

            // } catch (\Throwable $e) {

            //     $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            // }

        }

        public function documentosFueraTiempo(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            try {

                $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD') AS FECHA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD') AS FECHA_ENTREGA_CALCULO
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            AND DOC.FECHA_ENTREGA IS NULL
                            ORDER BY DOC.DOCUMENTO ASC";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

                    $str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                $lotes = array();

                /** Contadores */
                $en_tiempo = 0;
                $en_proceso = 0;
                $fuera_tiempo = 0;

                while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

                    $fecha_recibido = $data["FECHA_CALCULO"];
                    $plazo = $data["PLAZO_HORAS"];

                    $plazo = intval($plazo) / 24;
                    $str_plazo = "+".$plazo." weekday";

                    $now = new DateTime($fecha_recibido); //current date/time
                    $now->modify($str_plazo);
                    $new_time = $now->format('d/m/Y');
                    $calculo_fecha_limite = $now->format('Y/m/d');

                    /* Fecha actual */
                    $hoy = new DateTime();
                    $hoy = $hoy->format('Y/m/d');

                    /* La fecha de entrega debe ser menor o igual a la fecha limite */
                    /* Si existe fecha de entrega */
                    if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

                        if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
                            $data["CUMPLIMIENTO"] = 1;
                            $en_tiempo++;
                        }else{
                            $data["CUMPLIMIENTO"] = 0;
                            $fuera_tiempo++;

                            $data["FECHA_LIMITE"] = $new_time;
                            $lotes [] = $data;

                        }

                    }else{

                        $en_proceso++;

                    }

                }

                $this->returnResponse(SUCCESS_RESPONSE, $lotes);

            } catch (\Throwable $e) {

                $this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());

            }

        }

        /** Gráficas Dashboard */
        public function graficaPlazos(){

            $first_day_this_month = date('01/m/Y');
            $last_day_this_month  = date('t/m/Y');
            $month = date('m/Y');
            
            $fechas_no_validas = [

                strtotime("2021/03/29"),
                strtotime("2021/03/30"),
                strtotime("2021/03/31"),
                strtotime("2021/04/01"),
                strtotime("2021/04/02"),
                strtotime("2021/04/30"),
                strtotime("2021/05/03"),
                strtotime("2021/06/28"),
                strtotime("2021/07/01"),
                strtotime("2021/07/26"),
                strtotime("2021/08/16"),
                strtotime("2021/09/15"),
                strtotime("2021/10/20"),
                strtotime("2021/11/01"),
                strtotime("2021/12/24"),
                strtotime("2021/12/25"),
                strtotime("2021/12/26"),
                strtotime("2021/12/27"),
                strtotime("2021/12/28"),
                strtotime("2021/12/29"),
                strtotime("2021/12/30"),
                strtotime("2021/12/31"),
                strtotime("2022/01/01"),
                strtotime("2022/01/02"),
                strtotime("2022/01/03")
            ];

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            $fecha_reporte = $fecha_inicio . " - " . $fecha_fin;

            try {

                $query = "  SELECT CONCAT(CONCAT(DOC.DOCUMENTO, ' - '), DOC.ANIO) AS DOCUMENTO, DOC.PRIMER_NOMBRE,
                            DOC.PRIMER_APELLIDO, TO_CHAR(DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA, TO_CHAR(DOC.FECHA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_CALCULO, TO_CHAR(DOC.FECHA, 'HH24:MI:SS') AS HORA_CALCULO, DOC.USUARIO, DOC.PLAZO_HORAS, TO_CHAR(DOC.FECHA_ENTREGA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA_ENTREGA, TO_CHAR(DOC.FECHA_ENTREGA, 'YYYY/MM/DD HH24:MI:SS') AS FECHA_ENTREGA_CALCULO
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND TO_DATE(DOC.FECHA, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            ORDER BY DOC.DOCUMENTO ASC";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

					$str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                $lotes = array();

                /** Contadores */
                $ingresados = 0;
                $en_tiempo = 0;
                $en_proceso = 0;
                $fuera_tiempo = 0;

                while ($data = oci_fetch_array($stid,OCI_ASSOC)) {

                    $fecha_recibido = $data["FECHA_CALCULO"];
                    $hora_recibido = $data["HORA_CALCULO"];
                    $plazo = $data["PLAZO_HORAS"];
                    
                    $horas_dias = intval($plazo) / 24;

                    $dias = intval($horas_dias);
                    $horas = ($horas_dias - $dias) * 24;

                    $plazo = intval($plazo) / 24;

                    $str_plazo = "+".$dias." weekday $hora_recibido";
                    $now = new DateTime($fecha_recibido); 
                    $now->modify($str_plazo);
                    
                    $str_plazo = "+" .$horas. " hours";
                    $now->modify($str_plazo);

                    $new_time = $now->format('d/m/Y H:i:s');
                    $calculo_fecha_limite = $now->format('Y/m/d H:i:s');
                    
                    //Fecha de Recepción para calculo
                    $recepcion = strtotime(date("Y/m/d",strtotime($data["FECHA_CALCULO"])));
                    $str_recepcion = date("Y/m/d",strtotime($data["FECHA_CALCULO"]));

                    foreach ($fechas_no_validas as $dia) {
                        
                        if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

                            if ($recepcion <= $dia && strtotime($data["FECHA_ENTREGA_CALCULO"]) >= $dia) {

                                $data["SUMAR_DIA"] = true;

                                $fecha_limite = explode(" ", $new_time);
                                $fecha = explode("/", $fecha_limite[0]);

                                $fecha_limite_format = $fecha[2]. "/" . $fecha[1] . "/" . $fecha[0] . " " . $fecha_limite[1];
                
                                $data["FECHA_LIMITE_FORMAT"] = $fecha_limite_format;

                                $now = new DateTime($fecha_limite_format); 
                                $str_plazo = "+1 weekday " . $fecha_limite[1];
                                $now->modify($str_plazo);
                                $new_time = $now->format('d/m/Y H:i:s');
                                $calculo_fecha_limite = $now->format('Y/m/d H:i:s');

                            }
                        
                        }

                    }

                    /* Fecha actual */
                    $hoy = new DateTime();
                    $hoy = $hoy->format('Y/m/d');

                    /* La fecha de entrega debe ser menor o igual a la fecha limite */
                    /* Si existe fecha de entrega */
                    if (array_key_exists('FECHA_ENTREGA_CALCULO', $data)) {

                        if (strtotime($data["FECHA_ENTREGA_CALCULO"]) <= strtotime($calculo_fecha_limite)) {
                            
                            $en_tiempo++;

                        }else{

                            if ($plazo) {
                                
                                $fuera_tiempo++;

                            }else{
                                
                                 $en_tiempo++;
                                
                            }

                        }

                    }else{
                        $en_proceso++;
                    }

                    $ingresados++;

                }

                $valores_grafica = array($ingresados, $en_tiempo, $en_proceso, $fuera_tiempo, $query);

                $this->returnResponse(SUCCESS_RESPONSE, $valores_grafica);

            } catch (\Throwable $th) {

                $this->throwError(JWT_PROCESSING_ERROR, $th->getMessage());

            }

        }

        public function graficaCalidad(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            try {

                $query = "  SELECT *
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND TO_DATE(DOC.FECHA_ISO, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            AND DOC.FECHA_ISO IS NOT NULL
                            ORDER BY DOC.DOCUMENTO ASC";

                $stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

					$str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

                /** Total de documentos sometidos a control de calidad */
                $documentos_calidad = array();
                $rechazados = 0;
                $correctos = 0;

                while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

                    $documento = $data["DOCUMENTO"];
                    $year = $data["ANIO"];

                    /** Buscar documentos correctos */
                    $query = "SELECT COUNT(*) AS RECHAZADOS FROM CDO_Q_DOCUMENTO WHERE DOCUMENTO = $documento AND ANIO = $year AND ERROR IS NOT NULL";

                    $stid_1 = oci_parse($this->dbConn, $query);
                    oci_execute($stid_1);

                    $documentos_error = oci_fetch_array($stid_1, OCI_ASSOC);

                    if ($documentos_error["RECHAZADOS"]) {

                        $rechazados++;

                    }else{

                        /** Si no tiene ningun error validar si esta pendiente o correcto */
                        if ($data["CALIDAD"] == 2) {

                            $correctos++;

                        }

                    }

                    $documentos_calidad [] = $data;

                }

                $pendientes = count($documentos_calidad) - $correctos - $rechazados;

                $grafica = array(count($documentos_calidad), $correctos, $pendientes, $rechazados);

                $this->returnResponse(SUCCESS_RESPONSE, $grafica);

            } catch (\Throwable $th) {

                $this->throwError(JWT_PROCESSING_ERROR, $th->getMessage());

            }

        }

		public function graficaCriterios(){

			/** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

			/* Array de colores para grafica */

			$backgroundColors = array(
										'rgba(60, 234, 87, 0.5)',
										'rgba(66, 134, 244, 0.5)',
										'rgba(65, 181, 244, 0.5)',
										'rgba(106, 65, 244, 0.5)',
										'rgba(65, 244, 193, 0.5)',
										'rgba(175, 244, 65, 0.5)',
										'rgba(232, 244, 65, 0.5)',
										'rgba(244, 187, 65, 0.5)',
										'rgba(244, 139, 65, 0.5)',
										'rgba(244, 94, 65, 0.5)',
										'rgba(244, 65, 65, 0.5)',
										'rgba(158, 64, 148, 0.5)',
										'rgba(110, 85, 181, 0.5)',
										'rgba(86, 93, 119, 0.5)',
										'rgba(86, 158, 206, 0.5)',
										'rgba(75, 117, 110, 0.5)',
										'rgba(53, 81, 60, 0.5)',
										'rgba(110, 158, 44, 0.5)'
									);
			$borderColors = array(
									'rgba(60, 234, 87, 1)',
									'rgba(66, 134, 244, 1)',
									'rgba(65, 181, 244, 1)',
									'rgba(106, 65, 244, 1)',
									'rgba(65, 244, 193, 1)',
									'rgba(175, 244, 65, 1)',
									'rgba(232, 244, 65, 1)',
									'rgba(244, 187, 65, 1)',
									'rgba(244, 139, 65, 1)',
									'rgba(244, 94, 65, 1)',
									'rgba(244, 65, 65, 1)',
									'rgba(158, 64, 148, 1)',
									'rgba(110, 85, 181, 1)',
									'rgba(86, 93, 119, 1)',
									'rgba(86, 158, 206, 1)',
									'rgba(75, 117, 110, 1)',
									'rgba(53, 81, 60, 1)',
									'rgba(110, 158, 44, 1)'
								);

			try {

				/* Obtener de la tabla CDO_Q_DOCUMENTO los registros del control de calidad */
				// $query = "	SELECT ERRORES
				// 			FROM CDO_Q_DOCUMENTO
				// 			WHERE ERROR = 1
				// 			AND TO_DATE(FECHA, 'DD/MM/YYYY') BETWEEN
                // 			TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')";
                
                $query = "  SELECT ERRORES
                            FROM CDO_Q_DOCUMENTO T1
                            INNER JOIN CDO_DOCUMENTO T2
                            ON T1.DOCUMENTO = T2.DOCUMENTO 
                            WHERE ERROR = 1
                            AND T1.ANIO = T2.ANIO
                            AND TO_DATE(T2.FECHA_ISO, 'DD/MM/YYYY') BETWEEN
                            TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            AND T2.CODIGOCLASE = 3";

				$stid = oci_parse($this->dbConn, $query);
				oci_execute($stid);

				$errores = array();

				while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

					$errores [] = $data;

				}

				$query = "	SELECT CRITERIO
							FROM CDO_CRITERIOS_SIMA
							ORDER BY ID ASC";

				$stid = oci_parse($this->dbConn, $query);

                if (false === oci_execute($stid)) {

                    $err = oci_error($stid);

					$str_error = "No se pudo procesar la solicitud";

                    $this->throwError($err["code"], $str_error);

                }

				$criterios = array();
				$e = 0;

				while ($data = oci_fetch_array($stid, OCI_ASSOC)) {

					$i = 0;

					/* Por cada uno de los elementos de errores */
					foreach ($errores as $error) {

						$array_errores = explode(',', $error["ERRORES"]);

						foreach ($array_errores as $error_) {

							if ($data["CRITERIO"] == $error_) {

								$i++;

							}

						}

					}

					$dataset = array(
										"label" => $data["CRITERIO"],
										"data" => array($i),
										"backgroundColor" => $backgroundColors[$e],
										"borderColor" => $borderColors[$e],
										"borderWidth" => 2
									);

					$criterios [] = $dataset;

					$e++;

				}

				/* Crear un nuevo array  unicamente con los elementos mayores a 0 */
				$criterios_ = array();

				foreach ($criterios as $criterio) {

					if ($criterio["data"][0] > 0) {

						$criterios_ [] = $criterio;
					}

					//$this->returnResponse(SUCCESS_RESPONSE, $criterio["data"][0]);

				}

				$this->returnResponse(SUCCESS_RESPONSE, $criterios_);

			} catch (\Exception $e) {

			}


        }
        
        public function detalleDocumentosRechazados(){

            /** Parametros */
            $fecha_inicio = $this->validateParameter('fecha_inicio', $this->param['fecha_inicio'], STRING);
            $fecha_fin = $this->validateParameter('fecha_fin', $this->param['fecha_fin'], STRING);

            try {
                
                $query = "  SELECT DOC.DOCUMENTO, DOC.ANIO, Q_DOC.ERRORES, Q_DOC.USUARIO, TO_CHAR(Q_DOC.FECHA, 'DD/MM/YYYY HH24:MI:SS') AS FECHA
                            FROM CDO_DOCUMENTO DOC
                            INNER JOIN CDO_DETDOCUMENTO DET
                            ON DOC.DOCUMENTO = DET.DOCUMENTO
                            AND DOC.ANIO = DET.ANIO
                            AND DOC.CODIGOCLASE = DET.CODIGOCLASE
                            AND DOC.CODIGOCLASE = 3
                            AND DET.CODTRAMITE = 192
                            AND TO_DATE(DOC.FECHA_ISO, 'DD/MM/YYYY') BETWEEN TO_DATE('$fecha_inicio', 'DD/MM/YYYY') AND TO_DATE('$fecha_fin', 'DD/MM/YYYY')
                            AND DOC.FECHA_ISO IS NOT NULL
                            INNER JOIN CDO_Q_DOCUMENTO Q_DOC
                            ON Q_DOC.DOCUMENTO = DOC.DOCUMENTO
                            AND Q_DOC.ANIO = DOC.ANIO
                            AND Q_DOC.ERROR IS NOT NULL
                            ORDER BY DOC.DOCUMENTO ASC";

                $stid = oci_parse($this->dbConn, $query);
                oci_execute($stid);

                $documentos = array();

                while ($data = oci_fetch_array($stid, OCI_ASSOC)) {
                    
                    $errores_comma = $data["ERRORES"];
                    $errores = explode(',', $errores_comma);

                    $data["ERRORES"] = $errores;

                    $documentos [] = $data;

                }

                $this->returnResponse(SUCCESS_RESPONSE, $documentos);

            } catch (\Throwable $th) {
                //throw $th;
            }

        }

    }

?>
