<?php
//@error_reporting(0);
define ('JIRAFEAU_ROOT', dirname ('../index.php') . '/');

require (JIRAFEAU_ROOT . 'lib/config.original.php');
require (JIRAFEAU_ROOT . 'lib/config.local.php');
require (JIRAFEAU_ROOT . 'lib/settings.php');
require (JIRAFEAU_ROOT . 'lib/functions.php');
require (JIRAFEAU_ROOT . 'lib/lang.php');
require (JIRAFEAU_ROOT . 'lib/vendor/phpmailer/Exception.php');
require (JIRAFEAU_ROOT . 'lib/vendor/phpmailer/PHPMailer.php');
require (JIRAFEAU_ROOT . 'lib/vendor/phpmailer/SMTP.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
/*
*  * PREVENT Abuse
*   */
if (!isset($_POST) || count($_POST) == 0) {
	        die("No POST data found...");
}

$friend_Emails = $_POST['destinos'];
//$toString = implode(',', $friend_Emails);
$toString = str_replace(" ", ",", $friend_Emails);
$toString = preg_replace('/[,]+/i', ",", $toString);
$toString = preg_replace('/\s+$/i', "", $toString);
$toString = preg_replace('/^\s+/i', "", $toString);
$toString_array = explode(",", $toString);
$toString_email = preg_replace('/[,]+/i', "<br/>", $toString);

$you = $_POST['nombre'];
$your_Email = $_POST['email'];
$mensaje = preg_replace('/<[^>]+>/i', "", $_POST['mensaje']);
$mensaje = str_replace("\r\n", "<br/>", $mensaje);
$mensaje = str_replace("\n", "<br/>", $mensaje);
$mensaje = str_replace("\r", "<br/>", $mensaje);

$link  = $_POST['enlace'];
$delete_code  = $_POST['codigo_borra'];
$date  = str_replace(" ", " a las ", $_POST['fecha']);


$link_down =  $cfg['web_root'].'f.php?h='.$link;
if (isset($_POST['encriptacion'])) {
    $link_down =  $link_down . '&k=' . $_POST['encriptacion'];
}
$link_down = file_get_contents('https://rgld.eu/yourls-api.php?signature=4bb6436370&action=shorturl&format=simple&url=' . urlencode($link_down));
$link_delete =  $cfg['web_root'].'f.php?h='.$link.'&d='.$delete_code;
$link_delete = file_get_contents('https://rgld.eu/yourls-api.php?signature=4bb6436370&action=shorturl&format=simple&url=' . urlencode($link_delete));


$file_list = '';
$enlaces = file(VAR_GROUPS . $link, FILE_IGNORE_NEW_LINES);
foreach ($enlaces as $enla) {
    $pe = jirafeau_get_link($enla);
    $file_list .= '- ' . $pe['file_name'] . '#%#%#%';
}
$file_list = str_replace("#%#%#%", "<br/>", $file_list);
	
$dest_email_html_orig = file_get_contents(JIRAFEAU_ROOT . 'lib/template/email.html');
$sender_email_html_orig = file_get_contents(JIRAFEAU_ROOT . 'lib/template/emailsender.html');
	
    $dest_email_html_orig = str_replace("###SENDER###", $your_Email, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###SENDERNAME###", $you, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###ENLACE###", $link_down, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###ENLACEBORRAR###", $link_delete, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###MENSAJE###", $mensaje, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###DESTINATARIOS###", $toString_email, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###ARCHIVOS###", $file_list, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###FECHA###", $date, $dest_email_html_orig);
    $dest_email_html_orig = str_replace("###JIRAFEAU_URL###", $cfg['web_root'], $dest_email_html_orig);


    $sender_email_html_orig = str_replace("###SENDER###", $your_Email, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###SENDERNAME###", $you, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###ENLACE###", $link_down, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###ENLACEBORRAR###", $link_delete, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###MENSAJE###", $mensaje, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###DESTINATARIOS###", $toString_email, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###ARCHIVOS###", $file_list, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###FECHA###", $date, $sender_email_html_orig);
    $sender_email_html_orig = str_replace("###JIRAFEAU_URL###", $cfg['web_root'], $sender_email_html_orig);


$dest_email_html = wordwrap($dest_email_html_orig, 70);
$sender_email_html = wordwrap($sender_email_html_orig, 70);

/************************** SENDER MAIL ******************/
$subject = '[RG File Sharing] - Archivos enviados a '. count($toString_array) . ' contacto(s)';	
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: '.$cfg['sender_name'].'<'.$cfg['sender_email'].'>' . "\r\n";
if (isset($cfg['smtp_host']) && !empty($cfg['smtp_host'])) {
    $mail = new PHPMailer(true);
    $mail->IsSMTP();
    $mail->CharSet    = 'UTF-8';
    $mail->Host       = $cfg['smtp_host'];
    if (isset($cfg['smtp_auth']) && $cfg['smtp_auth'] !== false) {
        $mail->SMTPAuth   = true;
        $mail->Port       = $cfg['smtp_port'];
        $mail->Username   = $cfg['smtp_user'];
        $mail->Password   = $cfg['smtp_pass'];
    }
    if (isset($cfg['smtp_tls']) && $cfg['smtp_tls'] !== false) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
    }

    $mail->setFrom($cfg['sender_email'], $cfg['sender_name']);
    $mail->addReplyTo($cfg['sender_email']);
    $mail->addAddress($your_Email);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $sender_email_html;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if(!$mail->send()) {
        $results = false;
    } else {
        $results = true;
    }
}

/************************** DEST MAIL ******************/
$subject = '[RG File Sharing] - Nuevos archivos recibidos';	
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= 'From: '.$cfg['sender_name'].'<'.$cfg['sender_email'].'>' . "\r\n";
if (isset($cfg['smtp_host']) && !empty($cfg['smtp_host'])) {
    $dmail = new PHPMailer(true);
    $dmail->IsSMTP();
    $dmail->CharSet    = 'UTF-8';
    $dmail->Host       = $cfg['smtp_host'];
    if (isset($cfg['smtp_auth']) && $cfg['smtp_auth'] !== false) {
        $dmail->SMTPAuth   = true;
        $dmail->Port       = $cfg['smtp_port'];
        $dmail->Username   = $cfg['smtp_user'];
        $dmail->Password   = $cfg['smtp_pass'];
    }
    if (isset($cfg['smtp_tls']) && $cfg['smtp_tls'] !== false) {
        $dmail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  
    }

    $dmail->setFrom($cfg['sender_email'], $cfg['sender_name']);
    $dmail->addReplyTo($cfg['sender_email']);
    foreach($toString_array as $f_email){
        $dmail->addBCC($f_email);
    }
    $dmail->isHTML(true);
    $dmail->Subject = $subject;
    $dmail->Body    = $dest_email_html;
    //$dmail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    if(!$dmail->send()) {
        $results = false;
    } else {
        $results = true;
    }
}


if ($results){
    return "OK";
}else{
    return "Error";
}
		
?>		
