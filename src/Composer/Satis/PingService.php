<?php

namespace Bolt\Composer\Satis;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Translation\Translator as Trans;
use Bolt\Version;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class to provide pinging of the Bolt Marketplace as a service.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class PingService
{
    /** @var Client */
    private $client;
    /** @var RequestStack */
    private $requestStack;
    /** @var string */
    private $uri;
    /** @var MutableBag */
    private $messages;

    /**
     * Constructor.
     *
     * @param Client       $client
     * @param RequestStack $requestStack
     * @param string       $uri
     */
    public function __construct(Client $client, RequestStack $requestStack, $uri)
    {
        $this->client = $client;
        $this->requestStack = $requestStack;
        $this->uri = $uri;
        $this->messages = MutableBag::of();
    }

    /**
     * Ping site to see if we have a valid connection and it is responding correctly.
     *
     * @param bool $addQuery
     */
    public function ping($addQuery = false, $debugClient = false)
    {
        try {
            $this->doPing($addQuery, $debugClient);

            return true;
        } catch (ClientException $e) {
            // Thrown for 400 level errors
            $this->messages[] = Trans::__(
                'page.extend.error-message-client',
                ['%errormessage%' => $e->getMessage()]
            );
        } catch (ServerException $e) {
            // Thrown for 500 level errors
            $this->messages[] = Trans::__(
                'page.extend.error-message-server',
                ['%errormessage%' => $e->getMessage()]
            );
        } catch (RequestException $e) {
            // Thrown for connection timeout, DNS errors, etc
            $this->messages[] = Trans::__(
                'page.extend.error-message-connection',
                ['%errormessage%' => $e->getMessage()]
            );
        } catch (\Exception $e) {
            // Catch all
            $this->messages[] = Trans::__(
                'page.extend.error-message-generic',
                ['%errormessage%' => $e->getMessage()]
            );
        }

        return false;
    }

    /**
     * @return Bag
     */
    public function getMessages()
    {
        return $this->messages->immutable();
    }

    /**
     * @param bool $addQuery
     * @param bool $debugClient
     *
     * @throws ClientException
     * @throws ServerException
     * @throws RequestException
     */
    private function doPing($addQuery, $debugClient)
    {
        if ($this->requestStack->getCurrentRequest() !== null) {
            $www = $this->requestStack->getCurrentRequest()->server->get('SERVER_SOFTWARE', 'unknown');
        } else {
            $www = 'unknown';
        }
        $query = !$addQuery ? [] : [
            'bolt_ver'  => Version::VERSION,
            'php'       => PHP_VERSION,
            'www'       => $www,
        ];

        $options = [
            RequestOptions::QUERY           => $query,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::TIMEOUT         => 30,
        ];
        if ($debugClient) {
            $options[RequestOptions::DEBUG] = true;
        }

        $this->client->head($this->uri, $options);
    }
}
