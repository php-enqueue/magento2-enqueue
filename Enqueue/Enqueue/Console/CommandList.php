<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Enqueue\Enqueue\Console;

use Enqueue\Symfony\Client\ConsumeMessagesCommand;
use Enqueue\Symfony\Client\Meta\QueuesCommand;
use Enqueue\Symfony\Client\Meta\TopicsCommand;
use Enqueue\Symfony\Client\ProduceMessageCommand;
use Enqueue\Symfony\Client\SetupBrokerCommand;
/**
 * Provides list of commands to be available for uninstalled application
 */
class CommandList implements \Magento\Framework\Console\CommandListInterface
{
    /**
     * @var \Enqueue\Symfony\Client\SetupBrokerCommandFactory
     */
    private $setupBrokerCommandFactory;

    /**
     * @var \Enqueue\Symfony\Client\ConsumeMessagesCommandFactory
     */
    private $consumeMessagesCommandFactory;

    /**
     * @var \Enqueue\Symfony\Client\Meta\TopicsCommandFactory
     */
    private $topicsCommandFactory;

    /**
     * @var \Enqueue\Symfony\Client\ProduceMessageCommandFactory
     */
    private $produceMessageCommandFactory;

    /**
     * @var \Enqueue\Symfony\Client\Meta\QueuesCommandFactory
     */
    private $queuesCommandFactory;
    /**
     * @var \Enqueue\Enqueue\Model\EnqueueManager
     */
    private $enqueueManager;

    /**
     * CommandList constructor.
     *
     * @param \Enqueue\Enqueue\Model\EnqueueManager $enqueueManager
     * @param \Enqueue\Symfony\Client\SetupBrokerCommandFactory $setupBrokerCommandFactory
     * @param \Enqueue\Symfony\Client\Meta\QueuesCommandFactory $queuesCommandFactory
     * @param \Enqueue\Symfony\Client\Meta\TopicsCommandFactory $topicsCommandFactory
     * @param \Enqueue\Symfony\Client\ProduceMessageCommandFactory $produceMessageCommandFactory
     * @param \Enqueue\Symfony\Client\ConsumeMessagesCommandFactory $consumeMessagesCommand
     */
    public function __construct(
        \Enqueue\Enqueue\Model\EnqueueManager $enqueueManager,
        \Enqueue\Symfony\Client\SetupBrokerCommandFactory $setupBrokerCommandFactory,
        \Enqueue\Symfony\Client\Meta\QueuesCommandFactory $queuesCommandFactory,
        \Enqueue\Symfony\Client\Meta\TopicsCommandFactory $topicsCommandFactory,
        \Enqueue\Symfony\Client\ProduceMessageCommandFactory $produceMessageCommandFactory,
        \Enqueue\Symfony\Client\ConsumeMessagesCommandFactory $consumeMessagesCommand
    ) {
        $this->enqueueManager = $enqueueManager;
        $this->setupBrokerCommandFactory = $setupBrokerCommandFactory;
        $this->queuesCommandFactory = $queuesCommandFactory;
        $this->topicsCommandFactory = $topicsCommandFactory;
        $this->produceMessageCommandFactory = $produceMessageCommandFactory;
        $this->consumeMessagesCommandFactory = $consumeMessagesCommand;
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    private function getCommandsClasses()
    {
        return [
            SetupBrokerCommand::class,
            ProduceMessageCommand::class,
            TopicsCommand::class,
            ConsumeMessagesCommand::class,
            QueuesCommand::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCommands()
    {
        $commands = [];

        $this->enqueueManager->bindProcessors();
        $client = $this->enqueueManager->getClient();

        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = $this->setupBrokerCommandFactory->create(['driver' => $client->getDriver()]);
                $commands[] = $this->produceMessageCommandFactory->create(['producer' => $client->getProducer()]);
                $commands[] = $this->queuesCommandFactory->create(['queueRegistry' => $client->getQueueMetaRegistry()]);
                $commands[] = $this->topicsCommandFactory->create(['topicRegistry' => $client->getTopicMetaRegistry()]);
                $commands[] = $this->consumeMessagesCommandFactory->create(
                    [
                        'consumer' => $client->getQueueConsumer(),
                        'processor' => $client->getDelegateProcessor(),
                        'queueMetaRegistry' => $client->getQueueMetaRegistry(),
                        'driver' => $client->getDriver()
                    ]
                );
            } else {
                throw new \Exception('Class ' . $class . ' does not exist');
            }
        }

        return $commands;
    }
}