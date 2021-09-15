<?php
namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/PartnerEx.php';

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
	private   $container;

    /**
	 * VerificationController constructor.
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
         * @var \API\v1\Managers\Partner 
         */
        $Partner = new \API\v1\Managers\Partner();

        try{
            $Result = $Partner->GetByGUID($guid);
        }catch(\API\v1\Service\ErrorHandler $e){

            $response->getBody()->write(json_encode($e->getMessage()));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        
        $response->getBody()->write(json_encode($Result->AsArray()));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
        
    }
}