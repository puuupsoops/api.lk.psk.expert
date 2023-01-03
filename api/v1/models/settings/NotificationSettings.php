<?php

namespace API\v1\Models;
include_once $_SERVER['DOCUMENT_ROOT'] . '/api/v1/models/Base.php';

/**
 * Модель полей уведомлений.
 */
class NotificationSettings extends \API\v1\Models\Base
{
    public bool $order_email_created = false;
    public bool $order_email_changed = false;
    public bool $order_email_states = false;

    public bool $order_lk_created = false;
    public bool $order_lk_changed = false;
    public bool $order_lk_states = false;


    /**
     * Установить значения
     *
     * @param array $data Массив с внешними значениями
     * @return $this
     */
    public function Set(array $data): \API\v1\Models\NotificationSettings {
        if(array_key_exists('order',$data)) {

            if(array_key_exists('email',$data['order'])) {
                foreach ($data['order']['email'] as $key => $value){
                    switch ($key) {
                        case 'created':
                            $this->order_email_created = $value;
                            break;
                        case 'changed':
                            $this->order_email_changed = $value;
                            break;
                        case 'states':
                            $this->order_email_states = $value;
                            break;
                    }
                }
            }

            if(array_key_exists('lk',$data['order'])) {
                foreach ($data['order']['lk'] as $key => $value){
                    switch ($key) {
                        case 'created':
                            $this->order_lk_created = $value;
                            break;
                        case 'changed':
                            $this->order_lk_changed = $value;
                            break;
                        case 'states':
                            $this->order_lk_states = $value;
                            break;
                    }
                }
            }

        }

        return $this;
    }

    /** @internal */
    public function AsArray(): array
    {
        return [
            'order' => [
                'email' => [
                    'created' => $this->order_email_created,
                    'changed' => $this->order_email_changed,
                    'states' => $this->order_email_states
                ],
                'lk' => [
                    'created' => $this->order_lk_created,
                    'changed' => $this->order_lk_changed,
                    'states' => $this->order_lk_states
                ]
            ]
        ];
    }
}