<?php
namespace TinyHelpers;
/*
Should probably rename this to Async Exec Pool or similar
*/

class Command {
	public $key;
	public $command;
	public $handle = null;
	public $pipes = null;
	public $output = '';
	public $error = '';

	public function open() {
		$descriptors = array(
		   0 => array("pipe", "r"), // stdin is a pipe that the child will read from
		   1 => array("pipe", "w"), // stdout is a pipe that the child will write to
		   2 => array("pipe", "w") // stderr
		);
		$this->handle = proc_open($this->command, $descriptors, $this->pipes);
		fclose($this->pipes[0]);
		$this->pipes[0] = null;
		//stream_set_blocking($this->pipes[0], 0);
		//stream_set_blocking($this->pipes[2], 2);
		/*
		fclose($this->pipes[2]);
		$this->pipes[2] = null;
		*/
	}
	public function read() {
		// echo 'reading from: ' . $this->command . "\n";
		$pipe = $this->pipes[1];
		$bytes = stream_get_contents($pipe);
		if ($bytes === false) {
			//echo "failure\n";
			return;
		} elseif (strlen($bytes) > 0) {
			$this->output .= $bytes;
		}
	}
	// Return true when pipe is EOF
	public function readErrors() {
		//echo 'err from: ' . $this->command . "\n";
		$pipe = $this->pipes[2];
		$bytes = stream_get_contents($pipe);
		if ($bytes === false) {
			//echo  "failure\n";
			return;
		} elseif (strlen($bytes) > 0) {
			$this->error .= $bytes;
		}
	}
	public function isDone() {
		$done = 0;
		for ($i = 0; $i < 3; $i++) {
			if (!$this->pipes[ $i ]) {
				$done++;
			} elseif (feof($this->pipes[ $i ])) {
				fclose($this->pipes[ $i ]);
				$this->pipes[ $i ] = null;
				$done++;
			}
		}
		if ($done == 3) {
			return true;
		} else {
		}
		return false;
	}

	public function close() {
		if ($this->handle) {
			proc_close($this->handle);
			$this->handle = $this->pipes = null;
		}
	}
}

class AsyncExec {
	protected $commands;
	public function __construct($commands) {
		$this->commands = $commands;
	}

	public function run($simultaneously = 5, $timeout = 5, $pollSleep = 1) {
		$commands = array();
		foreach ($this->commands as $i => $command) {
			$o = new Command();
			$o->key = $i;
			$o->command = $command;
			$o->handle = null;
			$o->pipes = null;
			$commands[] = $o;
		}

		$pending = array();
		$done = array();

		// Keep checking
		while ($commands || $pending) {
			if ($pending) {
				$reads = array();
				$writes = array();
				$except = array();
				$readMap = array();
				$errorMap = array();
				foreach ($pending as $i => $command) {
					if ($command->pipes) {
						if ($command->pipes[1]) {
							$reads[] = $command->pipes[1];
							$readMap[ $i ] = $command->pipes[1];
						}
						if ($command->pipes[2]) {
							$reads[] = $command->pipes[2];
							$errorMap[ $i ] = $command->pipes[2];
						}
					}
				}
				//echo "select\n";
				$ready = stream_select($reads, $writes, $except, $pollSleep);
				if ($ready === false) {
					echo 'select error';
					// error?
				} elseif ($ready > 0) {
					foreach ($reads as $pipe) {
						$i = array_search($pipe, $errorMap);
						if ($i !== false) {
							$command = $pending[ $i ];
							$command->readErrors();
						}
						$i = array_search($pipe, $readMap);
						if ($i !== false) {
							$command = $pending[ $i ];
							$command->read();
						}
					}
				}
				foreach (array_keys($pending) as $i) {
					$command = $pending[ $i ];
					if ($command->isDone()) {
						$command->close();
						$done[ $command->key ] = $command;
						unset($pending[ $i ]);
					}
				}
			}

			while (count($commands) && count($pending) < $simultaneously) {
				$command = array_shift($commands);
				$command->open();
				$pending[] = $command;
				//echo "started\n";
			}
		}

		$output = array();
		foreach ($done as $i => $command) {
			$output[ $i ] = $command->output;
		}
		return $output;
	}
}

/*
$commands = array(
	'd' => '/bin/date',
	'ls' => '/bin/ls',
	's5' => '/bin/sleep 5',
	's2' => '/bin/sleep 2'
);
$a = new AsyncExec($commands);
$out = $a->run(4);

var_dump($out);
*/
