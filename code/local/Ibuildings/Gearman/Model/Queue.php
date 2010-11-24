<?php

@require_once 'Net/Gearman/Client.php';

/**
 * Ibuildings Gearman Magento Model Observer
 *
 * @copyright (c) 2010 Ibuildings UK Ltd.
 * @author Michael Davey
 * @version 0.1.0
 * @package Ibuildings
 * @subpackage Gearman
 */
class Ibuildings_Gearman_Model_Queue extends Mage_Core_Model_Abstract
{
    /**
     * Reference to the queue object for sending jobs and fetching status
     * @var GearmanClient|Net_Gearman_Client
     */
    private $_client;

    /**
     * Constructor
     *
     * Reads the server details from config and creates the GearmanClient
     * object and connects to the queue server
     */
    public function __construct()
    {
        $opts = Mage::getStoreConfig('gearman_options');
        if (class_exists('Net_Gearman_Client')) {
            $server = $opts['gearman']['server'] . ':' . $opts['gearman']['port'];
            $this->_client = new Net_Gearman_Client($server);
        }
        else {
            $this->_client = new GearmanClient();
            $this->_client->addServer(
                $opts['gearman']['server'],
                $opts['gearman']['port']
            );
        }
    }

    /**
     * Send the job to the queue specified
     *
     * @param array $task Array containing the 'queue' name and the job 'workload'
     * @return string|false The Gearman ID for the submitted task if the gearman extension is used
     */
    public function dispatchTask($task)
    {
        if (get_class($this->_client) === 'Net_Gearman_Client') {
            $ngTask = new Net_Gearman_Task(
                $task['queue'],
                array($task['task'])
            );
            $this->_client->submitTask($ngTask);
            // There is no way to query a job status in Net_Gearman
            // presently, so no point in returning this...
            // return $ngTask->handle;
            return null;
        }
        else {
            return $this->_client->doBackground(
                $task['queue'],
                serialize($task['task'])
            );
        }
    }
    
    /**
     * Check the status of a previously submitted job
     *
     * @param string $id The unique Gearman job ID
     * @return boolean Whether task is complete or not
     */
    public function checkTaskComplete($id)
    {
        if (get_class($this->_client) !== 'Net_Gearman_Client') {
            $status = $this->_client->jobStatus($id);
            return !$status[0];
        }
        else {
            return null;
        }
    }
}