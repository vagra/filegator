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

class HtmlConverter implements Service, ConverterInterface
{
    protected $config;
    protected $storage;

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
        $target = $this->storage->changeExt($source, 'pdf');

        // $target = $this->storage->checkUpcont($target);

        $basepath = APP_REPO_DIR . $this->storage->getPathPrefix();

        $inpath = escapeshellarg($basepath . $source);
        $outpath = escapeshellarg($basepath . $target);
        $logpath = $basepath . $destination . '/output.txt';
        error_log($outpath);

        $descriptorspec = array(
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['file', $logpath, 'a']
        );

        //$env = getenv('LANG');
        //error_log('LANG='.$env);

        $cmd = "weasyprint {$inpath} {$outpath}";
        $cmd = escapeshellcmd($cmd);

        $process = proc_open(
            $cmd,
            $descriptorspec,
            $pipes
        );
        
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $return_value = proc_close($process);
        
            if ($return_value == 0) {
                return true;
            }
        }

        return false;
    }

}