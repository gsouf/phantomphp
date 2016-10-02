<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

class ResponsePool
{
    /**
     * @var Response[]
     */
    protected $responses = [];

    public function addResponse(Response $response)
    {
        $this->responses[$response->getId()] = $response;
    }

    /**
     * @param $responseId
     * @return Response
     */
    public function getResponse($responseId)
    {
        return isset($this->responses[$responseId]) ? $this->responses[$responseId] : null;
    }
}
