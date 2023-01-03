<?php

namespace API\v1\middleware;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/managers/Partner.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

/**
 * Получаем расширенные данные о контрагенте
 */
class GetPartnerDdataMiddleware
{
    public function __invoke(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Server\RequestHandlerInterface $handler
    ): \Psr\Http\Message\ResponseInterface
    {
        // данные строкой
        $contents = $request->getBody()->getContents();

        /** @var array $arData Счёт массивом*/
        //$contents = str_replace('&quot;',' " ',$contents);
        $arData = json_decode($contents,true);

        if(array_key_exists('executorUID',$arData['offer']) &&
            $arData['offer']['executorUID'] !== '') {

            // подтягиваем данные
            try{
                $Partner = new \API\v1\Managers\Partner();
                $partner = $Partner->GetByGUID($arData['offer']['executorUID']);

                $inn = (int)$partner->AsArray()['inn'];

                if($inn) {
                    $Client = new \GuzzleHttp\Client();

                    $QueryResponse = $Client->post('https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',[
                        'timeout' => 3,
                        'headers' => [
                            'Authorization' => 'Token fa9cc892823cd6372cb25569b4902be99ce5bb6b'
                        ],
                        'json' => [
                            'query' => $inn
                        ]
                    ]);

                    $ddataContents = $QueryResponse->getBody()->getContents();
                    $requestOrganization = current(json_decode($ddataContents)->suggestions);

                    $org = [
                        'name' => $requestOrganization->value ?? '',
                        'inn' => (int)$requestOrganization->data->inn ?? $inn,
                        'kpp' => (int)$requestOrganization->data->kpp ?? 0,
                        'address' => $requestOrganization->data->address->unrestricted_value ?? '',
                    ];

                    $org['text'] = implode(',',$org);

                    $arData['offer']['executorData'] = $org;
                }
            }catch (\Exception $e) {

            }

        }

        return $handler->handle($request
            ->withAttribute('contents', $contents)
            ->withAttribute('arData',$arData)
        );
    }
}