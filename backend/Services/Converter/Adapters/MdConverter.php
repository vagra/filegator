<?php

/*
 * This file is not part of the FileGator package.
 *
 * (c) Yan Chen <vagra@sina.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Services\Converter\Adapters;

use Filegator\Config\Config;
use Filegator\Services\Converter\ConverterInterface;
use Filegator\Services\Service;
use Filegator\Services\Storage\Filesystem as Storage;
use Michelf\MarkdownExtra;

class MdConverter implements Service, ConverterInterface
{
    protected const PRINT_CSS = 'print-vue.css';

    protected $config;

    protected $storage;

    protected $outStream;

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

        $md = stream_get_contents($inStream['stream']);

        if (is_resource($inStream)) {
            fclose($inStream);
        }

        $content = MarkdownExtra::defaultTransform($md);

        $this->printHtml($content);

        $this->storage->store($destination, $filename.'.html', $this->outStream);

        if (is_resource($this->outStream)) {
            fclose($this->outStream);
        }
        
        $this->copyCss($destination);

        return true;
    }

    protected function copyCss(string $destination)
    {
        $srcPath = '/css/'.self::PRINT_CSS;
        $distPath = $destination .'/'. self::PRINT_CSS;

        if (! $this->storage->fileExists($distPath))
        {
            $this->storage->rootCopyFile($srcPath, $destination);
        }
    }

    protected function printHtml(string $content)
    {
        $this->outStream = tmpfile();

        $html = $this->html($content);

        fwrite($this->outStream, $html);

        rewind($this->outStream);
    }


    private function html(string $content)
    {
        $random = uniqid();

        return '<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="print-vue.css?v='.$random.'">
</head>

<body class="ready">
    <main>
        <section class="content">
            <article id="main" class="markdown-section">
                '.$content.'
            </article>
        </section>
    </main>
</body>

</html>';

    }
    
}
