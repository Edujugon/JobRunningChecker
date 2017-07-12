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
        $this->serializer = new Serializer();

        $this->textToSearch = $textToSearch;
        $this->event = $event;
        $this->sleep = $sleep;

        if (is_callable($callback)) {
            $this->callback = $this->serializer->serialize($callback);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!is_null($this->callback))
            $this->callback = $this->serializer->unserialize($this->callback);

        if ($this->foundText())
            $this->dispatchItself();
        else
            $this->runPayload();

    }

    /**
     * Search text in Job table
     *
     * @return bool
     */
    private function foundText()
    {
        $count = DB::table('jobs')->where('payload', 'like', '%' . $this->textToSearch . '%')
            ->where('payload', 'not like', '%' . 'JobRunningChecker' . '%')
            ->count();

        if ($count)
            return true;

        return false;
    }

    /**
     * Dispatch the same job with same parameters
     *
     */
    private function dispatchItself()
    {
        if ($this->sleep)
            sleep($this->sleep);

        $callback = is_callable($this->callback) ? ($this->callback)() : null;

        dispatch(new JobRunningChecker($this->textToSearch, $callback, $this->event, $this->sleep));
    }

    /**
     * Run the passed event and/or callback
     *
     */
    private function runPayload()
    {

        if ($this->event)
            event(new $this->event());

        if (is_callable($this->callback)) {
            ($this->callback)();
        }
    }
}
