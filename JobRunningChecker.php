<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use SuperClosure\Serializer;

class JobRunningChecker implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Time to wait before dispatching itself
     *
     * @var int
     */
    protected $sleep = 0;

    /**
     * Text to search in job payload
     *
     * @var
     */
    protected $textToSearch;

    /**
     * Event to fire
     *
     * @var
     */
    protected $event;

    /**
     * @var callable
     */
    protected $callback = null;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * Create a new job instance.
     *
     * @param $textToSearch
     * @param callable $callback
     * @param string $event
     * @param int $sleep
     */
    public function __construct($textToSearch, callable $callback = null, $event = null, $sleep = 0)
    {
        $this->textToSearch = $textToSearch;
        $this->event = $event;
        $this->sleep = $sleep;

        $this->serializer = new Serializer();

        $this->callback = $this->serializer->serialize($callback);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $callback = $this->serializer->unserialize($this->callback);

        //If found, dispatch job
        if ($this->foundText())
            $this->dispatchItself($callback());
        else
            $this->runPayload($callback());

    }

    /**
     * Search text in Job table
     *
     * @return bool
     */
    private function foundText()
    {
        $job = DB::table('jobs')->where('payload', 'like', '%' . $this->textToSearch . '%')
            ->where('payload', 'not like', '%' . 'JobRunningChecker' . '%')
            ->count();

        if ($job)
            return true;

        return false;
    }

    /**
     * Dispatch the same job with same parameters
     *
     * @param $callback
     */
    private function dispatchItself($callback)
    {
        if ($this->sleep)
            sleep($this->sleep);

        dispatch(new JobRunningChecker($this->textToSearch, $callback(), $this->event, $this->sleep));
    }

    /**
     * Run the passed event and/or callback
     *
     * @param $callback
     */
    private function runPayload($callback)
    {
        if ($this->event)
            event(new $this->event());

        if (is_callable($this->callback)) {
            $callback();
        }
    }
}
