<?php
// START SNIPPET: doxia
require_once dirname(__FILE__).'/../../main/php/Logger.php';
Logger::configure(dirname(__FILE__).'/../resources/appender_socket_server.properties');

require_once 'Net/Server.php';
require_once 'Net/Server/Handler.php';

class Net_Server_Handler_Log extends Net_Server_Handler {
  
        private $hierarchy;

        function onStart() {
                $this->hierarchy = Logger::getRootLogger();
        }
  
        function onReceiveData($clientId = 0, $data = "") {
                $events = $this->getEvents($data);
                foreach($events as $event) {
                        $root = $this->hierarchy->getRootLogger();
                        if($event->getLoggerName() === 'root') {
                            $root->callAppenders($event);
                        } else {
                             $loggers = $this->hierarchy->getCurrentLoggers();
                                foreach($loggers as $logger) {
                                        $root->callAppenders($event);
                                        $appenders = $logger->getAllAppenders();
                                        foreach($appenders as $appender) {
                                                $appender->doAppend($event);
                                        }
                                }
                        }
                }
        }
  
        function getEvents($data) {
                if (preg_match('/^<log4php:event/', $data)) {
                    throw new Exception("Please use 'log4php.appender.default.useXml = false' in appender_socket.properties file!");
                }
                preg_match('/^(O:\d+)/', $data, $parts);
                $events = split($parts[1], $data);
                array_shift($events);
                $size = count($events);
                for($i=0; $i<$size; $i++) {
                        $events[$i] = unserialize($parts[1].$events[$i]);
                }
                return $events;
        }
}

$host = 'localhost';
$port = 4242;
$server = Net_Server::create('sequential', $host, $port);
$handler = new Net_Server_Handler_Log();
$server->setCallbackObject($handler);
$server->start();
// END SNIPPET: doxia