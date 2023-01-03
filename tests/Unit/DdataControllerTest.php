<?php

namespace Unit;

use PHPUnit\Framework\TestCase;

class DdataControllerTest extends TestCase
{
    protected static $ResponseSuccessObject;
    protected static $ResponseFailureObject;
    protected static $ResponseNotFoundObject;

    /** @var \GuzzleHttp\Client */
    protected static $httpClient;

    public static function setUpBeforeClass(): void
    {
        self::$httpClient = new \GuzzleHttp\Client();

        self::$ResponseSuccessObject = (object)[
            'response' => [
                'name' => 'ООО ТД \"ФАВОРИТ\"',
                'inn' => 2311253520,
                'kpp' => 231101001,
                'address' => '350056, Краснодарский край, г Краснодар, Прикубанский округ, поселок Индустриальный, ул Лазурная, д 74, офис 3',
                'text' => 'ООО ТД \"ФАВОРИТ\",2311253520,231101001,350056, Краснодарский край, г Краснодар, Прикубанский округ, поселок Индустриальный, ул Лазурная, д 74, офис 3'
            ],
            'error' => null
        ];

        self::$ResponseFailureObject = (object)[
            'response' => [],
            'error' => [
                'code' => 400,
                'message' => 'Пустой ИНН.'
            ]
        ];

        self::$ResponseNotFoundObject = (object)[
            'response' => [],
            'error' => [
                'code' => 404,
                'message' => 'Не найдено'
            ]
        ];
    }

    protected function setUp(): void
    {

    }

    /**
     * Проверка соединения, <br>
     *  pass: если в ответе пресутствует ключ suggestions
     *
     * @test
     **/
    public function GetConnectionData() {
        $ch = curl_init();

        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Authorization: Token fa9cc892823cd6372cb25569b4902be99ce5bb6b','Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['query' => 2311253520])
        ]);

        //$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data = curl_exec($ch);
        //$curl_errno = curl_errno($ch);
        //$curl_error = curl_error($ch);

        //var_dump($curl_errno);
        //var_dump($curl_error);
        //var_dump($status);
        //var_dump($data);

        curl_close($ch);

        $contents = json_decode($data,true);
        $result = array_key_exists('suggestions',$contents);

        $this->assertEquals(true,$result);
    }

    /**
     * Успешное получение данных об организации по её ИНН <br>
     *  pass: если ИНН параметра и ответа идентичны
     *
     * @test
     * @dataProvider GetByInnSuccessProvider
     **/
    public function GetByInnSuccess($inn){
        $ch = curl_init();

        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Authorization: Token fa9cc892823cd6372cb25569b4902be99ce5bb6b','Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['query' => $inn])
        ]);

        $data = curl_exec($ch);
        curl_close($ch);

        $contents = json_decode($data);
        $org = current($contents->suggestions);
        $result = $org->data->inn;

        $this->assertEquals($inn,$result);
    }

    /**
     * Успешное получение данных об организации по её ИНН <br>
     *  pass: если ИНН параметра и ответа идентичны
     *
     * @test
     * @dataProvider GetByInnNotFoundProvider
     **/
    public function GetByInnNotFound($inn){
        $ch = curl_init();

        curl_setopt_array($ch,[
            CURLOPT_URL => 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Authorization: Token fa9cc892823cd6372cb25569b4902be99ce5bb6b','Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['query' => $inn])
        ]);

        $data = curl_exec($ch);
        curl_close($ch);

        $contents = json_decode($data);
        $org = current($contents->suggestions);

        try{
            if(!$org)
                throw new \Exception('Не найдено',404);
        }catch (\Exception $e){
            $this->assertEquals(404,$e->getCode());
        }

    }

    /**
     * @dataProvider GetByInnFailureProvider
     * @test
     **/
    public function GetByInnFailure($inn) {

        try{
            if(strlen( (string)$inn ) < 10)
                throw new \Exception('Пустой ИНН.',400);
        }catch (\Exception $e){
            $this->assertEquals(400,$e->getCode());
        }
    }

    /**
     * @dataProvider GetByInnFailureProvider
     * @test
     **/
    public function GetByInnLessTen($id) {
        $count = strlen( trim((string)$id) );
        $this->assertLessThan(10,$count);
    }

    /**
     * @dataProvider GetByInnSuccessProvider
     * @test
     **/
    public function GetByInnEqualsTen($id) {
        $count = strlen( trim((string)$id) );
        $this->assertEquals(10,$count);
    }

    /**
     * @dataProvider GetByInnNotFoundProvider
     * @test
     **/
    public function GetByInnGreaterTen($id) {
        $count = strlen( trim((string)$id) );
        $this->assertGreaterThan(10,$count);
    }

    public function GetByInnFailureProvider(): array{
        return [
            'nullNumber' => [null],
            'oneNumber' => [2],
            'twoNumber' => [23],
            'threeNumber' => [231],
            'fourNumber' => [2311],
            'fiveNumber' => [23112],
            'sixNumber' => [231125],
            'sevenNumber' => [2311253],
            'eightNumber' => [23112535],
            'nineNumber' => [231125352], // Failure
        ];
    }

    public function GetByInnSuccessProvider(): array{
        return [
            'correctTenNumber' => [2311253520], // Success
        ];
    }

    public function GetByInnNotFoundProvider(): array{
        return [
            'elevenExcessNumber' => [23112535201], // NotFound
        ];
    }

}