<?php
namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/PartnerEx.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';

use API\v1\Models\ErrorResponse;
use API\v1\Models\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class PartnerController
 *
 * @package API\v1\Controllers
 */
class PartnerController {
    /**
	 * @var ContainerInterface Container Interface
	 */
	protected $container;

    /**
	 * constructor.
	 *
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
    }

    /**
     * 
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
	public function GetByGUID(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        /**
         * @var string внешний XML идентификатор контрагента
         */
        $guid = $args['id'];

        /**
         * @var \API\v1\Managers\Partner Класс для взаимодействия с данными контрагентов
         */
        $Partner = new \API\v1\Managers\Partner();

        try{
            # Получить данные о контрагенте
            $Result = $Partner->GetByGUID($guid);
        }catch(\API\v1\Service\ErrorHandler $e){
            return \ErrorResponse($e,$response);
        }

        # Сформировать успешный ответ
        $Response = new Response();
        $Response->code = 200;
        $Response->data = $Result->AsArray();

        $response->getBody()->write($Response->AsJSON());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
        
    }
}