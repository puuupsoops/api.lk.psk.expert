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
	public $code;

	/**
	 * Конвертация класса в JSON
	 *
	 * @return false|string
	 */
	public final function AsJSON()
	{
		return json_encode(get_object_vars($this));
	}
}