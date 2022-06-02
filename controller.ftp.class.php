<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;


class ftpClientController
{
    private $connectionId;
    private $loginOk = false;
    private $messageArray = array();

    private function logMessage($message)
    {
        $this->messageArray[] = $message;
    }

    //regresa los mensajes de las funciones ftp
    public function getMessages()
    {
        return $this->messageArray;
    }

    public function connect($server, $ftpUser, $ftpPassword, $isPassive = false)
    {

        // *** Configurar conexión básica

        $this->connectionId = ftp_connect($server);

        // *** Iniciar sesión con usuario y contraseña

        $loginResult = ftp_login($this->connectionId, $ftpUser, $ftpPassword);

        // *** Activa/desactiva el modo pasivo (desactivado de forma predeterminada)

        ftp_pasv($this->connectionId, $isPassive);

        // *** Checar conexion
        if ((!$this->connectionId) || (!$loginResult)) {
            $this->logMessage('la conexión a FTP a fallado!');
            $this->logMessage('Attempted to connect to ' . $server . ' for user ' . $ftpUser, true);
            return false;
        } else {
            $this->logMessage('Connectado a ' . $server . ', para el usuario: ' . $ftpUser);
            $this->loginOk = true;
            return true;
        }
    }



    //crea un directorio 
    //$controllerFTPClient->makeDir("nombre de la carpeta a crear/otra carpeta a crear");

