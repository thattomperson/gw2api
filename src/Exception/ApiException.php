<?php

namespace GW2Treasures\GW2Api\Exception;

use Exception;
use GuzzleHttp\Message\ResponseInterface;

class ApiException extends Exception {
    /** @var ResponseInterface $response */
    protected $response;

    public function __construct( $message = "", ResponseInterface $response ) {
        $this->response = $response;

        parent::__construct( $message, $response->getStatusCode() ); // TODO: Change the autogenerated stub
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse() {
        return $this->response;
    }

    public function __toString() {
        return $this->message . ' (' .
               'status: ' . $this->response->getStatusCode() . '; ' .
               'url: ' . $this->response->getEffectiveUrl() . ')';
    }
}
