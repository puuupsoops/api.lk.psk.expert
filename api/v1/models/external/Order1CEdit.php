<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/Order1CAdd.php';

/**
 * Модель позиции заказа для обновлении в базе 1С.
 *
 * @package API\v1\Models
 */
class Order1CEdit extends \API\v1\Models\Order1CAdd
{
    /** @var string XML идентификатор заказа присвоенный базой 1С */
    public string $guid = '';

    /**
     * @param string $guid Идентификатор заказа присвоенный базой 1С
     *
     * @throws \Exception Пустой идентификатор.
     */
    public function __construct(string $guid)
    {
        parent::__construct();

        if(empty($guid) || $guid === '')
            throw new \Exception('XML идентификатор не может быть пустым.');

        $this->guid = $guid;
    }
}