<?php

namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 *  class DeliveryPoint
 *  Внешний класс модели, представляющий точку доставки на карте.
 */
class DeliveryPointEx extends BaseModelEx
{
    /** @var string Индекс по списку */
    public $index = '';

    /** @var string Широта */
    public $latitude = '';

    /** @var string Долгота */
    public $longitude = '';

    /** @var string Текст адреса */
    public $label = '';

    /** @var string Адрес */
    public $address = '';
}