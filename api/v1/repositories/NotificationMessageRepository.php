<?php

namespace API\v1\Repositories;

class NotificationMessageRepository
{
    /** @var \Psr\Container\ContainerInterface|null Контейнер */
    protected ?\Psr\Container\ContainerInterface $container;

    /** @var \PDO|null Handler базы данных */
    protected ?\PDO $dbh;

    /**
     * Установливает контейнер
     *
     * @param \Psr\Container\ContainerInterface $container
     * @return $this
     */
    public function addContainer(\Psr\Container\ContainerInterface $container): self {
        $this->container = $container;
        return $this;
    }

    /**
     * Устанавливает Handler базы данных
     *
     * @param \PDO $dbh
     * @return $this
     */
    public function addDBHandler(\PDO $dbh) : self {
        $this->dbh = $dbh;
        return $this;
    }

    /**
     * Добавить запись
     *
     * @param int $userId Идентификатор пользователя в Битрикс
     * @param string $text Текст сообщения
     * @param string|null $date Дата в формате d.m.Y H:m:s
     *
     * @return int Идентификатор созданной записи или 0 если ошибка
     */
    public function add(int $userId, string $text, string $date = null): int {
        $sql = sprintf(
            "INSERT INTO messages (USER_ID,CREATE_DATE,MESSAGE,SEND_DATE,IS_SEND,RECEIVED_DATE,IS_RECEIVED) VALUES (%d,'%s','%s','%s',%d,'%s',%d)",
            $userId,
            $date ?? date('d.m.Y H:m:s'),
            $text,
            '00.00.0000 00:00:00',
            0,
            '00.00.0000 00:00:00',
            0
        );

        return (int)$this->dbh->exec($sql);
    }
}