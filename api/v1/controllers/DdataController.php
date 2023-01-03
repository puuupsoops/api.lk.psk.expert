<?php

namespace API\v1\Controllers;

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/ErrorResponse.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/Token.php';

use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

/**
 * Контроллер запросов к внешнему серверу с данными организаций
 */
class DdataController
{
    /**
     * @var ContainerInterface Container Interface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $Monolog;

    /** @var \GuzzleHttp\Client */
    protected $Client;

    /**
     * constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->Client = new \GuzzleHttp\Client();

        //region Logger
        $this->Monolog = new Logger(mb_strtolower(basename(__FILE__,'.php')));

        $logFile  = $_SERVER['DOCUMENT_ROOT'] . '/logs/api/' . str_replace('\\', '/', __CLASS__) . '/' . date(
                'Y/m/d'
            ) . '/' . mb_strtolower(basename(__FILE__, '.php')) . '.' . date('H') . '.log';

        $this->Monolog->pushProcessor(new \Monolog\Processor\IntrospectionProcessor(Logger::INFO));
        $this->Monolog->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
        $this->Monolog->pushProcessor(new \Monolog\Processor\MemoryPeakUsageProcessor());
        $this->Monolog->pushProcessor(new \Monolog\Processor\ProcessIdProcessor());
        $this->Monolog->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

        $handler = new ErrorHandler($this->Monolog);
        $handler->registerErrorHandler([], false);
        $handler->registerExceptionHandler();
        $handler->registerFatalHandler();
        //endregion
    }

    /**
     * Получить данные об организации по ИНН
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function GetByInn(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface {

        //Slim\Psr7\Request
        //var_dump(get_class($request));

        //Slim\Psr7\Response
        //var_dump(get_class($response));
        try{

            if(strlen( trim($args['id']) ) < 10)
                throw new \Exception('Пустой ИНН.',400);

            $QueryResponse = $this->Client->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',[
                'headers' => [
                    'Authorization' => 'Token fa9cc892823cd6372cb25569b4902be99ce5bb6b'
                ],
                'json' => [
                    'query' => (int)$args['id']
                ]
            ]);

            if($QueryResponse->getStatusCode() === 401) {
                throw new \Exception('Сервис не доступен.',400);
            }

            $contents = $QueryResponse->getBody()->getContents();
            $requestOrganization = current(json_decode($contents)->suggestions);

            if(!$requestOrganization)
                throw new \Exception('Не найдено',404);

            $Response = new \API\v1\Models\Response();
            $Response->code = 200;
            $Response->data = [
                'name' => $requestOrganization->value ?? '',
                'inn' => (int)$requestOrganization->data->inn ?? (int)$args['id'],
                'kpp' => (int)$requestOrganization->data->kpp ?? 0,
                'address' => $requestOrganization->data->address->unrestricted_value ?? '',
            ];

            $Response->data['text'] = implode(',',$Response->data);


        }catch (\Exception $e){
            return ErrorResponse($e, $response);
        }

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }
}