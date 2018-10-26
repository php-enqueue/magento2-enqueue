<?php

/**
 * Magento 2 autoloader calls \Magento\Framework\Code\Generator
 * which is supposed to generate missing classes, e.g. Factories, Proxies, Interceptors.
 * For some reason, it throws \RuntimeException in \Magento\Framework\Code\Generator::tryToLoadSourceClass
 * when it finds out that the class for which a factory supposed to exist is also missing.
 * It's the case which we have in \Enqueue\Magento2\Model\EnqueueManager where, for example,
 * for missing \Enqueue\Stomp\StompConnectionFactory Magento tries to generate a factory
 * based on Enqueue\Stomp\StompConnection, but this class is also missing and this leads to the exception.
 *
 * To prevent such behavior from Magento, we could handle this exception.
 */
namespace Enqueue\SimpleClient {

    if (!\function_exists('\Enqueue\SimpleClient\class_exists')) {
        function class_exists($class_name, $autoload = true)
        {
            try {
                return \class_exists($class_name, $autoload);
            } catch (\RuntimeException $exception) {
                return false;
            }
        }
    }
}
