<?php

namespace Enqueue\Magento2\Plugin\Model\Config;

use \Enqueue\AmqpExt\AmqpContext;
use \Enqueue\Stomp\StompContext;
use \Enqueue\Fs\FsContext;
use \Enqueue\Sqs\SqsContext;
use \Enqueue\Redis\RedisContext;
use \Enqueue\Dbal\DbalContext;
use \Magento\Framework\Exception\TemporaryState\CouldNotSaveException;

class ConfigPlugin
{
    /**
     * @var array
     */
    protected $_preDefinedServices = [
        'rabbitmq_amqp' => [
            'name' => 'RabbitMQ AMQP',
            'package' => 'enqueue/amqp-ext',
            'class' => AmqpContext::class,
        ],
        'amqp' => [
            'name' => 'AMQP',
            'package' => 'enqueue/amqp-ext',
            'class' => AmqpContext::class,
        ],
        'rabbitmq_stomp' => [
            'name' => 'RabbitMQ STOMP',
            'package' => 'enqueue/stomp',
            'class' => StompContext::class,
        ],
        'stomp' => [
            'name' => 'STOMP',
            'package' => 'enqueue/stomp',
            'class' => StompContext::class,
        ],
        'fs' => [
            'name' => 'Filesystem',
            'package' => 'enqueue/fs',
            'class' => FsContext::class,
        ],
        'sqs' => [
            'name' => 'Amazon AWS SQS',
            'package' => 'enqueue/sqs',
            'class' => SqsContext::class,
        ],
        'redis' => [
            'name' => 'Redis',
            'package' => 'enqueue/redis',
            'class' => RedisContext::class,
        ],
        'dbal' => [
            'name' => 'Doctrine DBAL',
            'package' => 'enqueue/dbal',
            'class' => DbalContext::class,
        ]
    ];

    /**
     * @param \Magento\Config\Model\Config $subject
     * @throws CouldNotSaveException
     */
    public function beforeSave(\Magento\Config\Model\Config $subject)
    {
        $beforeSaveData = $subject->getGroups();

        if (isset($beforeSaveData['transport']['fields']['default']['value'])) {
            $configValue = $beforeSaveData['transport']['fields']['default']['value'];

            if (false == isset($this->_preDefinedServices[$configValue])) {
                throw new \LogicException(sprintf('Unknown transport: "%s"', $configValue));
            }

            if (false == $this->isClassExists($this->_preDefinedServices[$configValue]['class'])) {
                throw new CouldNotSaveException(
                    __(
                        vsprintf(
                        '%s transport requires package "%s". Please install it via composer. #> php composer.php require %s',
                        [
                            $this->_preDefinedServices[$configValue]['name'],
                            $this->_preDefinedServices[$configValue]['package'],
                            $this->_preDefinedServices[$configValue]['package']
                        ]
                    )
                    )
                );
            }
        }
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    private function isClassExists($class)
    {
        try {
            return class_exists($class);
        } catch (\Exception $e) { // in dev mode error handler throws exception
            return false;
        }
    }
}
