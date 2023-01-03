<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Base.php';


class Manager extends \API\v1\Models\Base
{
    public string $name = '';
    public string $email = '';
    public string $contact = '';
    public string $phone1 = '';
    public string $phone2 = '';
    public string $image = '';
    public array $header = [];

    public function __construct()
    {

    }

    public function GetEmail(): string {
        return $this->email;
    }

}