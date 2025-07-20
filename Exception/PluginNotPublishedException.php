<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Exception;

class PluginNotPublishedException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
