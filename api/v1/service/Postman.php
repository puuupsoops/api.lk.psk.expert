<?php
namespace API\v1\Service;

include_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

/**
 * Class Postman
 * Класс для работы с почтовыми сообщениями.
 *
 */
class Postman
{
    /** @var  \PHPMailer\PHPMailer\PHPMailer */
    private $PHPMailer;
    /** @var string Почтовый хост */
    private $host;
    /** @var int Порт */
    private $port;
    /** @var string Пользователь */
    private $username;
    /** @var string Пароль */
    private $password;

    /**
     * Конструктор класса
     * @param string|null $host     Хост
     * @param int|null $port        Порт
     * @param string|null $username Имя пользователя
     * @param string|null $password Пароль
     */
    public function __construct(
        string $host = 'smtp.spaceweb.ru',
        int $port = 25,
        string $username = 'lk.psk@devoops2.online',
        string $password = '970aP6DUnN4Y'
    )
    {
        $this->PHPMailer = new \PHPMailer\PHPMailer\PHPMailer();

        $this->host     = $host;
        $this->port     = $port;
        $this->username = $username;
        $this->password = $password;

    }

    /**
     * Отправить почтовое сообщение
     * @param string $subject Тема письма
     * @param string $message Текст сообщения
     * @param array | null $args Массив дополнительных почтовых адресов. (почта менеджера, пользователя и т.д.)
     *
     * @throws \PHPMailer\PHPMailer\Exception
     * @return boolean
     */
    public function SendMessage(string $subject, string $message, array $args = null): bool{

        //кодировка сообщения
        $this->PHPMailer->CharSet = 'UTF-8';

        // Настройки SMTP
        $this->PHPMailer->isSMTP();
        $this->PHPMailer->SMTPAuth = true;
        $this->PHPMailer->SMTPDebug = 0;

        $this->PHPMailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $this->PHPMailer->Host      = $this->host;
        $this->PHPMailer->Port      = $this->port;
        $this->PHPMailer->Username  = $this->username;
        $this->PHPMailer->Password  = $this->password;

        // От кого
        $this->PHPMailer->setFrom('lk.psk@devoops2.online', 'LK PSK');

        // Кому
        $this->PHPMailer->addAddress('lk.psk@devoops2.online', 'LK PSK');

        //дополнительные адреса, если есть
        if($args){
            foreach ($args as $email){
                if($email && is_string($email) ){
                    $this->PHPMailer->addAddress($email);
                }
            }
        }

        // Тема письма
        $this->PHPMailer->Subject = $subject;

        // Тело письма
        //$body = '<p><strong>«Hello, world!» </strong></p>';
        $this->PHPMailer->msgHTML($message);

        // Приложение
        //$PHPMailer->addAttachment(__DIR__ . '/image.jpg');

        // отправить
        return $this->PHPMailer->send();
    }
}