<?php

/**
 * This file is part of the bitbucket-api package.
 *
 * (c) Alexandru G. <alex@gentle.ro>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bitbucket\API\Http;

use Buzz\Client\ClientInterface as BuzzClientInterface;
use Buzz\Client\Curl;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Bitbucket\API\Http\Listener\ListenerInterface;

/**
 * @author  Alexandru G.    <alex@gentle.ro>
 */
class Client implements ClientInterface
{
    /**
     * @var array
     */
    protected $options = array(
        'base_url'      => 'https://api.bitbucket.org',
        'api_version'   => '1.0',
        'api_versions'  => array('1.0', '2.0'),     // supported versions
        'format'        => 'json',
        'formats'       => array('json', 'xml'),    // supported response formats
        'user_agent'    => 'bitbucket-api-php/0.2.0 (https://bitbucket.org/gentlero/bitbucket-api)',
        'timeout'       => 10,
        'verify_peer'   => false
    );

    /**
     * @var BuzzClientInterface
     */
    protected $client;

    /**
     * @var MessageInterface
     */
    private $lastRequest;

    /**
     * @var RequestInterface
     */
    private $lastResponse;

    /**
     * @var ListenerInterface[]
     */
    protected $listeners = array();

    public function __construct(array $options = array(), BuzzClientInterface $client = null)
    {
        $this->client   = (is_null($client)) ? new Curl : $client;
        $this->options  = array_merge($this->options, $options);

        $this->client->setTimeout($this->options['timeout']);
        $this->client->setVerifyPeer($this->options['verify_peer']);
    }

    /**
     * {@inheritDoc}
     */
    public function addListener(ListenerInterface $listener)
    {
        $this->listeners[$listener->getName()] = $listener;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getListener($name)
    {
        if (!$this->isListener($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown listener %s', $name));
        }

        return $this->listeners[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function isListener($name)
    {
        return isset($this->listeners[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function get($endpoint, $params = array(), $headers = array())
    {
        if (is_array($params) AND count($params) > 0) {
            $endpoint   .= (strpos($endpoint, '?') === false ? '?' : '&').http_build_query($params, '', '&');
            $params     = array();
        }

        return $this->request($endpoint, $params, 'GET', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function post($endpoint, $params = array(), $headers = array())
    {
        return $this->request($endpoint, $params, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($endpoint, $params = array(), $headers = array())
    {
        return $this->request($endpoint, $params, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($endpoint, $params = array(), $headers = array())
    {
        return $this->request($endpoint, $params, 'DELETE', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($endpoint, array $params = array(), $method, array $headers = array())
    {
        // do not set base URL if a full one was provided
        if (false === strpos($endpoint, $this->getApiBaseUrl())) {
            $endpoint = $this->getApiBaseUrl().'/'.$endpoint;
        }

        // change the response format
        if (strpos($endpoint, 'format=') === false) {
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&').'format='.$this->getResponseFormat();
        }

        $request = $this->createRequest($method, $endpoint);

        if (!empty($headers)) {
            $request->addHeaders($headers);
        }

        if (!empty($params)) {
            $request->setContent(is_array($params) ? http_build_query($params) : $params);
        }

        $response       = new Response;

        $this->executeListeners($request, 'preSend');

        $this->client->send($request, $response);

        $this->executeListeners($request, 'postSend', $response);

        $this->lastRequest  = $request;
        $this->lastResponse = $response;

        return $response;
    }

    /**
     * @access public
     * @return BuzzClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponseFormat()
    {
        return $this->options['format'];
    }

    /**
     * {@inheritDoc}
     */
    public function setResponseFormat($format)
    {
        if (!in_array($format, $this->options['formats'])) {
            throw new \InvalidArgumentException(sprintf('Unsupported response format %s', $format));
        }

        $this->options['format'] = $format;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiVersion()
    {
        return $this->options['api_version'];
    }

    /**
     * {@inheritDoc}
     */
    public function setApiVersion($version)
    {
        if (!in_array($version, $this->options['api_versions'])) {
            throw new \InvalidArgumentException(sprintf('Unsupported API version %s', $version));
        }

        $this->options['api_version'] = $version;

        return $this;
    }

    /**
     * Check if specified API version is the one currently in use
     *
     * @access public
     * @param  float $version
     * @return bool
     */
    public function isApiVersion($version)
    {
        return (abs($this->options['api_version'] - $version) < 0.00001);
    }

    /**
     * {@inheritDoc}
     */
    public function getApiBaseUrl()
    {
        return $this->options['base_url'].'/'.$this->getApiVersion();
    }

    /**
     * @access public
     * @return MessageInterface
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @access public
     * @return RequestInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @access protected
     * @param  string           $method
     * @param  string           $url
     * @return RequestInterface
     */
    protected function createRequest($method, $url)
    {
        $request = new Request($method);
        $request->addHeaders(array(
                'User-Agent' => $this->options['user_agent']
            ));
        $request->setProtocolVersion(1.1);
        $request->fromUrl($url);

        return $request;
    }

    /**
     * Execute all available listeners
     *
     * $when can be: preSend or postSend
     *
     * @access protected
     * @param RequestInterface $request
     * @param string           $when     When to execute the listener
     * @param MessageInterface $response
     */
    protected function executeListeners(RequestInterface $request, $when = 'preSend', MessageInterface $response = null)
    {
        $haveListeners  = count($this->listeners) > 0;

        if (!$haveListeners) {
            return;
        }

        $params = array($request);

        if (!is_null($response)) {
            $params[] = $response;
        }

        foreach ($this->listeners as $listener) {
            call_user_func_array(array($listener, $when), $params);
        }
    }
}
