<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

use PhantomPhp\Communication\ChannelInterface;
use PhantomPhp\Exception\PageException;
use PhantomPhp\Exception\PageException\CannotNavigateToUrl;

class Page
{

    /**
     * @var ChannelInterface
     */
    protected $channel;

    protected $pageId;

    /**
     * @param $pageId
     * @param ChannelInterface $channel
     */
    public function __construct($pageId, ChannelInterface $channel)
    {
        $this->pageId = $pageId;
        $this->channel = $channel;
    }

    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @return ChannelInterface
     */
    protected function getChannel()
    {
        return $this->channel;
    }

    public function navigate($url)
    {
        $message = new Message(
            'pageNavigate',
            ['pageId' => $this->getPageId(), 'url' => $url]
        );
        $this->getChannel()->sendMessage($message);
        $response = $this->getChannel()->waitForResponse($message, 3000);

        if ($response->isSuccessful()) {
            return $response->getData('url');
        } else {
            throw new CannotNavigateToUrl('Cannot navigate to the url ' . $url . '. ' . $response->getData('message'));
        }
    }

    public function getDomContent()
    {
        $message = new Message(
            'pageGetDom',
            ['pageId' => $this->pageId]
        );
        $this->getChannel()->sendMessage($message);
        $response = $this->getChannel()->waitForResponse($message, 3000);

        if ($response->isSuccessful()) {
            return $response->getData('DOM');
        } else {
            throw new PageException('Cannot get the dom. ' . $response->getData('message'));
        }
    }

    public function runScript($script)
    {
        $message = new Message(
            'pageRunScript',
            [
                'pageId' => $this->pageId,
                'script' => $script
            ]
        );
        $this->getChannel()->sendMessage($message);
        $response = $this->getChannel()->waitForResponse($message, 3000);

        if ($response->isSuccessful()) {
            return $response->getData();
        } else {
            throw new PageException('Cannot run script on the dom. ' . $response->getData('message'));
        }
    }
}
