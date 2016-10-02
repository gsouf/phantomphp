<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp\Message;

use PhantomPhp\Message;

class ExitMessage extends Message
{

    public function __construct()
    {
        parent::__construct('exit');
    }
}
