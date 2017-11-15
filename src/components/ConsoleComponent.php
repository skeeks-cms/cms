<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 01.11.2015
 */

namespace skeeks\cms\components;

use yii\base\Component;

/**
 * Class ConsoleComponent
 * @package skeeks\cms\components
 */
class ConsoleComponent extends Component
{

    /**
     * @param $cmd
     * @return string
     */
    public function execute($cmd)
    {
        if (function_exists('system')) {
            return $this->executeSystem($cmd);
        } else {
            list($output, $error, $code) = $this->executeProcOpen($cmd);
            return trim($output);
        }
    }

    /**
     * @param $command
     * @return string
     */
    public function executeSystem($command)
    {
        if (function_exists('system')) {
            ob_start();
            @system($command);
            $result = ob_get_clean();

            $result = trim($result);

            return $result;
        }

        return "";
    }

    /**
     * @param $command
     * @return array
     */
    public function executeProcOpen($command)
    {
        $descriptors = array(
            0 => array("pipe", "r"), // stdin - read channel
            1 => array("pipe", "w"), // stdout - write channel
            2 => array("pipe", "w"), // stdout - error channel
            3 => array("pipe", "r"), // stdin - This is the pipe we can feed the password into
        );

        $process = @proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            die("Can't open resource with proc_open.");
        }

        // Nothing to push to input.
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        // TODO: Write passphrase in pipes[3].
        fclose($pipes[3]);

        // Close all pipes before proc_close!
        $code = proc_close($process);

        return array($output, $error, $code);
    }

}