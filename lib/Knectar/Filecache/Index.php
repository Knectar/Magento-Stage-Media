<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Knectar Design
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 *
 * @category Utilities
 * @package Knectar_StageMedia
 * @author Daniel Deady <daniel.deady@knectar.com>
 * @license http://opensource.org/licenses/MIT
 */

class Knectar_Filecache_Index extends ArrayObject
{

    /**
     * @var string
     */
    protected $indexFile;

    /**
     * @var bool
     */
    protected $isDirty = false;

    public function __construct($dir, $indexFile = '.filecache')
    {
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $indexFile = ltrim($indexFile, DIRECTORY_SEPARATOR);
        $this->indexFile = $dir . $indexFile;
        $entries = @file($this->indexFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        parent::__construct($entries ? $entries : array());
    }

    public function __destruct()
    {
        if ($this->isDirty) {
            file_put_contents($this->indexFile, implode(PHP_EOL, $this->getArrayCopy()), LOCK_EX);
        }
    }

    public function offsetSet($index, $newval)
    {
        if (is_null($index)) {
            // easy write to make
            file_put_contents($this->indexFile, $newval . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        else {
            // writing will be expensive, schedule it for later
            $this->isDirty = true;
        }
        return parent::offsetSet($index, $newval);
    }

    public function offsetUnset($index)
    {
        $this->isDirty = true;
        return parent::offsetUnset($index);
    }
}
