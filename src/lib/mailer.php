<?php
header('Content-Type: text/html; charset=UTF-8');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$smtpUsername = 'noreply@argin.info';
$smtpPassword = '9(bf~{&%x-XF';
$emailFrom = 'TT Coffee';

function send_mail_template($emailTo, $emailToName, $subject, $mail_template, $render_list)
{
    global $smtpUsername, $smtpPassword, $emailFrom;

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'mail.argin.info';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->CharSet = 'utf-8';
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->setFrom($smtpUsername, $emailFrom);

    $mail->addAddress($emailTo, $emailToName);
    $mail->Subject = $subject;

    $template = file_get_contents($mail_template);

    foreach ($render_list as $alt_liste) {
        $template = str_replace($alt_liste[0], $alt_liste[1], $template);
    }

    $mail->msgHTML($template);
    $mail->AltBody = 'HTML messaging not supported';
    if (!$mail->send()) {
        return false;
    } else {
        return true;
    }
}

function send_mail($emailTo, $emailToName, $subject, $content)
{
    global $smtpUsername, $smtpPassword, $emailFrom;

    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->SMTPDebug = 0;
    $mail->Host = 'mail.argin.info';
    $mail->Port = 465;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->SMTPAuth = true;
    $mail->CharSet = 'utf-8';
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->setFrom($smtpUsername, $emailFrom);

    $mail->addAddress($emailTo, $emailToName);
    $mail->Subject = $subject;

    $mail->msgHTML($content);
    $mail->AltBody = 'HTML messaging not supported';
    if (!$mail->send()) {
        return false;
    } else {
        return true;
    }
}


// Örnek mail gönderimi
// Html sayfalar eklenip render edilebilir şekilde hazırlandı

/*
$liste = array(
    array("{{isim}}", "Atakan Argın"),
    array("{{aktivasyon_linki}}", "https://google.com/"),
    array("{{destek_eposta}}", "argin.atakan@gmail.com"),
);


$isSent = send_mail("argin.atakan@gmail.com", "Atakan Argın", "TT Coffee Kayıt", "mail_template/registration.html", $liste);

if ($isSent) {
    echo "Success";
} else {
    echo "Nok";
}
*/