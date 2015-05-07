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

class Knectar_Filecache_Remote
{

    /**
     * @var Knectar_Filecache_Index
     */
    protected $index;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $destination;

    public function __construct($source, $destination)
    {
        $this->source = rtrim($source, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->destination = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->index = new Knectar_Filecache_Index($destination);
    }

    /**
     * Download $filename relative to $source and store in $destination
     *
     * @param string $filename
     */
    public function fetch($filename)
    {
        $filename = ltrim($filename, DIRECTORY_SEPARATOR);
        $source = $this->source . $filename;
        $destination = $this->destination . $filename;
        if (file_exists($destination) && ! is_writable($destination)) {
            throw new RuntimeException($destination . ' cannot be overwritten.');
        }

        // check if it has been tried previously
        if ($this->index->in_array($filename)) {
            return;
        }
        // mark as attempted
        // if file is not written next it won't be attmpted until after next purge (24hrs?)
        $this->index->append($filename);

        $destinationDir = dirname($destination);
        @mkdir($destinationDir, 0777, true);
        if (! is_writable($destinationDir)) {
            throw new RuntimeException($destinationDir . ' is not writable for downloading.');
        }

        // TODO use established HTTP client which doesn't depend on allow_url_fopen
        $sourceFile = @fopen($source, 'r');
        if (! $sourceFile) {
            throw new RuntimeException($source . ' could not be read remotely.');
        }

        $tempname = tempnam(sys_get_temp_dir(), 'download');
        $tempfile = @fopen($tempname, 'w');
        if (! $tempfile) {
            throw new RuntimeException(sys_get_temp_dir() . ' is not writable.');
        }
        stream_copy_to_stream($sourceFile, $tempfile);
        fclose($sourceFile);
        fclose($tempfile);
        rename($tempname, $destination) && chmod($destination, 0777);
    }

    /**
     * Delete all tracked files older than $oldest
     *
     * @param string $oldest
     * @see http://php.net/manual/en/datetime.formats.php
     */
    public function purge($oldest = 'last week')
    {
        $cutoff = is_int($oldest) ? $oldest : strtotime($oldest);
        foreach ($this->index->getArrayCopy() as $id => $filename) {
            $filename = $this->destination . $filename;
            if (! file_exists($filename)) {
                $this->index->offsetUnset($id);
                continue;
            }

            $atime = fileatime($filename);
            if (!$atime) $atime = filemtime($filename);
            if (!$atime) $atime = filectime($filename);
            if ($atime && ($atime < $cutoff)) {
                unlink($filename) && $this->index->offsetUnset($id);
            }
        }
        // index will now write itself on __destruct()
    }
}
