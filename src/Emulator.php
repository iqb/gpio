<?php
/*
 * (c) 2015 by Dennis Birkholz <dennis@birkholz.biz>
 * All rights reserved.
 * For the license to use this software,
 * see the LICENSE file provided with this package.
 */

namespace iqb\gpio;

class Emulator
{
    /**
     * Emulate kernel sysfs interface to GPIO here
     * @var string
     */
    protected $basedir;

    /**
     * @param $basedir
     */
    public function __construct($basedir)
    {
        $this->basedir = $basedir;

        if (!is_dir($this->basedir) && !mkdir($this->basedir, 0777, true)) {
            throw new \RuntimeException('Unable to use directory "' . $this->basedir . '" to emulate sysfs interface');
        }
    }

    /**
     * File handle to the gpio/export file used to enable gpio ports
     * @var resource
     */
    protected $export_fp;

    /**
     * File handle to the gpio/unexport file used to disable gpio ports
     * @var resource
     */
    protected $unexport_fp;

    public function run()
    {
        $this->export_fp = $this->connectSocket($this->basedir . '/export');
        $this->unexport_fp = $this->connectSocket($this->basedir . '/unexport');

        while (true) {
            $read = [$this->export_fp, $this->unexport_fp];
            $write = $except = null;

            $count = stream_select($read, $write, $except, 30);
            if ($count === -1) { continue; }

            foreach ($read as $fp) {
                if ($fp === $this->export_fp) {
                    $this->export();
                }

                elseif ($fp === $this->unexport_fp) {
                    $this->unexport();
                }
            }
        }
    }

    /**
     * Enabled GPIO ports
     * @var array
     */
    protected $ports = [];

    protected function export()
    {
        $port = trim(fread($this->export_fp, 1024));
        echo "Exporting '$port'\n";
    }

    protected function unexport()
    {
        $port = trim(fread($this->unexport_fp, 1024));
        echo "Unexporting '$port'\n";
    }

    /**
     * Helper function to create a unix domain socket in datagram mode (and clear a previously existing socket)
     *
     * @param $filename Path name of the socket
     * @return resource
     */
    protected function connectSocket($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        $fp = @stream_socket_server('udg://' . $filename, $errno, $errstr, STREAM_SERVER_BIND);

        if (!$fp || !is_resource($fp)) {
            throw new \RuntimeException('Could not create "' . $filename . '" listener, reason: (' . $errno . ')' . $errstr);
        } else {
            return $fp;
        }
    }
}