<?php

namespace Enqueue\Magento2\Model;

use Enqueue\Client\Message;
use Enqueue\Rpc\Promise;
use Enqueue\SimpleClient\SimpleClient;
use Interop\Queue\PsrProcessor;

class EnqueueManager
{

    /**
     * @var
     */
    protected $client;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * EnqueueManager constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager

    ) {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }

    public function bindProcessors()
    {
        if (false == $processors = $this->scopeConfig->getValue('enqueue/processors')) {
            return;
        }

        foreach ($processors as $name => $config) {
            if (empty($config['topic'])) {
                throw new \LogicException(sprintf('Topic name is not set for processor: "%s"', $name));
            }

            if (empty($config['helper'])) {
                throw new \LogicException(sprintf('Helper name is not set for processor: "%s"', $name));
            }

            $this->getClient()->bind($config['topic'], $name, function () use ($config) {
                $processor = $this->objectManager->create($config['helper']);

                if (false == $processor instanceof PsrProcessor) {
                    throw new \LogicException(sprintf('Expects processor is instance of: "%s"', PsrProcessor::class));
                }

                return call_user_func_array([$processor, 'process'], func_get_args());
            });
        }
    }

    /**
     * @param string               $topic
     * @param string|array|Message $message
     */
    public function sendEvent($topic, $message)
    {
        $this->getProducer()->sendEvent($topic, $message);
    }

    /**
     * @param string               $command
     * @param string|array|Message $message
     * @param bool                 $needReply
     *
     * @return Promise|null the promise is returned if needReply argument is true
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        return $this->getProducer()->sendCommand($command, $message, $needReply);
    }

    /**
     * @return \Enqueue\Client\ProducerInterface
     */
    public function getProducer()
    {
        return $this->getClient()->getProducer();
    }

    /**
     * @return SimpleClient
     */
    public function getClient()
    {
        if (null === $this->client) {
            $this->client = new SimpleClient($this->buildConfig());
        }

        return $this->client;
    }

    /**
     * @return array
     */
    public function buildConfig()
    {
        $config = $this->getClientConfig();
        $config['transport'] = [];

        switch ($name = $this->scopeConfig->getValue('enqueue/transport/default')) {
            case 'rabbitmq_amqp':
                $config['transport'] = $this->getRabbitMqAmqpConfig();
                break;
            case 'amqp':
                $config['transport'] = $this->getAmqpConfig();
                break;
            case 'stomp':
                $config['transport'] = $this->getStompConfig();
                break;
            case 'rabbitmq_stomp':
                $config['transport'] = $this->getRabbitMqStompConfig();
                break;
            case 'fs':
                $config['transport'] = $this->getFsConfig();
                break;
            case 'sqs':
                $config['transport'] = $this->getSqsConfig();
                break;
            case 'redis':
                $config['transport'] = $this->getRedisConfig();
                break;
            case 'dbal':
                $config['transport'] = $this->getDbalConfig();
                break;
            case 'null':
                $config['transport'] = $this->getNullConfig();
                break;
            default:
                throw new \LogicException(sprintf('Unknown transport: "%s"', $name));
        }

        return $config;
    }


    /**
     * @return array
     */
    public function getClientConfig()
    {
        return ['client' => [
            'prefix' => $this->scopeConfig->getValue('enqueue/client/prefix'),
            'app_name' => $this->scopeConfig->getValue('enqueue/client/app_name'),
            'router_topic' => $this->scopeConfig->getValue('enqueue/client/router_topic'),
            'router_queue' => $this->scopeConfig->getValue('enqueue/client/router_queue'),
            'default_processor_queue' => $this->scopeConfig->getValue('enqueue/client/default_processor_queue'),
            'redelivered_delay_time' => (int) $this->scopeConfig->getValue('enqueue/client/redelivered_delay_time'),
        ]];
    }

    /**
     * @return array
     */
    public function getRabbitMqAmqpConfig()
    {
        return ['rabbitmq_amqp' => [
            'host' => $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/host'),
            'port' =>  (int) $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/port'),
            'user' => $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/user'),
            'pass' => $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/pass'),
            'vhost' => $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/vhost'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/rabbitmq_amqp/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getAmqpConfig()
    {
        return ['amqp' => [
            'host' => $this->scopeConfig->getValue('enqueue/amqp/host'),
            'port' => (int) $this->scopeConfig->getValue('enqueue/amqp/port'),
            'user' => $this->scopeConfig->getValue('enqueue/amqp/user'),
            'pass' => $this->scopeConfig->getValue('enqueue/amqp/pass'),
            'vhost' => $this->scopeConfig->getValue('enqueue/amqp/vhost'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/amqp/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getStompConfig()
    {
        return ['stomp' => [
            'host' => $this->scopeConfig->getValue('enqueue/stomp/host'),
            'port' => (int) $this->scopeConfig->getValue('enqueue/stomp/port'),
            'login' => $this->scopeConfig->getValue('enqueue/stomp/login'),
            'password' => $this->scopeConfig->getValue('enqueue/stomp/password'),
            'vhost' => $this->scopeConfig->getValue('enqueue/stomp/vhost'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/stomp/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getRabbitMqStompConfig()
    {
        return ['rabbitmq_stomp' => [
            'host' => $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/host'),
            'port' => (int) $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/port'),
            'login' => $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/login'),
            'password' => $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/password'),
            'vhost' =>  $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/vhost'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/lazy'),
            'delay_plugin_installed' => (bool) $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/delay_plugin_installed'),
            'management_plugin_installed' => (bool) $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/management_plugin_installed'),
            'management_plugin_port' => (int) $this->scopeConfig->getValue('enqueue/rabbitmq_stomp/management_plugin_port'),
        ]];
    }

    /**
     * @return array
     */
    public function getFsConfig()
    {
        return ['fs' => [
            'store_dir' => $this->scopeConfig->getValue('enqueue/fs/store_dir'),
            'pre_fetch_count' => (int) $this->scopeConfig->getValue('enqueue/fs/pre_fetch_count'),
            'chmod' => intval($this->scopeConfig->getValue('enqueue/fs/chmod'), 8),
        ]];
    }

    /**
     * @return array
     */
    public function getSqsConfig()
    {
        return ['sqs' => [
            'key' => $this->scopeConfig->getValue('enqueue/sqs/key'),
            'secret' => $this->scopeConfig->getValue('enqueue/sqs/secret'),
            'token' => $this->scopeConfig->getValue('enqueue/sqs/token'),
            'region' => $this->scopeConfig->getValue('enqueue/sqs/region'),
            'retries' => (int) $this->scopeConfig->getValue('enqueue/sqs/retries'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/sqs/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getRedisConfig()
    {
        return ['redis' => [
            'host' => $this->scopeConfig->getValue('enqueue/redis/host'),
            'port' => (int) $this->scopeConfig->getValue('enqueue/redis/port'),
            'vendor' => $this->scopeConfig->getValue('enqueue/redis/vendor'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/redis/lazy'),
        ]];
    }

    /**
     * @return array
     */
    public function getDbalConfig()
    {
        return ['dbal' => [
            'connection' => [
                'url' => $this->scopeConfig->getValue('enqueue/dbal/url'),
            ],
            'table_name' => $this->scopeConfig->getValue('enqueue/dbal/table_name'),
            'polling_interval' => (int) $this->scopeConfig->getValue('enqueue/dbal/polling_interval'),
            'lazy' => (bool) $this->scopeConfig->getValue('enqueue/dbal/lazy'),
        ]];
    }

    /**
     * @return array
     */
    private function getNullConfig(): array
    {
        return ['null' => []];
    }
}