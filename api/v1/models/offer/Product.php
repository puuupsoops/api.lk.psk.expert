<?php

namespace API\v1\Models\Offer;

include_once $_SERVER["DOCUMENT_ROOT"] . '/api/v1/models/offer/Characteristic.php';

class Product
{
    protected string $article;
    protected string $name;
    protected string $guid;
    protected float $amount;
    private float $total;

        /** @var string[] Изображения в формате base64 */
    private array $images;


    /** @var \API\v1\Models\Offer\Characteristic[] Изображения в формате base64 */
    private array $characteristics;

    public function __construct(array $data, array $images = [])
    {
        $this->total = 0.0;

        foreach ($images as $source) {
            $this->images[] = $this->convertToBase64($source);
        }

        $this->article = $data['article'];
        $this->name = htmlspecialchars_decode($data['product']['NAME']);
        $this->guid = $data['guid'];
        $this->amount = (float)count($data['characteristics']);

        foreach ($data['characteristics'] as $characteristic) {
            $item = new \API\v1\Models\Offer\Characteristic($characteristic);
            $this->total += $item->GetTotal();
            $this->characteristics[] = $item;
        }

    }

    public function GetTotal(): float { return $this->total; }

    private function convertToBase64(string $url): string {
        $type = pathinfo($url,PATHINFO_EXTENSION);
        $source = file_get_contents($url);
        return 'data:image/' . $type . ';base64,' . base64_encode($source);
    }

    public function AsArray(): array {
        $arData = [
            'article'   => $this->article,
            'name'      => $this->name,
            'guid'      => $this->guid,
            'amount'    => $this->amount,
            'images'    => $this->images
        ];

        foreach ($this->characteristics as $characteristic){
            $arData['characteristics'][] = $characteristic->AsArray();
        }

        return $arData;
    }
}