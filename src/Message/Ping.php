<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp\Message;

use PhantomPhp\Message;

class Ping extends Message
{

    public function __construct()
    {
        parent::__construct('ping');
    }
}
