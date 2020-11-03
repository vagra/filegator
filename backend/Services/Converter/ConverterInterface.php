<?php

/*
 * This file is not part of the FileGator package.
 *
 * (c) Yan Chen <vagra@sina.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Services\Converter;

use Filegator\Services\Storage\Filesystem;

interface ConverterInterface
{
    public function convert(string $source, string $destination) : bool;
}
