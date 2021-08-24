<?php

namespace App\Component\HttpFoundation;

use SimpleXMLElement;
use StdClass;
use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    public function __construct(?stdClass $responseData, string $returnCode = 'SUCCESS', int $status = 200, array $headers = [])
    {
        if ($responseData === null) {
            $responseData = (object) [];
        }
        $responseData->returncode = $returnCode;

        $document = new SimpleXMLElement('<?xml version="1.0"?><response></response>');
        $this->convertToXml($document, $responseData);

        $content = $document->saveXML();
        parent::__construct($content, $status, array_merge($headers, [
            'Content-Type' => 'text/xml',
        ]));
    }

    /**
     * Convert the data object to XML using SimpleXML.
     *
     * @param SimpleXMLElement $node
     * @param mixed $data
     */
    protected function convertToXml(SimpleXMLElement $node, $data): void {
        foreach ((array) $data as $key => $value) {
            if (is_array($value)) {
                $value = (object) $value;
            }
            if (is_object($value) && !empty($value->forcexmlarraytype)) {
                $newkey = $value->forcexmlarraytype;
                $value = array_values($value->array);
                $subnode = $node->addChild($key);
                foreach ($value as $val) {
                    $arraynode = $subnode->addChild($newkey);
                    $this->convertToXml($arraynode, $val);
                }

            } else {
                if (is_object($value)) {
                    $value = (array) $value;
                }
                if (is_numeric($key)) {
                    $key = "_{$key}";
                }
                if (is_array($value)) {
                    $subnode = $node->addChild($key);
                    $this->convertToXml($subnode, $value);
                } else {
                    $node->addChild((string) $key, htmlspecialchars($value));
                }
            }
        }
    }
}
