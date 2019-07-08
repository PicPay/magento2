<?php

namespace Picpay\Payment\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;

class DoNothingCommand implements CommandInterface
{
    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        // This is fake. No action expected.
    }
}