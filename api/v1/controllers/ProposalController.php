<?php

namespace API\v1\Controllers;
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/Responses.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/responses/ErrorResponse.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/Environment.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/Token.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/offer/Offer.php';

use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;

class ProposalController
{
    /**
     * @var ContainerInterface Container Interface
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $Monolog;

    /**
     * constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

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
     *  Добавить логотип компании
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function AddLogo(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            /** @var \Slim\Psr7\UploadedFile[] $arUploadedFiles Массив с Файлами вложения, ключ 'files' или пустой массив */
            $arUploadedFiles = $request->getUploadedFiles();
            $this->Monolog->debug('file', [$arUploadedFiles]);

            /** @var array $arProps Для сохранения файлов */
            $arProps = [];

            //получить существующие файлы
            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_LOGOS']
            );

            while($element = $arElements->GetNext()){

                $arProps['COMPANY_LOGOS'][]= \CFile::MakeFileArray($element['VALUE'], '/proposal/logo');
            }

            /** @var \Slim\Psr7\UploadedFile $file */
            if( $file =  $arUploadedFiles['file']) {

                // $arUploadedFiles по ключу files - должен содержать массив с \Slim\Psr7\UploadedFile
                // {"files":[ {"Slim\\Psr7\\UploadedFile":[]}, {"Slim\\Psr7\\UploadedFile":[]} ] }
                    $fileId = \CFile::SaveFile(
                        [
                            'name'    => $file->getClientFilename(),
                            'size'    => $file->getSize(),
                            'type'    => $file->getClientMediaType(),
                            'content' => (string) $file->getStream()
                        ],
                        '/proposal/logo' // Путь к папке в которой хранятся файлы (относительно папки /upload).
                    );

                    if($fileId) {
                        $arProps['COMPANY_LOGOS'][] = \CFile::MakeFileArray($fileId, '/proposal/logo');
                    }
            }

            \CIBlockElement::SetPropertyValuesEx(
                $Token->GetConfig(),
                false,
                $arProps,
            );

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [
            'status' => 'success',
            'id' => (int) $fileId
        ];
        $Response->code = 201;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     *  Удалить логотип из списка по его идентификатору
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function DeleteLogoById(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $contents = $request->getBody()->getContents();
            $arData = json_decode($contents,true);
            $id = (int)$arData['id'];

            if(!$id)
                throw new \Exception('Пустой идентификатор файла.',400);

            /** @var array $arProps Для сохранения файлов */
            $arProps = [];

            //получить существующие файлы
            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_LOGOS']
            );

            while($element = $arElements->GetNext()) {
                if($id === (int)$element['VALUE']) {
                    \CFile::Delete($id);
                    continue;
                }

                $arProps['COMPANY_LOGOS'][]= \CFile::MakeFileArray($element['VALUE'], '/proposal/logo');
            }

            \CIBlockElement::SetPropertyValuesEx(
                $Token->GetConfig(),
                false,
                $arProps,
            );

            //получить существующие файлы
            $arFiles = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_LOGOS']
            );

            while($element = $arElements->GetNext()){
                $arData['id'] = $element['VALUE'];

                if($file = \CFile::GetPath($element['VALUE'])) {
                    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);

                    $arData['image'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }

                $arFiles[] = $arData;
            }

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [
            'status' => 'success',
            'data' => $arFiles
        ];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     *  Получить список логотипов компании
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function GetLogoList(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $arFiles = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_LOGOS']
            );

            while($element = $arElements->GetNext()){
                $arData['id'] = $element['VALUE'];

                if($file = \CFile::GetPath($element['VALUE'])) {
                    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);

                    $arData['image'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }

                $arFiles[] = $arData;
            }

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = $arFiles;
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     *  Добавит преамбулу (шапку коммерческого предложения)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function AddPreamble(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $contents = $request->getBody()->getContents();
            $arData = json_decode($contents,true);
            $text = htmlspecialchars($arData['text']);

            //получить существующие записи
            $arData = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_PREAMBLE_LIST']
            );

            while($element = $arElements->GetNext()){
                $arData[] = $element['~VALUE']['TEXT'];
            }

            $arData[] = $text;

            \CIBlockElement::SetPropertyValuesEx(
                $Token->GetConfig(),
                false,
                ['COMPANY_PREAMBLE_LIST' => $arData],
            );

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = ['status' => 'success'];
        $Response->code = 201;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     *  Получить список преамбул (шапки коммерческого предложения)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function GetPreambleList(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            //получить существующие записи
            $arData = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_PREAMBLE_LIST']
            );

            while($element = $arElements->GetNext()) {
                if($element['PROPERTY_VALUE_ID']) {
                    $arData[] = [
                        'id' => $element['PROPERTY_VALUE_ID'],
                        'text' => htmlspecialchars_decode($element['~VALUE']['TEXT'])
                    ];
                }
            }

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = $arData;
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     *  Удалить преамбулу по идентификатору (шапки коммерческого предложения)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function DeletePreambleById(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $contents = $request->getBody()->getContents();
            $arData = json_decode($contents,true);
            $id = (int)$arData['id'];

            if(!$id)
                throw new \Exception('Пустой идентификатор файла.',400);


            //получить существующие записи
            $arData = [];
            $newData = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_PREAMBLE_LIST']
            );

            while($element = $arElements->GetNext()) {
                if($id === (int)$element['PROPERTY_VALUE_ID']) {
                    continue;
                }

                $newData[(int)$element['PROPERTY_VALUE_ID']] = [
                    'VALUE' => [
                        'TEXT' => $element['~VALUE']['TEXT'],
                        'TYPE' => 'HTML'
                    ],
                    'DESCRIPTION' => ''
                ];
            }

            // обновляем оставшимися данными
            \CIBlockElement::SetPropertyValuesEx(
                $Token->GetConfig(),
                false,
                ['COMPANY_PREAMBLE_LIST' => $newData],
            );

            // todo: переделать повторная выборка
            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'COMPANY_PREAMBLE_LIST']
            );

            while($element = $arElements->GetNext()) {
                $arData[] = [
                    'id' => (int)$element['PROPERTY_VALUE_ID'],
                    'text' => htmlspecialchars_decode($element['~VALUE']['TEXT'])
                ];
            }

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [
            'status' => 'success',
            'data' => $arData
        ];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Добавить документ
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function Add(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try
        {
            //флаг перенаправления на тестовую 1С
            $redirect1CTestDB = !\Configuration::GetInstance()::IsProduction();

            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            // данные строкой
            $contents = $request->getAttribute('contents');

            /** @var array $arData Счёт массивом*/
            $arData = $request->getAttribute('arData');

            // данные строкой
            //$contents = $request->getBody()->getContents();

            //region сохраняем структуру

            \CIBlockElement::SetPropertyValuesEx(
                $Token->GetConfig(),
                false,
                ['PROPOSAL_LIST' => [$contents]],
            );

            //endregion

            /** @var array $arData Счёт массивом*/
            //$contents = str_replace('&quot;',' " ',$contents);
            //$arData = json_decode($contents,true);

            //region костыль
            $arData['offer']['additionally'] = $arData['additionally'];
            $arData['offer']['header'] = $arData['header'];

            //получаем в base64
            if(is_integer($arData['headerLogo'])) {
                if($file = \CFile::GetPath($arData['headerLogo'])) {
                    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);

                    $arData['offer']['headerLogo'] = 'data:image/' . $type . ';base64,' . base64_encode($data);
                }
            }else{
                $arData['offer']['headerLogo'] = $arData['headerLogo'];
            }

            $arData['offer']['headerText'] = $arData['headerText'];
            //endregion

            $offer = new \API\v1\Models\Offer\Offer($arData['offer']);

            $TwigLoader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/documents/proposal');
            $Twig = new \Twig_Environment($TwigLoader);
            $template = $Twig->loadTemplate('base.html');

            $html = $template->render($offer->AsArray());

            //var_dump($offer->AsArray());
            //die();

/*            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor(
                $_SERVER['DOCUMENT_ROOT'] . '/local/src/word_templates/proposal/new.docx');
            $templateProcessor->setValues([
                'executor' => $arData['offer']['executor'],
                'n' => $arData['offer']['n'],
                'date' => date('d.m.Y',$arData['offer']['date']) . ' г.'
            ]);
            $filepath = $_SERVER['DOCUMENT_ROOT'] . '/upload/new.docx';
            $templateProcessor->saveAs($filepath);*/

            if($arData['as'] === 'WORD') {

                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $section = $phpWord->addSection([
                    'orientation' => 'landscape'
                ]);
                $section->addText(sprintf('Коммерческое предложение № %s от %s г.',
                    $offer->GetNumberDocument(),
                    $offer->GetDate()
                ));
                $section->addText('');
                $section->addText('Исполнитель: ' . $offer->GetExecutor() );
                $section->addText('Заказчик: ' . $offer->GetExecutor() );

                // таблица
                $table = $section->addTable([
                    'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::START,
                    'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_AUTO
                ]);

                $row = $table->addRow([
                    'tblHeader' => true // повторять на каждой странице строку таблицы
                ]);
                $row->addCell()->addText('№',['bold' => true]);
                $row->addCell()->addText('Артикул',['bold' => true]);
                $row->addCell()->addText('Название / Описание',['bold' => true]);
                $row->addCell()->addText('Фото',['bold' => true]);
                $row->addCell()->addText('Характеристика',['bold' => true]);
                $row->addCell()->addText('Цена',['bold' => true]);
                $row->addCell()->addText('Кол-во',['bold' => true]);
                $row->addCell()->addText('Сумма',['bold' => true]);

                foreach($offer->AsArray()['products'] as $key => $value){
                    $row = $table->addRow();
                    $row->addCell()->addText((string)($key + 1));
                    $row->addCell()->addText(htmlspecialchars($value['article']));
                    $row->addCell()->addText(htmlspecialchars($value['name']));
                    $row->addCell()->addText('');//фото
                    $characteristicsRow = $row->addCell();
                    $priceRow = $row->addCell();
                    $amountRow = $row->addCell();
                    $sumRow = $row->addCell();

                    foreach ($value['characteristics'] as $characteristics){
                        $characteristicsRow->addText($characteristics['title']);
                        $priceRow->addText($characteristics['price']);
                        $amountRow->addText($characteristics['amount']);
                        $sumRow->addText($characteristics['total']);
                    }
                }

                $section->addText(sprintf('Всего наименований: %s , на сумму: %s ₽',
                    $offer->GetAmount(),
                    $offer->GetSum()
                ));
                $section->addText($offer->GetSumAsText());
                $section->addText($offer->GetComment());

                //$phpWord = new \PhpOffice\PhpWord\PhpWord();
                //$section = $phpWord->addSection([
                //    'orientation' => 'landscape'
                //]);
                //\PhpOffice\PhpWord\Shared\Html::addHtml($section,$html,true,true);//$html

                //$phpWord = \PhpOffice\PhpWord\IOFactory::load($_SERVER['DOCUMENT_ROOT'] . '/local/src/twig_templates/documents/proposal/base.html', 'HTML');

                header('Access-Control-Allow-Origin: *');
                header('Content-Disposition: inline; filename="file.docx"');
                //header('Content-Type: application/octet-stream');
                header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');

                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord);
                $objWriter->save('php://output');

                return $response
                    ->withStatus(200);
/*                $response->getBody()->write(file_get_contents($filepath));
                return $response
                    ->withHeader('Content-Length', filesize($filepath))
                    ->withHeader('Content-Disposition','inline; filename="file.docx"')
                    ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
                    ->withStatus(200);*/

            }else{

                $dompdf = new \Dompdf\Dompdf();
                $dompdf->setBasePath($_SERVER['DOCUMENT_ROOT'] . '/upload/');

                //$phpWord = \PhpOffice\PhpWord\IOFactory::load($filepath);
                //$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
                //$objWriter->save($_SERVER['DOCUMENT_ROOT'] . '/upload/new.html');

                //file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/upload/new.html')
                $dompdf->loadHtml($html,'UTF8');
                $dompdf->setPaper('A4','landscape');
                $dompdf->setOptions(new \Dompdf\Options(['defaultFont' => 'dejavu sans']));

                //$dompdf->stream($_SERVER['DOCUMENT_ROOT'] . '/upload/document.pdf',['compress' => 0, 'attachment' => 0]);
                header('Access-Control-Allow-Origin: *');
                $dompdf->render();
                $dompdf->stream('document.pdf',['Attachment' => 1]); // attachment - 0 - вывод, 1 - скачивание
                /*
                return $response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withStatus(200);
                */
                //
                //$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord,'PDF');
                //$data = stream_get_contents($objWriter->save('php://output'));
                //var_dump($data);
                //$phpWord->save($_SERVER['DOCUMENT_ROOT'] . '/upload/new','PDF');

                //$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord,'PDF');
                //$objWriter->save($_SERVER['DOCUMENT_ROOT'] . '/upload/new.pdf');

                return $response
                    ->withHeader('Content-Type', 'application/pdf')
                    ->withStatus(200);
            }

        }
        catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [$arData];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Получить сохраненные коммерческие предложения
     *
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function GetProposalList(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));

            $arData = [];

            $arElements = \CIBlockElement::GetProperty(
                \Environment::GetInstance()['iblocks']['Users'],
                $Token->GetConfig(),
                [],
                ['CODE' => 'PROPOSAL_LIST']
            );

            while($element = $arElements->GetNext()) {
                if($element['~PROPERTY_VALUE_ID']) {
                    $arData[] = [
                        'id' => $element['~PROPERTY_VALUE_ID'],
                        'data' => json_decode($element['~VALUE'])
                    ];
                }
            }

        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = $arData;
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Получить структуру коммерческого предложения по идентификатору
     *
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function GetProposalById(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));
        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    /**
     * Удалить позицию структуры коммерческого предложения по идентификатору
     *
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param array $args
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function DeleteProposalById(
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        array $args
    ): \Psr\Http\Message\ResponseInterface{
        try{
            /** @var \API\v1\Models\Token Модель данных из токена авторизации */
            $Token = new \API\v1\Models\Token($request->getAttribute('tokenData'));
        } catch (\Exception $e) {
            return ErrorResponse($e, $response);
        }

        $Response = new \API\v1\Models\Response();
        $Response->data = [];
        $Response->code = 200;

        $response->getBody()->write($Response->AsJSON());
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($Response->code);
    }

    function number2string($number) {

        // обозначаем словарь в виде статической переменной функции, чтобы
        // при повторном использовании функции его не определять заново
        static $dic = array(

            // словарь необходимых чисел
            array(
                -2	=> 'две',
                -1	=> 'одна',
                1	=> 'один',
                2	=> 'два',
                3	=> 'три',
                4	=> 'четыре',
                5	=> 'пять',
                6	=> 'шесть',
                7	=> 'семь',
                8	=> 'восемь',
                9	=> 'девять',
                10	=> 'десять',
                11	=> 'одиннадцать',
                12	=> 'двенадцать',
                13	=> 'тринадцать',
                14	=> 'четырнадцать' ,
                15	=> 'пятнадцать',
                16	=> 'шестнадцать',
                17	=> 'семнадцать',
                18	=> 'восемнадцать',
                19	=> 'девятнадцать',
                20	=> 'двадцать',
                30	=> 'тридцать',
                40	=> 'сорок',
                50	=> 'пятьдесят',
                60	=> 'шестьдесят',
                70	=> 'семьдесят',
                80	=> 'восемьдесят',
                90	=> 'девяносто',
                100	=> 'сто',
                200	=> 'двести',
                300	=> 'триста',
                400	=> 'четыреста',
                500	=> 'пятьсот',
                600	=> 'шестьсот',
                700	=> 'семьсот',
                800	=> 'восемьсот',
                900	=> 'девятьсот'
            ),

            // словарь порядков со склонениями для плюрализации
            array(
                array('рубль', 'рубля', 'рублей'),
                array('тысяча', 'тысячи', 'тысяч'),
                array('миллион', 'миллиона', 'миллионов'),
                array('миллиард', 'миллиарда', 'миллиардов'),
                array('триллион', 'триллиона', 'триллионов'),
                array('квадриллион', 'квадриллиона', 'квадриллионов'),
                // квинтиллион, секстиллион и т.д.
            ),

            // карта плюрализации
            array(
                2, 0, 1, 1, 1, 2
            )
        );

        // обозначаем переменную в которую будем писать сгенерированный текст
        $string = array();

        // дополняем число нулями слева до количества цифр кратного трем,
        // например 1234, преобразуется в 001234
        $number = str_pad($number, ceil(strlen($number)/3)*3, 0, STR_PAD_LEFT);

        // разбиваем число на части из 3 цифр (порядки) и инвертируем порядок частей,
        // т.к. мы не знаем максимальный порядок числа и будем бежать снизу
        // единицы, тысячи, миллионы и т.д.
        $parts = array_reverse(str_split($number,3));

        // бежим по каждой части
        foreach($parts as $i=>$part) {

            // если часть не равна нулю, нам надо преобразовать ее в текст
            if($part>0) {

                // обозначаем переменную в которую будем писать составные числа для текущей части
                $digits = array();

                // если число треххзначное, запоминаем количество сотен
                if($part>99) {
                    $digits[] = floor($part/100)*100;
                }

                // если последние 2 цифры не равны нулю, продолжаем искать составные числа
                // (данный блок прокомментирую при необходимости)
                if($mod1=$part%100) {
                    $mod2 = $part%10;
                    $flag = $i==1 && $mod1!=11 && $mod1!=12 && $mod2<3 ? -1 : 1;
                    if($mod1<20 || !$mod2) {
                        $digits[] = $flag*$mod1;
                    } else {
                        $digits[] = floor($mod1/10)*10;
                        $digits[] = $flag*$mod2;
                    }
                }

                // берем последнее составное число, для плюрализации
                $last = abs(end($digits));

                // преобразуем все составные числа в слова
                foreach($digits as $j=>$digit) {
                    $digits[$j] = $dic[0][$digit];
                }

                // добавляем обозначение порядка или валюту
                $digits[] = $dic[1][$i][(($last%=100)>4 && $last<20) ? 2 : $dic[2][min($last%10,5)]];

                // объединяем составные числа в единый текст и добавляем в переменную, которую вернет функция
                array_unshift($string, join(' ', $digits));
            }
        }

        // преобразуем переменную в текст и возвращаем из функции, ура!
        return join(' ', $string);
    }

    function num2str($inn, $stripkop=false) {
        $nol = 'ноль';
        $str[100]= array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот', 'восемьсот','девятьсот');
        $str[11] = array('','десять','одиннадцать','двенадцать','тринадцать', 'четырнадцать','пятнадцать','шестнадцать','семнадцать', 'восемнадцать','девятнадцать','двадцать');
        $str[10] = array('','десять','двадцать','тридцать','сорок','пятьдесят', 'шестьдесят','семьдесят','восемьдесят','девяносто');
        $sex = array(
            array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),// m
            array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять') // f
        );
        $forms = array(
            array('копейка', 'копейки', 'копеек', 1), // 10^-2
            array('рубль', 'рубля', 'рублей',  0), // 10^ 0
            array('тысяча', 'тысячи', 'тысяч', 1), // 10^ 3
            array('миллион', 'миллиона', 'миллионов',  0), // 10^ 6
            array('миллиард', 'миллиарда', 'миллиардов',  0), // 10^ 9
            array('триллион', 'триллиона', 'триллионов',  0), // 10^12
        );
        $out = $tmp = array();
        // Поехали!
        $tmp = explode('.', str_replace(',','.', $inn));
        $rub = number_format($tmp[ 0], 0,'','-');
        if ($rub== 0) $out[] = $nol;
        // нормализация копеек
        $kop = isset($tmp[1]) ? substr(str_pad($tmp[1], 2, '0', STR_PAD_RIGHT), 0,2) : '00';
        $segments = explode('-', $rub);
        $offset = sizeof($segments);
        if ((int)$rub== 0) { // если 0 рублей
            $o[] = $nol;
            $o[] = $this->morph( 0, $forms[1][ 0],$forms[1][1],$forms[1][2]);
        }
        else {
            foreach ($segments as $k=>$lev) {
                $sexi= (int) $forms[$offset][3]; // определяем род
                $ri = (int) $lev; // текущий сегмент
                if ($ri== 0 && $offset>1) {// если сегмент==0 & не последний уровень(там Units)
                    $offset--;
                    continue;
                }
                // нормализация
                $ri = str_pad($ri, 3, '0', STR_PAD_LEFT);
                // получаем циферки для анализа
                $r1 = (int)substr($ri, 0,1); //первая цифра
                $r2 = (int)substr($ri,1,1); //вторая
                $r3 = (int)substr($ri,2,1); //третья
                $r22= (int)$r2.$r3; //вторая и третья
                // разгребаем порядки
                if ($ri>99) $o[] = $str[100][$r1]; // Сотни
                if ($r22>20) {// >20
                    $o[] = $str[10][$r2];
                    $o[] = $sex[ $sexi ][$r3];
                }
                else { // <=20
                    if ($r22>9) $o[] = $str[11][$r22-9]; // 10-20
                    elseif($r22> 0) $o[] = $sex[ $sexi ][$r3]; // 1-9
                }
                // Рубли
                $o[] = $this->morph($ri, $forms[$offset][ 0],$forms[$offset][1],$forms[$offset][2]);
                $offset--;
            }
        }
        // Копейки
        if (!$stripkop) {
            $o[] = $kop;
            $o[] = $this->morph($kop,$forms[ 0][ 0],$forms[ 0][1],$forms[ 0][2]);
        }
        return preg_replace("/\s{2,}/",' ',implode(' ',$o));
    }

    /**
     * Склоняем словоформу
     */
    function morph($n, $f1, $f2, $f5) {
        $n = abs($n) % 100;
        $n1= $n % 10;
        if ($n>10 && $n<20) return $f5;
        if ($n1>1 && $n1<5) return $f2;
        if ($n1==1) return $f1;
        return $f5;
    }
}