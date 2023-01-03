<?php

namespace API\v1\Models;

include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/external/BaseModelEx.php';

/**
 * Внешняя модель счёта
 *
 * @package API\v1\Models
 */
class OrderCheckEx extends \API\v1\Models\BaseModelEx
{
    /** @var int Идентификатор елемента в Битрикс */
    public int $id = 0;

    /** @var string XML идентификатор в 1С */
    public string $guid = '';

    /** @var int Код статуса */
    public int $status = 0;

    /** @var string Идентификатор организации */
    public string $organization_id = '';

    /** @var string Код документа в 1С */
    public string $n = '';

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->guid = $data['guid'];
        $this->status = $data['status'];
        $this->organization_id = $data['organization_id'];
        $this->n = $data['n'];
    }
}