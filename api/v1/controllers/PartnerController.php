<?php
namespace API\v1\Controllers;
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Contract.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Document.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Partner.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Contract.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Document.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/PartnerEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageEx.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/StorageDocumentEx.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/ErrorResponse.php';

include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

\Bitrix\Main\Loader::includeModule('iblock');

use API\v1\Managers\Partner;
use API\v1\Models\ErrorResponse;
use API\v1\Models\Response;
use Firebase\JWT\JWT;
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
     * Временная заглушка для данных менеджера
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function Manager(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface{

        $responseData = [
                'image' => 'http://10.68.5.243/upload/main/476/476c8c674d0c302163f5d03734e538dd.png',
                'phone2' => '',
                'email' => 's.melentyev@psk.expert',
                'contact' => '84951033030',
                'phone1' => '495-103-3030 доб.490',
                'name' => 'Мелентьев Сергей Александрович'
        ];

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
     * Получить пул данных контрагентов для авторизированного пользователя.
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
        global $USER;

        $PartnerList = [];

        $header = $request->getHeader('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header[0] ?? '', $matches)) {
            $token = $matches[1] ?? '';
        }

        $arAlgs = ['HS256', 'HS512', 'HS384'];
        $tokenData = (array)JWT::decode($token ?? '', \Environment::JWT_PRIVATE_KEY, $arAlgs);


        $rsUser = $USER->GetByID($tokenData['id']);

        $userLink = $rsUser->Fetch()['UF_PARTNERS_LIST'];


        $sizes =  \CIBlockElement::GetProperty(
            4,
            $userLink,
            [],
            ['CODE' => 'PARTNERS']
        );

        $partnersID = [];
        while($size = $sizes->GetNext()){

            # получаем ID связанных записей контрагентов
            $partnersID[] = $size['VALUE'];
        }

        $Partner = new \API\v1\Managers\Partner();
        $Contract = new \API\v1\Managers\Contract();
        $Document = new \API\v1\Managers\Document();
/*
        foreach( $partnersID as $el){
            $p = $Partner->GetByBitrixID($el);
            $PartnerList[] = $p->AsArray()['uid'];
        }
*/
/*
        for($i = 0; $i < count($PartnerList); $i++){

            try{
                $partner = $Partner->GetByGUID($PartnerList[$i]);

            }catch (\Exception $e){
                continue;
            }

            try{
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
                    $storages[] = $currentStorage;
                }

                # данные
                $responseData[$i] = $partner->AsArray();
                $responseData[$i]['storages'] = $storages;

            }catch (\Exception $e){
                continue;
            }

        }

        # Ошибка
        if(empty($responseData)){
            $Response = new ErrorResponse();
            $Response->code = 404;
            $Response->message = 'Контракты не найдены';

            $response->getBody()->write($Response->AsJSON());

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($Response->code);
        }
*/
        $responseData = [];

        for($i = 0; $i < count($partnersID); $i++){
            $partner = $Partner->GetByBitrixID($partnersID[$i]);
            $responseData[$i] = $partner->AsArray();

            $arContracts = $Contract->GetAll($partner);

            foreach($arContracts as $contract){
                $storage = $contract->AsArray();
                $documents = [];
                try{
                    $arDocuments = [];

                    $arDocuments[] = $Document->GetBounds($contract);
                    foreach($arDocuments as $document){

                        foreach($document as $el){
                            // переводим объекты документов в массив
                            $documents[] = $el->AsArray();
                        }

                    }

                }catch(\Exception $e){
                    if($e->getCode() == 404)
                        $documents = [];
                }
                $storage['documents'] = $documents;

                $responseData[$i]['storages'][] = $storage;
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
     * Получить данные о контрагенте по уникальному идентификатору.
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