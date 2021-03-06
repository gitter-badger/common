<?php

namespace VGMdb\Component\Queue;

/**
 * Runs the worker job loop.
 *
 * @author Gigablah <gigablah@vgmdb.net>
 */
class Runner
{
    const INTERVAL = 10000;

    protected $worker;
    protected $interval;
    protected $wait;
    protected $counter;

    public function __construct(WorkerInterface $worker, array $options = array())
    {
        $this->worker = $worker;
        $this->interval = isset($options['interval']) ? intval($options['interval']) : self::INTERVAL;
        $this->wait = isset($options['wait']) ? (Boolean) $options['wait'] : true;
        $this->counter = null;
    }

    public function getWorker()
    {
        return $this->worker;
    }

    public function run($counter = null)
    {
        $this->counter = intval($counter) ?: null;

        while (0 !== $this->counter) {
            if ($job = $this->worker->getJob()) {
                try {
                    $this->worker->runJob($job);
                } catch (\Exception $exception) {
                    // BackOffStrategy?
                }
                if ($this->counter) {
                    $this->counter--;
                }
            } else {
                if (!$this->wait) {
                    $this->counter--;
                }
            }
            usleep($this->interval);
        }
    }
}
