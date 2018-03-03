# Magento2 EnqueueModule

The module integrates [Enqueue Client](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/client/quick_tour.md) with Magento2. You can send and consume messages to different message queues such as RabbitMQ, AMQP, STOMP, Amazon SQS, Kafka, Redis, Google PubSub, Gearman, Beanstalk, Google PubSub and others. Or integrate Magento2 app with other applications or service via [Message Bus](https://github.com/php-enqueue/enqueue-dev/blob/master/docs/client/message_bus.md).

## Installation

We recommend using [composer](https://getcomposer.org/) to install [magento2-enqueue](https://github.com/php-enqueue/magento-enqueue) module. To install libraries run the commands in the application root directory.

```bash
composer require "enqueue/magento2-enqueue:*@dev" "enqueue/amqp-ext"
```

Run setup:upgrade so Magento2 picks up the installed module.

```bash
php bin/magento setup:upgrade 
```

## Configuration

At this stage we have configure the Enqueue extension in Magento backend. 
The config is here: `Stores -> Configuration -> General -> Enqueue Message Queue`.
Here's the example of Amqp transport that connects to RabbitMQ broker on localhost:

![Сonfiguration](enqueue_doc.png)

## Publish Message

To send a message you have to take enqueue helper and call `send` method.

```php
<?php

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$enqueueManager = $objectManager->create('Enqueue\Enqueue\Model\EnqueueManager');
$enqueueManager->send('a_topic', 'aMessage');
```

## Message Consumption

I assume you have `acme` Magento module properly created, configured and registered. 
To consume messages you have to define a processor class first: 

```php
<?php
// app/code/Acme/Module/Helper/Async/Foo.php

namespace Acme\Module\Helper\Async;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class Foo implements PsrProcessor
{
    public function process(PsrMessage $message, PsrContext $context)
    {
        // do job
        // $message->getBody() -> 'payload'

        return self::ACK;         // acknowledge message
        // return self::REJECT;   // reject message
        // return self::REQUEUE;  // requeue message
    }
}
```

than subscribe it to a topic or several topics:


```xml
<!-- app/etc/local.xml -->

<config>
  <default>
    <enqueue>
      <processors>
        <foo-processor>
          <topic>a_topic</topic>
          <helper>acme/async_foo</helper>
        </foo-processor>
      </processors>
    </enqueue>
  </default>
</config>
```

and run message consume command:

```bash
$ php bin/magento enqueue:consume -vvv --setup-broker
```

[back to index](../index.md)

## Developed by Forma-Pro

Forma-Pro is a full stack development company which interests also spread to open source development. 
Being a team of strong professionals we have an aim an ability to help community by developing cutting edge solutions in the areas of e-commerce, docker & microservice oriented architecture where we have accumulated a huge many-years experience. 
Our main specialization is Symfony framework based solution, but we are always looking to the technologies that allow us to do our job the best way. We are committed to creating solutions that revolutionize the way how things are developed in aspects of architecture & scalability.

If you have any questions and inquires about our open source development, this product particularly or any other matter feel free to contact at opensource@forma-pro.com

## License

It is released under the [MIT License](LICENSE).
