<?php
namespace API\v1\Service;

use Exception;

/**
 * Класс для генерации исключений
 */
class ErrorHandler extends \Exception{
    
    /**
     * @var string 
     */
    const INVALID_PARAM_TYPE = 101;

    /**
     * @var array Массив с сообщениями
     */
    private $arMessage = [
        self::INVALID_PARAM_TYPE => 'Получен неверный тип параметра',
    ];

    /**
     * Конструктор класса
     * @param string $message       Текстовое сообщение
     * @param int $code             Код ошибки
     * @param array $arg            Аргументы
     * @param \Throwable $previous  Предыдущая ошибка
     */
    public function __construct(string $message = '', int $code = 0, array $arg, \Throwable $previous = null)
    {
        $message = $this->GenerateMessage($code,$arg);

        parent::__construct($message,$code,$previous);
    }

    
    private function GenerateMessage(int $code, array $arg): string{
        
        $message = '';

        switch($code){
            case self::INVALID_PARAM_TYPE:
                $message = $this->arMessage[self::INVALID_PARAM_TYPE] . $this->ParseArg($arg) . ' is given.';
                break;
            default:     
        }

        return $message;
    }

    /**
     * Парсит массив аргументов определяя тип данных
     * 
     * @param array $arg Аргументы
     * @return string Строка с типами данных аргументов
     */
    private function ParseArg(array $arg): string{
        $argLenght = count($arg);
        
        if($argLenght > 1){
            $message = [];

            foreach($arg as $elem){
                $message[] = get_debug_type($elem);
            }

            return 'Arg[' . implode(',',$message) . ']';
        }
        else
        {
            return get_debug_type($arg[0]);
        }

        return 'Arg[] is empty';
    }
}