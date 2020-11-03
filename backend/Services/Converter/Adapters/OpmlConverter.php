<?php

/*
 * This file is not part of the FileGator package.
 *
 * (c) Yan Chen <vagra@sina.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Services\Converter\Adapters;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Filegator\Config\Config;
use Filegator\Services\Converter\ConverterInterface;
use Filegator\Services\Service;
use Filegator\Services\Storage\Filesystem as Storage;

class OpmlConverter implements Service, ConverterInterface
{
    protected $config;

    protected $storage;

    protected $outStream;

    protected $level;

    protected $first;

    public function __construct(Config $config, Storage $storage)
    {
        $this->config = $config;
        $this->storage = $storage;
    }

    public function init(array $config = [])
    {
    }

    public function convert(string $source, string $destination) : bool
    {
        $inStream = $this->storage->readStream($source);
        $filename = $this->storage->getFileName($source);

        $content = stream_get_contents($inStream['stream']);

        if (is_resource($inStream)) {
            fclose($inStream);
        }

        $doc = new DOMDocument();

        libxml_use_internal_errors(true);
        $doc->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOBLANKS);
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($doc);

        $this->outStream = tmpfile();

        if (!$this->parseTitle($xpath)) {
            return false;
        }

        if (!$this->parseBody($xpath)) {
            return false;
        }

        rewind($this->outStream);

        $this->storage->store($destination, $filename.'.md', $this->outStream);

        if (is_resource($this->outStream)) {
            fclose($this->outStream);
        }

        return true;
    }

    protected function parseTitle(DOMXPath $xpath) : bool
    {
        $nodes = $xpath->query('/opml/head/title');
        
        if (! isset($nodes) || $nodes->length < 1) {
            return false;
        }

        $title = $nodes->item(0);

        $this->level = 1;

        $heading = "#1 {$title->nodeValue}\n\n";
        
        fwrite($this->outStream, $heading);

        return true;
    }

    protected function parseBody(DOMXPath $xpath) : bool
    {
        $nodes = $xpath->query('/opml/body');
        
        if (! isset($nodes) || $nodes->length < 1) {
            return false;
        }

        $body = $nodes->item(0);

        $this->first = true;

        if (! $this->parseNode($body))
        {
            return false;
        }

        return true;
    }

    protected function parseNode(DOMNode $node) : bool
    {
        if ($node->nodeName == "outline"){
            if ($this->first)
            {
                $this->level = 2;
                $this->first = false;
            }

            $textAttr = $node->attributes->getNamedItem('text');
            if (! isset($textAttr)) {
                return false;
            }
            $text = $textAttr->nodeValue;

            if ($node->hasChildNodes())
            {
                $heading = "#{$this->level} {$text}\n\n";
                fwrite($this->outStream, $heading);
            }
            else
            {
                $paragraph = "{$text}\n\n";
                fwrite($this->outStream, $paragraph);
            }

            $noteAttr = $node->attributes->getNamedItem('_note');
            if (isset($noteAttr))
            {
                $note = $noteAttr->nodeValue;

                if (strlen($note) > 0) {
                    $quote = "> {$note}\n\n";
                    fwrite($this->outStream, $quote);
                }
            }
        }

        if ($node->hasChildNodes())
        {
            $this->level += 1;
            foreach ($node->childNodes as $childNode)
            {
                if (! $this->parseNode($childNode))
                {
                    return false;
                }
            }
            $this->level -= 1;
        }

        return true;
    }
}
