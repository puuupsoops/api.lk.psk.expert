<?php
namespace API\v1\Models;

/**
 * Абстрактный класс для ответов
 */
abstract class BaseResponse
{
	/**
	 * @var int Код ошибки
	 */
	public int $code;

    /**
     * @var string Текст ошибки
     */
    public string $message;

	/**
	 * Конвертация класса в JSON
	 *
	 * @return false|string
	 */
	public function AsJSON()
	{
		return json_encode(get_object_vars($this));
	}
}