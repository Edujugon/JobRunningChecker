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
    protected $callback;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * Create a new job instance.
     *
     * @param $textToSearch
     * @param callable $callback
     * @param $event
     * @param int $sleep
     */
    public function __construct($textToSearch, callable $callback = null, $event = null, $sleep = 0)
    {
        $this->textToSearch = $textToSearch;
        $this->event = $event;

        if ($sleep)
            $this->sleep = $sleep;

        if (is_callable($callback)) {
            $this->serializer = new Serializer();
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
        //If found, dispatch job
        if ($this->foundText()) {

            if ($this->sleep)
                sleep($this->sleep);

            $callback = $this->serializer->unserialize($this->callback);
            dispatch(new JobRunningChecker($this->textToSearch, $callback(), $this->event, $this->sleep));
        } else {
            if ($this->event)
                event(new $this->event());

            if (!is_null($this->callback)) {
                $callback = $this->serializer->unserialize($this->callback);
                $callback();
            }
        }
    }

    /**
     * Search text in Job table
     *
     * @return bool
     */
    protected function foundText()
    {
        $job = DB::table('jobs')->where('payload', 'like', '%' . $this->textToSearch . '%')
            ->where('payload', 'not like', '%' . 'JobRunningChecker' . '%')
            ->count();

        if ($job)
            return true;

        return false;
    }
}
