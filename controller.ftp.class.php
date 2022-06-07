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
        $documentRoot = ""/* $_SERVER["DOCUMENT_ROOT"] . "/" . $res[3] */;
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
                    /* do {
                        $buscador = $dirRemote . $carpeta . "/";
                        if ($this->comprobarDir($buscador) == true) {
                            if (explodeEnd(".", $buscador) != "/") {
                                $this->arrdir2($buscador, $createDir, $data);
                            }
                        }
                    } while ($this->comprobarDir($buscador) == false); */
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
        return $data;
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


    function getDirRemoto($dirRemote, $localDirect)
    {

        $this->connect(FTP_HOST, FTP_USER, FTP_PASS);

        $expS = str_replace(" ", "", "\ ");
        $res = explode($expS, __FILE__);
        $documentRoot = ""/* $_SERVER["DOCUMENT_ROOT"] . "/" . $res[3] */;
        $localDir = $documentRoot . $localDirect;
        $listaDir = $this->getListaDir($dirRemote);
        $countArchivos = 0;
        $file = $listaDir;
        echo "
        <div class='card' style='background:#fff; color: #000; text-align: center; padding:15px; border: 3px solid none;'>
        <div class='card-body text-center'>
        <label><b>Directorio Local: </b> $localDir/ </label><br>
        <label><b>Directorio Remoto: </b> $dirRemote </label><br>
        <label>⚫ Se encuentra en tu directorio local </label><br>
        <label>🔴 No encuentra en tu directorio local </label><br>
        <br>
        
        <button style='padding: 15px; border-radius: 20px; background: #1B9FD4; color: white;' class='contentoutofsync'>Local</button> 
        <button style='padding: 15px; border-radius: 20px; background: #1B9FD4; color: white;' class='contentoutofsync'>Nube</button>
          </div>
      </div> 
       
         <div style='background:#fff; color: #000; margin:auto; padding:15px;>
        <b class='text-left' style='color:black; margin:left; float: left;'>📁 $dirRemote </b> <br>";
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
        echo "<b>archivos encontrados</b> $countArchivos<br>";
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
        /*         echo "archivos encontrados $countArchivos<br>";
 */
    }


    /* 
****************************************************************************************************************
        
        BARRA DE PROGRESO DE LA ACTUALIZACION
        SE PUEDE PONER EN LA FUNCION O SI SON VARIAS FUNCIONES PONER EN LA VISTA

        !!!!! OJO ¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡¡ Si no se pone no jala para que jale se necesitan las librerias de bootstrap 4.0 o mayor y jquery 3.0 o mayor 

        <div class="progress" style="width: 500px; margin:auto; height:30px; border-radius:20px; border: 1px solid #000; display: none;">
        <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="progress">
        </div>
        </div>

****************************************************************************************************************
*/
    public function syncArchivos($localDir, $dirRemote)
    {
        $listaDir = $this->getListaDir($dirRemote);
        $arrayExtenciones = array('txt', 'webp', 'db', 'ico', 'csv', 'png', 'jpg', 'pdf', 'config', 'shtml', 'php', 'html', 'jpeg', 'svg', 'DS_Store', 'gif', 'old', 'mp4');
        $countArchivos = 0;
        $countArchivosLoad = 0;
        $countArchivosNoLoad = 0;
        $totalbytes = 0;
        $contNewFilesDownload = "";
        echo "
        <div class='card' style='background:#fff; color: #000; text-align: center; padding:15px; border: 3px solid none;'>
        <div class='card-body text-center'>
        <label><b>Directorio Local:</b> $localDir/ </label><br>
        <label><b>Directorio Remoto:</b> $dirRemote </label><br>
        <label>⚫ Se encuentra en tu directorio local </label><br>
        <label>🔴 No encuentra en tu directorio local </label><br>
        
        <a href='#' data-toggle='modal' data-target='#btn-sure' class='btn btn-warning contentoutofSyncall' name='syncallLocal' style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white;'>subir todo a local</a>
        <a href='#' data-toggle='modal' data-target='#btn-sure2' style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white;' class='btn btn-primary contentoutofsync'>subir archivos no cargados a local</a>
        </div> 
        </div>
        ";
?>
        <br>


        <div class="modal fade" id="btn-sure" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <label for=""><b style="color: red;">¡Cuidado!</b> Estas a punto de sobrescribir todos los datos que hay en tu directorio local, <b>¿Deseas continuar?</b></label><br>
                        <p>&nbsp;</p>
                        <a href='#' id='downloadAllFileContent' data-id='aaa' data-toggle='modal' class='btn btn-danger contentoutofSyncall' onclick="javascript:$('#btn-sure').modal('hide');$('.progress').show(); window.history.pushState('page2', 'Title', '?downloadAllFiles=1');//window.location.reload();" name='syncallLocal' style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white; margin-left:20px;'>Aceptar</a>
                        <a data-dismiss=" modal" aria-label="Close" style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white;' class='btn btn-primary'>Cancelar</a>
                        <p>&nbsp;</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="btn-sure2" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <label for=""><b style="color: red;">¡Cuidado!</b> Estas a punto de descargar archivos que no hay en tu directorio local, <b>¿Deseas continuar?</b></label>
                        <p>&nbsp;</p>
                        <a href='#' id='downloadFileContent' data-id='aaa' data-toggle='modal' class='btn btn-danger contentoutofSyncall' onclick="javascript:$('#btn-sure2').modal('hide');$('.progress').show(); window.history.pushState('page2', 'Title', '?downloadNewFiles=1');//window.location.reload();" name='syncallLocal' style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white; margin-left:20px;'>Aceptar</a>
                        <a data-dismiss=" modal" aria-label="Close" style='padding: 5px 30px; border: 1px solid #000; border-radius: 20px; color: white;' class='btn btn-primary'>Cancelar</a>
                        <p>&nbsp;</p>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <?php
        echo " 
            <div class='text-center' id='archivosOcultos' style='display: ;'><button name='btn_archivosHide'><b style='color:black; float:left;'>📁 $dirRemote </b>
            <br>
            ";

        $file = $listaDir;



        for ($x = 0; $x < count($file); $x++) {
            $fileTo = $this->explodeEnd("/", $file[$x]);
            $createDir = $localDir . "/" . $fileTo;

            $arrExtenciones = $arrayExtenciones;
            $ext = $this->explodeEnd(".", $file[$x]);


            if (!file_exists($localDir)) {
                //mkdir($localDir, 0777, true); 
            }

            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, "/" . $file[$x]);
                $totalbytes += $fsize;
                if (!file_exists($createDir)) {
                    $countArchivos++;
                    $countArchivosNoLoad++;
                    //$controllerFTPClient->descargarArchivo($file[$x],$createDir);

                    echo "<div id='dirArchivos' class='archivos text-left' style='display: ;'><b style='color:red;'>|__🧾" . $fileTo . "</b> $fsize bytes<br></div>";
                } else {
                    $countArchivos++;
                    $countArchivosLoad++;
                    echo "<div id='dirArchivos' class='archivos text-left' style='display: ;'><b  style='color:black;'>|__🧾" . $fileTo . " </b> $fsize bytes<br></div>";
                }
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        echo "<b style='color:red;'>📁 $fileTo </b><br>";
                        mkdir($createDir, 0777, true);
                    } else {
                        echo "<b style='color:black;'>📁 $fileTo </b><br> ";
                    }
                }
            }
        }


        if (isset($_GET['downloadAllFiles'])) {
            $this->downloadAllFiles($localDir, $dirRemote);
        }
        if (isset($_GET['downloadNewFiles'])) {
            $this->downloadNewFiles($localDir, $dirRemote);
        }
        echo "
        <br>";
        echo "
        <label> <b>Tamaño Total:</b> $totalbytes bytes</label><br>
        <label> <b>archivos encontrados:</b> $countArchivos</label><br>
        <label> <b>archivos no descargados:</b> $countArchivosNoLoad</label><br>
        <label> <b>archivos descargados:</b> $countArchivosLoad</label><br></div>";
        ?>


        <div id="divhide"></div>
        <script type="text/javascript">
            document.getElementById('downloadAllFileContent').addEventListener("click", function() {
                let i = setInterval(function() {
                    let curvalue = parseInt(document.getElementById('progress').getAttribute('aria-valuenow'));
                    var URLactual = window.location;
                    var objXMLHttpRequest = new XMLHttpRequest();
                    objXMLHttpRequest.onreadystatechange = function() {
                        if (objXMLHttpRequest.readyState === 4) {
                            if (objXMLHttpRequest.status === 200) {
                                //alert(objXMLHttpRequest.responseText);
                            } else {
                                //alert('Error Code: ' + objXMLHttpRequest.status);
                                //alert('Error Message: ' + objXMLHttpRequest.statusText);
                            }
                        }
                    }
                    objXMLHttpRequest.open('GET', URLactual);
                    objXMLHttpRequest.send();

                    if (curvalue < <?= 100 ?>) {
                        curvalue += <?= 10 ?>;
                        document.getElementById('progress').setAttribute('aria-valuenow', curvalue);
                        document.getElementById('progress').setAttribute('style', 'width: ' + curvalue + '%');
                    } else {
                        console.log(<?php echo $contNewFilesDownload; ?>);
                        Swal.fire({
                            title: 'Mensaje',
                            text: "Proceso Terminado",
                            icon: {
                                if (qr_reading_enable) {
                                    timerVideo = timerVideoOriginal;
                                    CART_VOUCHER_DATA = result;
                                    qr_reading_enable = false;
                                    ajaxProcess = undefined;
                                    keyCodeRepeat = 0;
                                    keyCode = "";
                                    backToCart();
                                }
                            },
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Aceptar!'
                        }).then((result) => {
                            if (result.value) {
                                location.href = '?';

                            }
                        })
                        clearInterval(i);
                    }
                }, 2000)
            });


            document.getElementById('downloadFileContent').addEventListener("click", function() {
                let i = setInterval(function() {
                    let curvalue = parseInt(document.getElementById('progress').getAttribute('aria-valuenow'));
                    var URLactual = window.location;
                    var objXMLHttpRequest = new XMLHttpRequest();
                    objXMLHttpRequest.onreadystatechange = function() {
                        if (objXMLHttpRequest.readyState === 4) {
                            if (objXMLHttpRequest.status === 200) {
                                //alert(objXMLHttpRequest.responseText);
                            } else {
                                //alert('Error Code: ' + objXMLHttpRequest.status);
                                //alert('Error Message: ' + objXMLHttpRequest.statusText);
                            }
                        }
                    }
                    objXMLHttpRequest.open('GET', URLactual);
                    objXMLHttpRequest.send();

                    if (curvalue < <?= 100 ?>) {
                        curvalue += <?= 10 ?>;
                        document.getElementById('progress').setAttribute('aria-valuenow', curvalue);
                        document.getElementById('progress').setAttribute('style', 'width: ' + curvalue + '%');
                    } else {
                        console.log(<?php echo $contNewFilesDownload; ?>);
                        Swal.fire({
                            title: 'Mensaje',
                            text: "Proceso Terminado",
                            icon: {
                                if (qr_reading_enable) {
                                    timerVideo = timerVideoOriginal;
                                    CART_VOUCHER_DATA = result;
                                    qr_reading_enable = false;
                                    ajaxProcess = undefined;
                                    keyCodeRepeat = 0;
                                    keyCode = "";
                                    backToCart();
                                }
                            },
                            showCancelButton: false,
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'Aceptar!'
                        }).then((result) => {
                            if (result.value) {
                                location.href = '?';

                            }
                        })
                        clearInterval(i);
                    }
                }, 1000)
            });
        </script>
    <?php

    }



    public function downloadNewFiles($localDir, $dirRemote)
    {
        $listaDir = $this->getListaDir($dirRemote);
        $arrayExtenciones = array('txt', 'webp', 'db', 'ico', 'csv', 'png', 'jpg', 'pdf', 'config', 'shtml', 'php', 'html', 'jpeg', 'svg', 'DS_Store', 'gif', 'old', 'mp4');
        $countArchivos = 0;
        $countArchivosLoad = 0;
        $countArchivosNoLoad = 0;
        $totalbytes = 0;

        $file = $listaDir;

        for ($x = 0; $x < count($file); $x++) {
            $fileTo = $this->explodeEnd("/", $file[$x]);
            $createDir = $localDir . "/" . $fileTo;
            $arrExtenciones = $arrayExtenciones;
            $ext = $this->explodeEnd(".", $file[$x]);

            if (!file_exists($localDir)) {
                mkdir($localDir, 0777, true);
            }

            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, "/" . $file[$x]);
                $totalbytes += $fsize;
                if (!file_exists($createDir)) {
                    $countArchivos++;
                    $this->descargarArchivo($file[$x], $createDir);
                }
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        mkdir($createDir, 0777, true);
                    }
                }
            }
        }
        echo $countArchivos + "tuptmmarch";
    }

    public function downloadAllFiles($localDir, $dirRemote)
    {
        $listaDir = $this->getListaDir($dirRemote);
        $arrayExtenciones = array('txt', 'webp', 'db', 'ico', 'csv', 'png', 'jpg', 'pdf', 'config', 'shtml', 'php', 'html', 'jpeg', 'svg', 'DS_Store', 'gif', 'old', 'mp4');
        $countArchivos = 0;
        $countArchivosLoad = 0;
        $countArchivosNoLoad = 0;
        $totalbytes = 0;

        $file = $listaDir;

        for ($x = 0; $x < count($file); $x++) {
            $fileTo = $this->explodeEnd("/", $file[$x]);
            $createDir = $localDir . "/" . $fileTo;
            $arrExtenciones = $arrayExtenciones;
            $ext = $this->explodeEnd(".", $file[$x]);

            if (!file_exists($localDir)) {
                mkdir($localDir, 0777, true);
            }

            if (in_array($ext, $arrExtenciones)) {
                $fsize = ftp_size($this->connectionId, "/" . $file[$x]);
                $totalbytes += $fsize;
                if (!file_exists($createDir)) {
                    $this->descargarArchivo($file[$x], $createDir);
                } else {
                    $this->descargarArchivo($file[$x], $createDir);
                }
            } else {
                if ($fileTo != ".." && $fileTo != "." && $fileTo != "" && $fileTo != "/") {
                    if (!file_exists($createDir)) {
                        mkdir($createDir, 0777, true);
                    } else {
                        mkdir($createDir, 0777, true);
                    }
                }
            }
        }
    }



    public function __deconstruct()
    {
        if ($this->connectionId) {
            ftp_close($this->connectionId);
        }
    }
}
