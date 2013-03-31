<?php

// Без этой директивы PHP не будет перехватывать сигналы
// PHP >= 5.3.0 вместо declare(ticks = 1) надо бы использовать pcntl_signal_dispatch()
declare(ticks=1); 

class Observer
{
    // Максимальное количество дочерних процессов
    public    $max_processes = 5;
    // Когда установится в TRUE, демон завершит работу
    protected $stop_server = FALSE;
    // Здесь будем хранить запущенные дочерние процессы
    protected $currentJobs = array();

    public function __construct($max_processes = 5, $stop_server = FALSE)
    {
        $this->max_processes = $max_processes;
        $this->stop_server = $stop_server;
        
        echo "Сonstructed daemon controller".PHP_EOL;
        // Ждем сигналы SIGTERM и SIGCHLD
        pcntl_signal(SIGTERM, array($this, "childSignalHandler"));
        pcntl_signal(SIGCHLD, array($this, "childSignalHandler"));
    }

    public function run() {
        echo "Running daemon controller".PHP_EOL;

        // Пока $stop_server не установится в TRUE, гоняем бесконечный цикл
        while (!$this->stop_server)
        {
            // Если уже запущено максимальное количество дочерних процессов, ждем их завершения
            while(count($this->currentJobs) >= $this->max_processes)
            {
                echo "Maximum children allowed, waiting...".PHP_EOL;
                sleep(1);
            }
            
            $this->launchJob();
        } 
    }
    
    public function childSignalHandler($signo, $pid = null, $status = null) {
        switch($signo) {
            case SIGTERM:
                // При получении сигнала завершения работы устанавливаем флаг
                $this->stop_server = true;
                break;
            case SIGCHLD:
                // При получении сигнала от дочернего процесса
                if (!$pid)
                {
                    $pid = pcntl_waitpid(-1, $status, WNOHANG); 
                } 
                // Пока есть завершенные дочерние процессы
                while ($pid > 0)
                {
                    if ($pid && isset($this->currentJobs[$pid]))
                    {
                        // Удаляем дочерние процессы из списка
                        unset($this->currentJobs[$pid]);
                    } 
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                } 
                break;
            default:
                // все остальные сигналы
        }
    }
    
    protected function launchJob()
    { 
        // Создаем дочерний процесс
        // весь код после pcntl_fork() будет выполняться
        // двумя процессами: родительским и дочерним
        $pid = pcntl_fork();
        if ($pid == -1)
        {
            // Не удалось создать дочерний процесс
            error_log('Could not launch new job, exiting');
            return FALSE;
        } 
        elseif ($pid)
        {
            // Этот код выполнится родительским процессом
            $this->currentJobs[$pid] = TRUE;
        } 
        else
        { 
            $this->_do();
        } 
        return TRUE; 
    }
    
    protected function _do()
    {
            // А этот код выполнится дочерним процессом
            echo "Процесс с ID ".getmypid().PHP_EOL;
            exit(); 
    }
}