    public function crearDir($directory)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);
        // *** Si la creación de un directorio es exitosa...

        if (ftp_mkdir($this->connectionId, $directory)) {

            $this->logMessage('Directorio "' . $directory . '" creado satisfactoriamente');
            return true;
        } else {

            // *** ...si falla.
            $this->logMessage('fallo creando el directorio  "' . $directory . '"');
            return false;
        }
    }

    function explodeEnd($separador, $arr)
    {
        $explotar = explode($separador, $arr);
        return end($explotar);
    }

    //$dir = "esto-es-una-prueba/x2";
    //$fileFrom = 'aaa.webp';              
    //$fileTo = $dir . '/' . $fileFrom;

    function cargarArchivo($fileFrom, $fileTo)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        // *** Establecer el modo de transferencia (ASCII O BINARY)

        $asciiArray = array('txt', 'csv');
        $exp = explode('.', $fileFrom);
        $extension = end($exp);
        if (in_array($extension, $asciiArray)) {
            $mode = FTP_ASCII;
        } else {
            $mode = FTP_BINARY;
        }

        // *** aCtualizar archivo
        $upload = ftp_put($this->connectionId, $fileTo, $fileFrom, $mode);

        if (!$upload) {
            $this->logMessage('La carga FTP ha fallado!');
            return false;
        } else {
            $this->logMessage('Cargando "' . $fileFrom . '" como "' . $fileTo);
            return true;
        }
    }

    public function comprobarDir($directory)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        if (ftp_chdir($this->connectionId, $directory)) {
            $this->logMessage('El directorio existe: ' . ftp_pwd($this->connectionId));
            return true;
        } else {
            $this->logMessage('Directorio no encontrado');
            return false;
        }
    }

    //El método getListaDir devuelve una array que contiene nuestra lista de directorios - archivos.
    public function getListaDir($directory = '.', $parameters = '-la')
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        // obtwnwr contenido del directorio
        $contentsArray = ftp_nlist($this->connectionId, $parameters . '  ' . $directory);

        return $contentsArray;
    }

    public function getListaDirCondetalles($directorio)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);
        if ($directorio == "") {
            $directorio = "/";
        }
        // obtwnwr contenido del directorio
        $contentsArray = ftp_rawlist($this->connectionId, $directorio);

        return $contentsArray;
    }

    public function descargarArchivo($fileFrom, $fileTo)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        $asciiArray = array('txt', 'csv');
        $exp = explode('.', $fileFrom);
        $extension = end($exp);
        if (in_array($extension, $asciiArray)) {
            $mode = FTP_ASCII;
        } else {
            $mode = FTP_BINARY;
        }

        if (ftp_get($this->connectionId, $fileTo, $fileFrom, $mode, 0)) {

            return true;
            $this->logMessage(' Archivo "' . $fileTo . '" descargado correctamente');
        } else {

            return false;
            $this->logMessage('error descargando el archivo "' . $fileFrom . '" a "' . $fileTo . '"');
        }
    }





    public $arrayExtenciones = array('txt', 'webp', 'db', 'ico', 'csv', 'png', 'jpg', 'pdf', 'config', 'shtml', 'php', 'html', 'jpeg', 'svg', 'DS_Store', 'gif', 'old', 'mp4');

    function comprobarArchivos($dirRemote, $localDirect)
    {
        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        $expS = str_replace(" ", "", "\ ");
        $res = explode($expS, __FILE__);
        $documentRoot = $_SERVER["DOCUMENT_ROOT"] . "/" . $res[3];
        $localDir = $documentRoot . $localDirect;
        $listaDir = $this->getListaDir($dirRemote);
        $countArchivos = 0;
        $countArchivosCargados = 0;
        $countArchivosNOCargados = 0;
        $countdir = 0;
        $file = $listaDir;
        $data["data"] =
            [
                "dir_raiz" => $dirRemote,
                "content" => []
            ];

        for ($i = 0; $i < count($file); $i++) {
            $py = 30;
            $fileTo = explodeEnd("/", $file[$i]);
            $createDir = $localDir . "/" . $fileTo;

            $arrExtenciones = $this->arrayExtenciones;
            $ext = explodeEnd(".", $file[$i]);
            if (in_array($ext, $arrExtenciones) && $ext != "remember") {
                $fsize = ftp_size($this->connectionId, $file[$i]);

                if (!file_exists($createDir)) {
                    $countArchivos++;
                    $countArchivosNOCargados++;

                    $data["data"]["content"][$i] = [
                        "tipo" => "Archivo",
                        "tamaño" => $fsize . " bytes",
                        "ruta" => $file[$i],
                        "nombre" => "🧾" . $fileTo,
                        "dir_local" => "no"
                    ];
                } else {
                    $countArchivos++;
                    $countArchivosCargados++;

                    $data["data"]["content"][$i] = [
                        "tipo" => "Archivo",
                        "tamaño" => $fsize . " bytes",
                        "ruta" => $file[$i],
                        "nombre" => "🧾" . $fileTo,
                        "dir_local" => "si"
                    ];
                }

                //$archivo = "<b style='color:black;'> $fileTo </b> <br>";
                //echo $archivo;
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        $countdir++;
                        $data["data"]["content"][$i] = [
                            "tipo" => "Carpeta",
                            "tamaño" => "null",
                            "ruta" => $file[$i],
                            "nombre" => "📁" . $fileTo,
                            "dir_local" => "no"
                        ];
                    } else {
                        $countdir++;
                        $data["data"]["content"][$i] = [
                            "tipo" => "Carpeta",
                            "tamaño" => "null",
                            "ruta" => $file[$i],
                            "nombre" => "📁" . $fileTo,
                            "dir_local" => "si"
                        ];
                    }
                    $carpeta = $fileTo;
                    do {
                        $buscador = $dirRemote . $carpeta . "/";
                        if ($this->comprobarDir($buscador) == true) {
                            if (explodeEnd(".", $buscador) != "/") {
                                $this->arrdir2($buscador, $createDir, $data);
                            }
                        }
                    } while ($this->comprobarDir($buscador) == false);
                }
            }
        }
        $total = array(
            "total_archivos" => $countArchivos,
            "total_directorios" => $countdir,
            "archivos_cargados" => $countArchivosCargados,
            "archivos_no_cargados" => $countArchivosNOCargados,
        );
        array_push($data["data"], $total);
        dump($data);
    }

    function arrdir2($dirRemote, $localDirect, $data)
    {

        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        $expS = str_replace(" ", "", "\ ");
        $res = explode($expS, __FILE__);
        $documentRoot = $_SERVER["DOCUMENT_ROOT"] . "/" . $res[3];
        $localDir = $documentRoot . $localDirect;
        $listaDir = $this->getListaDir($dirRemote);
        $countArchivos = 0;
        $file = $listaDir;

        for ($i = 0; $i < count($file); $i++) {
            $py = 30;
            $fileTo = explodeEnd("/", $file[$i]);
            $createDir = $localDir . "/" . $fileTo;

            $arrExtenciones = $this->arrayExtenciones;
            $ext = explodeEnd(".", $file[$i]);
            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, $file[$i]);

                if (!file_exists($createDir)) {

                    $countArchivos++;
                    /* $data["data"]["content"]["content"][$i] = [
                        "tipo" => "Archivo",
                        "tamaño" => $fsize . " bytes",
                        "ruta" => $file[$i],
                        "nombre" => "🧾 " . $fileTo,
                        "dir_local" => "no"
                    ]; */
                } else {
                    /* $data["data"]["content"]["content"][$i] = [
                        "tipo" => "Archivo",
                        "tamaño" => $fsize . " bytes",
                        "ruta" => $file[$i],
                        "nombre" => "🧾 " . $fileTo,
                        "dir_local" => "si"
                    ];
  */
                }

                //$archivo = "<b style='color:black;'> $fileTo </b> <br>";
                //echo $archivo;
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        /*  $data["data"]["content"]["content"][$i] = [
                            "tipo" => "Carpeta",
                            "tamaño" => "null",
                            "ruta" => $file[$i],
                            "nombre" => "📁 " . $fileTo,
                            "dir_local" => "no"
                        ]; */
                    } else {
                        /* $data["data"]["content"]["content"][$i] = [
                            "tipo" => "Carpeta",
                            "tamaño" => "null",
                            "ruta" => $file[$i],
                            "nombre" => "📁 " . $fileTo,
                            "dir_local" => "si"
                        ]; */
                    }
                    $carpeta = $fileTo;
                    do {
                        $buscador = $dirRemote . $carpeta . "/";
                        if ($this->comprobarDir($buscador) == true) {
                            if (explodeEnd(".", $buscador) != "/") {
                                $this->arrdir2($buscador, $createDir, $py);
                            }
                        }
                    } while ($this->comprobarDir($buscador) == false);
                }
            }
        }
        /*             array_push($data["data"], $total );
 */
        //dump($data);
    }

    public $tot_arch = 0;
    function getDirRemoto($dirRemote, $localDirect)
    {

        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        $expS = str_replace(" ", "", "\ ");
        $res = explode($expS, __FILE__);
        $documentRoot = $_SERVER["DOCUMENT_ROOT"] . "/" . $res[3];
        $localDir = $documentRoot . $localDirect;
        $listaDir = $this->getListaDir($dirRemote);
        $countArchivos = 0;
        $file = $listaDir;
        echo "
        <div class='card' style='background:#fff; color: #000; text-align: center; padding:15px; border: 3px solid none;'>
        <div class='card-body text-center'>
        <label><b>Directorio Local:</b> $localDir/ </label><br>
        <label><b>Directorio Remoto:</b> $dirRemote </label><br>
        <label>⚫ Se encuentra en tu directorio local </label><br>
        <label>🔴 No encuentra en tu directorio local </label><br>
        <br>
        
        <button class='contentoutofSyncall' style='padding: 15px; border-radius: 20px; background: #E6C000; color: white;'>Sincronizar todo</button>
        <button style='padding: 15px; border-radius: 20px; background: #1B9FD4; color: white;' class='contentoutofsync'>Sincronizar archivos no cargados</button>
          </div>
      </div>
      
         <div style='background:#fff; color: #000; margin:auto; padding:15px;>
        <b style='color:black;'>📁 $dirRemote </b> <br>";
        for ($i = 0; $i < count($file); $i++) {
            $py = 30;
            $fileTo = explodeEnd("/", $file[$i]);
            $createDir = $localDir . "/" . $fileTo;

            $arrExtenciones = $this->arrayExtenciones;
            $ext = explodeEnd(".", $file[$i]);
            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, "/" . $file[$i]);
                if (!file_exists($createDir)) {

                    $countArchivos++;

                    //echo $file[$i] . "<br>";
                    /*   if ($fsize != -1) {
                        echo " $fileTo is $fsize bytes.";
                    } else {
                        echo " no disponible.";
                    } */

                    echo "<b style='color:red;'>|__🧾" . $fileTo . " $fsize bytes</b><br>";
                    //mkdir($createDir, 0777, true);
                } else {
                    $countArchivos++;
                    echo "<b style='color:black;'>|__🧾" . $fileTo . " $fsize bytes</b><br>";
                }

                //$archivo = "<b style='color:black;'> $fileTo </b> <br>";
                //echo $archivo;
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        echo "<b style='color:red;'>|__📁 $fileTo </b><br>";
                    } else {
                        echo "<b style='color:black;'>|__📁 $fileTo </b><br> ";
                    }
                    $carpeta = $fileTo;
                    do {
                        $buscador = $dirRemote . $carpeta . "/";
                        if ($this->comprobarDir($buscador) == true) {
                            if (explodeEnd(".", $buscador) != "/") {

                                $this->dir2($buscador, $createDir, $py);
                            }
                        }
                    } while ($this->comprobarDir($buscador) == false);
                }
            }
        }
        echo "archivos encontrados $countArchivos<br>";
    }


    function dir2($dirRemote, $localDir, $py)
    {
        $listaDir = $this->getListaDir($dirRemote);
        /* $carpeta;
        $archivo; */
        $countArchivos = 0;
        $c = 0;
        $file = $listaDir;
        for ($i = 0; $i < count($file); $i++) {

            $fileTo = explodeEnd("/", $file[$i]);
            $createDir = $localDir . "/" . $fileTo;

            $arrExtenciones = $this->arrayExtenciones;
            $ext = explodeEnd(".", $file[$i]);
            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, "/" . $file[$i]);
                if (!file_exists($createDir)) {
                    /* echo $createDir;*/

                    echo "<b style='margin-left:" . $py . "px; color:red;'>|__🧾" . $fileTo . " $fsize bytes</b> <br>";
                    $countArchivos++;
                    /*                     $c = $c + $countArchivos;
 */                    //mkdir($createDir, 0777, true);
                } else {
                    echo "<b style='margin-left:" . $py . "px; color:black;'>|__🧾" . $fileTo . " $fsize bytes</b><br>";
                    $countArchivos++;
                    /*                     $c = $c + $countArchivos;
 */                    /* echo "  " . $c . " c"; */
                }
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    /* echo $createDir;*/
                    if (!file_exists($createDir)) {
                        echo "<b style='margin-left:" . $py . "px; color:red;'>|__📁 $fileTo </b> <br>";
                    } else {
                        echo "<b style='margin-left:" . $py . "px; color:black;'>|__📁 $fileTo </b> <br>";
                    }
                    $carpeta = $fileTo;

                    $buscador = $dirRemote . $carpeta . "/";
                    if ($this->comprobarDir($buscador) == true) {
                        if (explodeEnd(".", $buscador) != "/") {
                            $px = intval($py + 20);
                            $this->dir2($buscador, $localDir, $px);
                        }
                    }
                }
            }
        }
        //echo "archivos encontrados $countArchivos<br>";

    }





    public function __deconstruct()
    {
        if ($this->connectionId) {
            ftp_close($this->connectionId);
        }
    }
}
