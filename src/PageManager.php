<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

use PhantomPhp\Communication\ChannelInterface;
use PhantomPhp\Exception\PageException\CannotCreatePageException;

class PageManager
{

    /**
     * @var ChannelInterface
     */
    protected $chanel;

    /**
     * @param ChannelInterface $chanel
     */
    public function __construct(ChannelInterface $chanel)
    {
        $this->chanel = $chanel;
    }

    public function createPage($name = null)
    {

        $message = new Message('pageCreate', ['pageId' => $name]);
        $this->chanel->sendMessage($message);
        $response = $this->chanel->waitForResponse($message, 2000);

        if ($response->isSuccessful()) {
            return new Page($response->getData('pageId'), $this->chanel);
        } else {
            throw new CannotCreatePageException('Cannot create page. ' . $response->getData('message'));
        }
    }
}
