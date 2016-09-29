<?php

namespace TinyHelpers;

error_reporting(E_ALL);

/*
WHY?

* Wanted non-blocking UDP sockets
* Ability to override default namespace for each metric we track
* Ability to adjust the sample rate for each metric

*/

class StatsD
{
    private $sender;
    // FOR NOW
    public $host = '127.0.0.1';
    public $port = 8125;

    private $namespace;
    private $sampleRate = 1;

    private $queue = [];

    const MAX_UDP_SIZE_STR = 512;

    // TODO: use interfaces for Sender
    public function __construct($host = '127.0.0.1', $port = 8125, $namespace = null, $sampleRate = 1)
    {
        $this->host = $host;
        $this->port = $port;
        $this->namespace = $namespace;
    }


    public function timing($key, $time, $sampleRate = null, $namespace = null)
    {
        $this->push($this->makeKey($key, $namespace) . ':' . $time . '|ms' . $this->makeSampleRate($sampleRate));

        return $this;
    }

    // Torn about these types that don't support sample rate ... should we always have namespace as 3rd param, and optionally have sampleRate as 4th?
    public function gauge($key, $value, $namespace = null)
    {
        $this->push($this->makeKey($key, $namespace) . ':' . $value . '|g');

        return $this;
    }


    public function set($key, $value, $namespace = null)
    {
        $this->push($this->makeKey($key, $namespace) . ':' . $value . '|s');

        return $this;
    }


    public function increment($key, $sampleRate = null, $namespace = null)
    {
        $this->count($key, 1, $sampleRate);

        return $this;
    }


    public function decrement($key, $sampleRate = null, $namespace = null)
    {
        $this->count($key, -1, $sampleRate);

        return $this;
    }


    public function count($key, $delta, $sampleRate = null, $namespace = null)
    {
        $this->push($this->makeKey($key, $namespace) . ':' . $delta . '|c' . $this->makeSampleRate($sampleRate));

        return $this;
    }
    public function counting($key, $delta) {
        return $this->count($key, $delta);
    }

    private function push($item) {
        $this->queue[] = $item;
    }

    private function makeKey($key, $namespace) {
        if ($namespace) {
            return $namespace . '.' . $key;
        } elseif ($this->namespace) {
            return $this->namespace . '.' . $key;
        }
        return $key;
    }

    // $sampleRate must be a decimal between 0.0 and 1.0
    /*
        sampleRate CAN be null, in which case it'll use the default sample rate
    */
    private function makeSampleRate($sampleRate) {
        $sampleRate = $sampleRate ?: $this->sampleRate;
        return ($sampleRate < 1 ? '|@' . $sampleRate : null);
    }


    private function getBatch() {
        $out = '';
        $len1 = 0;
        while ($this->queue) {
            $item = end($this->queue);
            $len2 = strlen($item);
            if ($len1 + $len2 < self::MAX_UDP_SIZE_STR) {
                $out .= $item . "\n";
                $len1 += $len2 + 1;
                array_pop($this->queue);
            } else {
                // Is this 1 item bigger than the UDP max?
                // TODO: error if so
                break;
            }

        }
        return substr($out, 0, -1);
    }

    /*
     * Send the metrics over UDP
     */
    public function send()
    {
        $written = 0;
        //failures in any of this should be silently ignored if ..
        try {
            $fp = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$fp) {
                return;
            }

            // Put it into non-blocking mode
            socket_set_nonblock($fp);

            while ($this->queue) {
                $message = $this->getBatch();
                echo $message . "\n\n";

                socket_sendto($fp, $message, strlen($message), 0, $this->host, $this->port);
                //fwrite($fp, $message);
            }
            socket_close($fp);
            $this->queue = [];
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return $this;
    }

}

/*
// So we can see batch separations
$s = new StatsD('127.0.0.1', 8125, 'namespace');
$s->increment('counter');
$s->decrement('rev-counter');
$s->gauge('gauge', 46);
//$s->send();

for ($i = 0; $i < 100; $i++) {
    $s->increment('house' . $i);
    $s->timing('something' . $i, 43);
}
$s->send();
*/
