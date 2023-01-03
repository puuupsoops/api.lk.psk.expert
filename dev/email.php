<?php
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';
include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

$PHPMailer = new \PHPMailer\PHPMailer\PHPMailer();
$PHPMailer->isSMTP();
$PHPMailer->Host     = '10.68.5.235';//'10.68.5.235' 'smtp.spaceweb.ru';
$PHPMailer->Port     = 25;
$PHPMailer->Username = 'b2b';//'b2b' 'lk.psk@devoops2.online';
$PHPMailer->Password = 'P$k0600s';//'P$k0600s' '970aP6DUnN4Y';

$PHPMailer->CharSet = 'UTF-8'; //кодировка сообщения
$PHPMailer->SMTPAutoTLS = false;
$PHPMailer->SMTPAuth = true;
//$PHPMailer->host     = 'localhost';
$PHPMailer->SMTPDebug = 0;

$PHPMailer->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

try{
    $PHPMailer->setFrom('mail.psk.expert', 'LK PSK'); // От кого 'mail.psk.expert' 'lk.psk@devoops2.online'
    $PHPMailer->addAddress('45201a@gmail.com', 'LK PSK'); // Кому

    $PHPMailer->Subject = '123'; // Тема письма
    $PHPMailer->msgHTML('321'); // Тело письма
    $result = $PHPMailer->send();
    var_dump($result);
    var_dump($PHPMailer);
}catch(\Exception $e){
    var_dump($e->GetMessage());
    var_dump($e->GetCode());
}