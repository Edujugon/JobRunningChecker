# JobRunningChecker - A Laravel Job

Run a callback and/or fire an event just after a job or a jobs list of a class have finished.

Many times you have to split the task in many jobs and you need to know when that list has finished in order to run another task.
 It could be a bit tricky but thanks to this Class you can add a callback or an event to be run just after the jobs have been completed.


## What does the JobRunningChecker class actually do?

This class performs a db query looking for a provided text.
That text can be the job class name you want to know if has finished or even any property data that belongs to that job.
If that text is found, JobRunningChecker will dispatch itself and perform the same check.
Once it's not found it means the job or jobs list has finished so it's time to run the callback and fire the event.

## How to use it

Copy the JobRunningChecker Class into you app\Jobs folder.

Keep in mind that the default namespace for this Class is `App\Jobs` so if you would like to place the Class under another folder you have to update the namespace.

```php
namespace App\Jobs;
```

## Usage samples

Run a callback:

```php
dispatch(new JobRunningChecker('TEXT-TO-SEARCH',function(){
            //Your Code which will be run just after the searched job has finished
        }));
```
> Remember 'TEXT-TO-SEARCH' can be the job class name or any other text that contain to the job.

Fire an event:

```php
dispatch(new JobRunningChecker('TEXT-TO-SEARCH',null,EventName::class);
```

Run a callback and fire an event:

```php
dispatch(new JobRunningChecker('TEXT-TO-SEARCH',function(){
            //Your Code which will be run just after the searched job has finished
        }),EventName::class);
```

You can also pass a sleep time to make it wait till the next itself call:

```php
dispatch(new JobRunningChecker('TEXT-TO-SEARCH',function(){
            //Your Code which will be run just after the searched job has finished
        }),EventName::class,5);
```
> In the above sample it will wait for 5 seconds till dispatching itself.

## Limitations

For now, It only works when you have set the queue driver to database.

## Thanks to

[Laravel](https://laravel.com/)

[SuperClosure](https://github.com/jeremeamia/super_closure)