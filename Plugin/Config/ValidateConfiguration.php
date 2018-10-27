<?php

namespace Enqueue\Magento2\Plugin\Config;

use \Enqueue\AmqpExt\AmqpContext;
use Enqueue\Null\NullContext;
use \Enqueue\Stomp\StompContext;
use \Enqueue\Fs\FsContext;
use \Enqueue\Sqs\SqsContext;
use \Enqueue\Redis\RedisContext;
use \Enqueue\Dbal\DbalContext;
use \Magento\Framework\Exception\TemporaryState\CouldNotSaveException;

class ValidateConfiguration
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
        ],
        'null' => [
            'name' => 'Null transport',
            'package' => 'enqueue/null',
            'class' => NullContext::class
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

            if (false === isset($this->_preDefinedServices[$configValue])) {
                throw new \LogicException(sprintf('Unknown transport: "%s"', $configValue));
            }

            if (false === $this->isClassExists($this->_preDefinedServices[$configValue]['class'])) {
                throw new CouldNotSaveException(
                    __(
                        '%name transport requires package "%package".'
                        . ' Please install it via composer. #> php composer.php require %package',
                        [
                            'name' => $this->_preDefinedServices[$configValue]['name'],
                            'package' => $this->_preDefinedServices[$configValue]['package'],
                        ]
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
