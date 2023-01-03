<?php
/** Скрипт для отправки почтовых сообщений из таблицы PostMessagesTable */
// 5 сообщений = 1 минута !

$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';

include_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/service/Postman.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/repositories/EmailMessageRepository.php';

//region Настройки журналирования
$Monolog = new \Monolog\Logger(mb_strtolower(basename(__FILE__, '.php')));

$logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/cron/postman/' . date(
        'Y/m/d'
    ) . '/' . mb_strtolower(basename(__FILE__, '.php')) . '.' . date('H') . '.log';

$Monolog->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(\Monolog\Logger::INFO));
$Monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
$Monolog->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
$Monolog->pushProcessor(new \Monolog\Processor\ProcessIdProcessor());
$Monolog->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::DEBUG));

$handler = new \Monolog\ErrorHandler($Monolog);
$handler->registerErrorHandler([], false);
$handler->registerExceptionHandler();
$handler->registerFatalHandler();

//endregion
$Monolog->info('Старт скрипта отправки почтовых сообщений: ' . __FILE__);


try{
    /** @var array $EmailMessages Список сообщений для отправки */
    $EmailMessages = \API\v1\Repositories\EmailMessageRepository::Get(5);
    $Postman = new \API\v1\Service\Postman();

    $Monolog->debug('Список сообщений для отправки',$EmailMessages);
    //YjJi - login64 b2b
    //UA== - pass64 P$k0600s
    foreach ($EmailMessages as $message) {
        //отправляем почтовое сообщение
        if(!$Postman->SendMessage($message->subject,$message->text,$message->address)) {
            $Monolog->error('Сообщение не отправлено',['message' => $message]);
            continue;
        }
        \API\v1\Repositories\EmailMessageRepository::Update((int)$message->id);
    }
}catch (\Exception $e){
    $Monolog->error('Ошибка при отправке сообщений',['error' => $e->getMessage(), 'code' => $e->getCode()]);
}

$Monolog->info('Завершение скрипта отправки почтовых сообщений: ' . __FILE__);
$Monolog->close();