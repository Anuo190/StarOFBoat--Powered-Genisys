<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\scheduler;

use pocketmine\event\Timings;
use pocketmine\Server;

class AsyncPool{

	/** @var Server */
	private $server;

	protected $size;

	/** @var AsyncTask[] */
	private $tasks = [];
	/** @var int[] */
	private $taskWorkers = [];

	/** @var AsyncWorker[] */
	private $workers = [];
	/** @var int[] */
	private $workerUsage = [];

	public function __construct(Server $server, $size){
		$this->server = $server;
		$this->size = (int) $size;

		for($i = 0; $i < $this->size; ++$i){
			$this->workerUsage[$i] = 0;
			$this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1);
			$this->workers[$i]->setClassLoader($this->server->getLoader());
			$this->workers[$i]->start();
		}
	}

	public function getSize(){
		return $this->size;
	}

	public function increaseSize($newSize){
    $newSize = (int) $newSize;
    if($newSize > $this->size){
        for($i = $this->size; $i < $newSize; ++$i){
            $this->workerUsage[$i] = 0;
            if(!isset($this->workers[$i])){
                $this->workers[$i] = new AsyncWorker($this->server->getLogger(), $i + 1);
                $this->workers[$i]->setClassLoader($this->server->getLoader());
                $this->workers[$i]->start();
            }
        }
        $this->size = $newSize;
    }
}

        public function submitTaskToWorker(AsyncTask $task, $worker){
        if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
            return;
        }

        $worker = (int) $worker;
        if($worker < 0 or $worker >= $this->size){
            throw new \InvalidArgumentException("Invalid worker $worker");
        }

        $taskId = $task->getTaskId();
        $this->tasks[$taskId] = $task;

        $this->workers[$worker]->stack($task);
        $this->workerUsage[$worker]++;
        $this->taskWorkers[$taskId] = $worker;
    }


    public function submitTask(AsyncTask $task){
        if(isset($this->tasks[$task->getTaskId()]) or $task->isGarbage()){
            return;
        }

        $selectedWorker = $this->findMostIdleWorker();
        $this->submitTaskToWorker($task, $selectedWorker);
    }

    private function removeTask(AsyncTask $task, $force = false){
        $taskId = $task->getTaskId();
        $task->setGarbage();

        if(isset($this->taskWorkers[$taskId])){
            if(!$force and ($task->isRunning() or !$task->isGarbage())){
                return;
            }
            $workerId = $this->taskWorkers[$taskId];
            $this->workerUsage[$workerId]--;
            $this->workers[$workerId]->collector($task);
        }

        unset($this->tasks[$taskId]);
        unset($this->taskWorkers[$taskId]);

        $task->cleanObject();
    }

        public function removeTasks(){
        $tasksCount = count($this->tasks);
        do{
            foreach($this->tasks as $task){
                $task->cancelRun();
                $this->removeTask($task);
            }

            if($tasksCount > 0){
                Server::microSleep(25000);
            }
        }while($tasksCount > 0);

        for($i = 0; $i < $this->size; ++$i){
            $this->workerUsage[$i] = 0;
        }

        $this->taskWorkers = [];
        $this->tasks = [];
    }
	public function collectTasks(){
		Timings::$schedulerAsyncTimer->startTiming();

		foreach($this->tasks as $task){
			if($task->isFinished() and !$task->isRunning() and !$task->isCrashed()){

				if(!$task->hasCancelledRun()){
					$task->onCompletion($this->server);
				}

				$this->removeTask($task);
			}elseif($task->isTerminated() or $task->isCrashed()){
				$this->server->getLogger()->critical("Could not execute asynchronous task " . (new \ReflectionClass($task))->getShortName() . ": Task crashed");
				$this->removeTask($task, true);
			}
		}

		Timings::$schedulerAsyncTimer->stopTiming();
	}
    private function findMostIdleWorker(){
        $selectedWorker = mt_rand(0, $this->size - 1);
        $selectedTasks = $this->workerUsage[$selectedWorker];
        for($i = 0; $i < $this->size; ++$i){
            if($this->workerUsage[$i] < $selectedTasks){
                $selectedWorker = $i;
                $selectedTasks = $this->workerUsage[$i];
            }
        }
        return $selectedWorker;
    }
}
