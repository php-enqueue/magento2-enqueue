<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Enqueue\Magento2\Console;

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
     * @var \Enqueue\Magento2\Model\EnqueueManager
     */
    private $enqueueManager;

    /**
     * CommandList constructor.
     *
     * @param \Enqueue\Magento2\Model\EnqueueManager $enqueueManager
     * @param \Enqueue\Symfony\Client\SetupBrokerCommandFactory $setupBrokerCommandFactory
     * @param \Enqueue\Symfony\Client\Meta\QueuesCommandFactory $queuesCommandFactory
     * @param \Enqueue\Symfony\Client\Meta\TopicsCommandFactory $topicsCommandFactory
     * @param \Enqueue\Symfony\Client\ProduceMessageCommandFactory $produceMessageCommandFactory
     * @param \Enqueue\Symfony\Client\ConsumeMessagesCommandFactory $consumeMessagesCommand
     */
    public function __construct(
        \Enqueue\Magento2\Model\EnqueueManager $enqueueManager,
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
     * @inheritdoc
     */
    public function getCommands()
    {
        $commands = [];

        $this->enqueueManager->bindProcessors();
        $client = $this->enqueueManager->getClient();

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

        return $commands;
    }
}