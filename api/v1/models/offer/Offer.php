<?php
namespace API\v1\Models\Offer;
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/offer/Product.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/offer/Additional.php';

class Offer
{
    /** @var string|mixed  Исполнитель */
    protected string $executor;

    /** @var string|mixed  XML Идентификатор исполнителя (пустая строка, если набран вручную)*/
    protected string $executorUID;

    /** @var string|mixed Заказчик */
    protected string $customer;

    /** @var string|mixed Номер коммерческого предложения */
    protected string $n;

    /** @var int|float|mixed Дата коммерческого предложения */
    protected int $date;

    /** @var string|mixed Комментарий */
    protected string $comment;

    /** @var string  Общая стоимость (с учетом доставки) */
    private string $sum;
    /** @var string Стоимость без учета доставки */
    private string $cost;
    /** @var string  */
    private string $textSum;

    /** @var float|int  */
    private float $amount;

    /** @var float  */
    private float $total;

    private bool $header = false;
    private string $headerLogo = '';
    private string $headerText = '';

    /** @var  \API\v1\Models\Offer\Additional Дополнительные условия */
    private \API\v1\Models\Offer\Additional $additionally;

    /** @var \API\v1\Models\Offer\Product[] */
    private array $products;

    private $httpClient;

    /** @var array|mixed Расширенные данные по Поставщику (исполнитель)
     * @see GetPartnerDdataMiddleware::class
     */
    private array $executorData = [];

    public function __construct(array $data)
    {
        $this->httpClient = new \GuzzleHttp\Client();
        $this->products = [];
        $this->total = 0.0;

        $this->additionally = new \API\v1\Models\Offer\Additional($data['additionally'] ?? []);

        $this->executor = $data['executor'] ?? '';
        $this->executorUID = $data['executorUID'] ?? '';
        $this->executorData = $data['executorData'] ?? [];

        $this->customer = $data['customer'] ?? '';
        $this->n = $data['n'] ?? (string)time();
        $this->date = $data['date'] ?? time() * 1000;
        $this->comment = $data['comment'];

        foreach ($data['position'] as $position) {
            if($position['guid'] !== '') {
                $response = $this->httpClient->get('https://psk.expert/test/product-page/ajax.php',[
                    'query' => [
                        'QUERY'     => $position['guid'],
                        'OPTION'    => 8
                    ],
                    'verify' => false
                ]);
                $contents = $response->getBody()->getContents();
                $urlImage = current(json_decode($contents,true)['IMAGES']);

            }

            $product = new \API\v1\Models\Offer\Product($position,[$urlImage]);
            $this->total += $product->GetTotal();
            $this->products[] = $product;
        }

        $this->amount = count($this->products);
        $this->cost = number_format($this->total,2, ',', ' ');

        // добавляем стоимость доставки в общую сумму
        if($this->additionally->IsDelivery())
            $this->total += $this->additionally->GetDeliveryCost();

        //region text format
        $str = $this->num2str($this->total);
        $this->textSum =  mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
        $this->sum = number_format($this->total,2, ',', ' ');
        //endregion text format

        $this->header = $data['header'] ?? false;
        $this->headerLogo = $data['headerLogo'] ?? '';
        $this->headerText = $data['headerText'] ?? '';
    }

    public function GetExecutor(): string { return $this->executor; }
    public function GetCustomer(): string { return $this->customer; }
    public function GetNumberDocument(): string { return $this->n; }
    public function GetDate(): string { return date('d.m.Y',$this->date/1000); }
    public function GetComment(): string { return $this->comment; }
    public function GetSum(): string { return $this->sum; }
    public function GetSumAsText(): string { return $this->textSum; }
    public function GetAmount(): string { return (string)$this->amount; }

    /**
     * Получить дополнительные условия
     * @return \API\v1\Models\Offer\Additional
     */
    public function GetAdditionally(): \API\v1\Models\Offer\Additional { return $this->additionally; }

    public function AsArray(){
        $arData = get_object_vars($this);
        unset($arData['httpClient']);
        unset($arData['products']);

        $arData['date'] = date('d.m.Y',$this->date/1000);

        $arData['additionally'] = $this->additionally->AsArray();

        foreach ($this->products as $product){
            $arData['products'][] = $product->AsArray();
        }

        return $arData;
    }

    private function num2str($inn, $stripkop=false) {
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
    private function morph($n, $f1, $f2, $f5) {
        $n = abs($n) % 100;
        $n1= $n % 10;
        if ($n>10 && $n<20) return $f5;
        if ($n1>1 && $n1<5) return $f2;
        if ($n1==1) return $f1;
        return $f5;
    }
}