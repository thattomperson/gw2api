<?php

namespace GW2Treasures\GW2Api\V2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Pool;
use GW2Treasures\GW2Api\Exception\ApiException;
use GW2Treasures\GW2Api\GW2Api;

abstract class Endpoint implements IEndpoint {
    /** @var GW2Api $api */
    protected $api;

    /** @var ApiHandler[] */
    protected $handlers = [];

    /**
     * @param GW2Api $api
     */
    public function __construct( GW2Api $api ) {
        $this->api = $api;

        $this->api->attachRegisteredHandlers( $this );
    }

    /**
     * @return Client
     */
    protected function getClient() {
        return $this->getApi()->getClient();
    }

    protected function getApi() {
        return $this->api;
    }

    /**
     * Creates a new Request to this Endpoint.
     *
     * @param string[] $query
     * @param null     $url
     * @param string   $method
     * @param array    $options
     * @return ApiResponse
     */
    protected function request( array $query = [], $url = null, $method = 'GET', $options = [] ) {
        $request = $this->createRequest( $query, $url, $method, $options );

        foreach( $this->handlers as $handler ) {
            $handler->onRequest( $request );
        }

        try {
            $response = $this->getClient()->send( $request );
        } catch( ClientException $ex ) {
            if( $ex->hasResponse() ) {
                $response = $ex->getResponse();

                foreach( $this->handlers as $handler ) {
                    $handler->onError( $response );
                }

                $this->handleUnhandledError( $response );
            } else {
                throw $ex;
            }
        }

        foreach( $this->handlers as $handler ) {
            $handler->onResponse( $response );
        }

        return new ApiResponse( $response );
    }

    /**
     * Creates a new Request to this Endpoint.
     *
     * @param string[][] $queries
     * @param null       $url
     * @param string     $method
     * @param array      $options
     * @return ApiResponse[]
     */
    protected function requestMany( array $queries = [], $url = null, $method = 'GET', $options = [] ) {
        $requests = [];
        $responses = [];

        foreach( $queries as $query ) {
            $request = $this->createRequest( $query, $url, $method, $options );

            foreach( $this->handlers as $handler ) {
                $handler->onRequest( $request );
            }

            $requests[] = $request;
        }

        $results = Pool::batch( $this->getClient(), $requests, [ 'pool_size' => 128 ]);

        foreach( $results as $response ) {
            /** @var Response|ClientException|\Exception $response */

            if( $response instanceof \Exception ) {
                if( $response instanceof ClientException && $response->hasResponse() ) {
                    foreach( $this->handlers as $handler ) {
                        $handler->onError( $response );
                    }

                    $this->handleUnhandledError( $response );
                }

                throw $response;
            }

            foreach( $this->handlers as $handler ) {
                $handler->onResponse( $response );
            }

            $responses[] = new ApiResponse( $response );
        }

        return $responses;
    }


    /**
     * Creates a new Request to this Endpoint.
     *
     * @param string[] $query
     * @param null     $url
     * @param string   $method
     * @param array    $options
     * @return RequestInterface
     */
    protected function createRequest( array $query = [], $url = null, $method = 'GET', $options = [] ) {
        $url = !is_null( $url ) ? $url : $this->url();
        return $this->getClient()->createRequest( $method, $url, $options + [ 'query' => $query ]);
    }

    /**
     * Handles response codes != 200
     *
     * @param ResponseInterface $response
     * @throws ApiException
     */
    private function handleUnhandledError( ResponseInterface $response ) {
        $responseJson = null;

        if( $response->hasHeader('Content-Type') ) {
            $contentType = $response->getHeader('Content-Type');
            if( stripos( $contentType, 'application/json' ) === 0 ) {
                $responseJson = $response->json([ 'object' => true ]);
            }
        }

        if( !is_null( $responseJson) && isset( $response->text )) {
            $message = $response->text;
        } else {
            $message = 'Unknown GW2Api error';
        }

        throw new ApiException( $message, $response );
    }

    /**
     * @param ApiHandler $handler
     */
    public function attach( ApiHandler $handler ) {
        $this->handlers[] = $handler;
    }

    /**
     * The url of this endpoint.
     *
     * @return string
     */
    protected abstract function url();

    /**
     * String representation of this endpoint.
     *
     * @return string
     */
    function __toString() {
        return '[' . get_class( $this ) . '(' . $this->url() . ']';
    }
}
