<?php
/**
 * @license see LICENSE
 */

namespace PhantomPhp;

class HttpClient extends PhantomClient
{

    public function __construct($port = 8080, $phantomjsBinaries = null)
    {
        parent::__construct($phantomjsBinaries, 'http', ['httpPort' => $port]);
    }
}
