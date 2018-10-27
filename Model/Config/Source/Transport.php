<?php

namespace Enqueue\Magento2\Model\Config\Source;

class Transport
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'rabbitmq_amqp', 'label' => __('RabbitMQ AMQP')],
            ['value' => 'amqp', 'label' => __('AMQP')],
            ['value' => 'rabbitmq_stomp', 'label' => __('RabbitMQ STOMP')],
            ['value' => 'stomp', 'label' => __('STOMP')],
            ['value' => 'fs', 'label' => __('Filesystem')],
            ['value' => 'sqs', 'label' => __('Amazon AWS SQS')],
            ['value' => 'redis', 'label' => __('Redis')],
            ['value' => 'dbal', 'label' => __('Doctrine DBAL')],
            ['value' => 'null', 'label' => __('Null transport')],
        ];
    }
}
