<?php
namespace API\v1\Controllers;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/PartnerEx.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';

use API\v1\Managers\Partner;
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
    public function Partners(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{
        $PartnerList = [
            'a7c28d6f-5deb-11ea-80e7-000c293f55f7',
            '8152948b-ace6-11de-a660-0050569a3a91',
            'f168528d-631b-11df-bfa2-0050569a3a91'
        ];

        $Partner = new \API\v1\Managers\Partner();

        $Contract = new \API\v1\Managers\Contract();

        $Document = new \API\v1\Managers\Document();

        $responseData = [];

        for($i = 0; $i < count($PartnerList); $i++){

            $partner = $Partner->GetByGUID($PartnerList[$i]);

            $responseData[$i] = $partner->AsArray();

            # array contracts
            $contract = $Contract->GetAll($partner);

            $storages = [];

            foreach($contract as $elem){
                $currentStorage = $elem->AsArray();

                # array documents
                $documents = $Document->GetBounds($elem);

                foreach ($documents as $doc){
                    $currentStorage['documents'][] = $doc->AsArray();
                }

                $responseData[$i]['storages'][] = $currentStorage;
            }
        }

        # Сформировать успешный ответ
        $Response = new Response();
        $Response->code = 200;
        $Response->data = $responseData;

        $response->getBody()->write($Response->AsJSON());

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
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
        }catch(\Exception $e){
            return ErrorResponse($e,$response);
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