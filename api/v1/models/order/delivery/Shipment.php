<?php

namespace API\v1\Models\Order\Delivery;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 * Модель отгрузки
 *
 * @package API\v1\Models
 */
class Shipment extends \API\v1\Models\BaseModelEx
{
    /** @var string  Название */
    protected string $title = '';

    /** @var string  Мнемонический код */
    protected string $label = '';

    /** @var int  Числовой индентификатор */
    protected int $code = 0;

    /**
     * @param array $data Данные
     */
    public function __construct(array $data)
    {
        $this->title = $data['title'] ?? '';
        $this->label = $data['label'] ?? '';
        $this->code  = $data['code'] ?? -1;
    }

    public function GetTitle(): string { return $this->title; }
    public function GetLabel(): string { return $this->label; }
    public function GetCode(): int { return $this->code; }
}