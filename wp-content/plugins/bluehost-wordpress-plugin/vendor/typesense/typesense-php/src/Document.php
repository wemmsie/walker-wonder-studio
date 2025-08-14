<?php

namespace Devloops\Typesence;

use Devloops\Typesence\Lib\Configuration;

/**
 * Class Document
 *
 * @package Devloops\Typesence
 * @date    4/5/20
 * @author  Abdullah Al-Faqeir <abdullah@devloops.net>
 */
class Document
{

    /**
     * @var \Devloops\Typesence\Lib\Configuration
     */
    private $config;

    /**
     * @var string
     */
    private $collectionName;

    /**
     * @var int
     */
    private $documentId;

    /**
     * @var \Devloops\Typesence\ApiCall
     */
    private $apiCall;

    /**
     * Document constructor.
     *
     * @param  \Devloops\Typesence\Lib\Configuration  $config
     * @param  string  $collectionName
     * @param  string  $documentId
     */
    public function __construct(
      Configuration $config, string $collectionName, string $documentId
    ) {
        $this->config         = $config;
        $this->collectionName = $collectionName;
        $this->documentId     = $documentId;
        $this->apiCall        = new ApiCall($config);
    }

    /**
     * @return string
     */
    private function endpoint_path(): string
    {
        return sprintf('%s/%s/%s/%s', Collections::RESOURCE_PATH, $this->collectionName, Documents::RESOURCE_PATH, $this->documentId);
    }

    /**
     * @return array
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError|\GuzzleHttp\Exception\GuzzleException
     */
    public function retrieve(): array
    {
        return $this->apiCall->get($this->endpoint_path(), []);
    }

    /**
     * @return array
     * @throws \Devloops\Typesence\Exceptions\TypesenseClientError|\GuzzleHttp\Exception\GuzzleException
     */
    public function delete(): array
    {
        return $this->apiCall->delete($this->endpoint_path());
    }

}
