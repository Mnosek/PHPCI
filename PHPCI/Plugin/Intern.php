<?php

namespace PHPCI\Plugin;

use PHPCI\Builder;
use PHPCI\Helper\Lang;
use PHPCI\Model\Build;
use PHPCI\Plugin\Util\TestResultParsers\Intern as Parser;


/**
 * Intern plugin
 * @author       Michał Nosek <michal.nosek@velis.pl>
 * @package      PHPCI
 * @subpackage   Plugins
 */
class Intern implements \PHPCI\Plugin
{
    protected $phpci;
    protected $build;
    protected $executable;
    protected $config;
    protected $vars;
    protected $failures = 0;
    protected $timetaken = 0;
    protected $tests = 0;
    protected $output = array();


    /**
     * Standard Constructor
     *
     * @param Builder $phpci
     * @param Build   $build
     * @param array   $options
     */
    public function __construct(Builder $phpci, Build $build, array $options = array())
    {
        $this->phpci    = $phpci;
        $this->build    = $build;
        $this->config   = array();

        if (isset($options['executable'])) {
            $this->executable = $options['executable'];
        } else {
            $this->executable = $this->phpci->findBinary('intern-runner');
        }


        if (isset($options['config'])) {
            $this->config = $options['config'];
        }

        if (isset($options['vars'])) {
            $this->vars = $options['vars'];
        }

    }


    /**
     * Runs intern tests.
     */
    public function execute()
    {
        $curdir = getcwd();
        chdir($this->phpci->buildPath);

        $intern = $this->executable;

        if (!$intern) {
            $this->phpci->logFailure(Lang::get('could_not_find', 'intern-runner'));
            return false;
        }

        if (empty($this->config)) {
            $this->phpci->logFailure('Config file not specified');
        }


        foreach ($this->config as $configFile) {
            if (!file_exists($configFile)) {
                $this->phpci->logFailure('Config file: ' . $configFile . ' not found!');
            }

            $success = $this->phpci->executeCommand($intern . '  config=' . $configFile . $this->prepareVars() . ' reporters=junit');
            
            $xml = file_get_contents('report.xml');

            if (!$xml) {
                $this->phpci->logFailure('Report file not found!');
                return false;
            }


            $parser = new Parser($this->phpci, $xml);

            $this->output = array_merge($this->output, $parser->parse());

            $this->failures = $this->failures + $parser->getTotalFailures();
            $this->timetaken = $this->timetaken + $parser->getTotalTimeTaken();
            $this->tests = $this->tests + $parser->getTotalTests();   
        }

        if (!$this->tests) {
            $this->phpci->logFailure('No tests performed!');
            return false;
        }

        $this->build->storeMeta('intern-summary', $this->output);     

        $meta = array(  'tests'     => $this->tests,
                        'timetaken' => $this->timetaken,
                        'failure'   => $this->failures
        );

        $this->build->storeMeta('intern-meta', $meta);
        $this->build->storeMeta('intern-errors', $this->failures);

        chdir($curdir);

        if ($this->failures) {
            return false;
        }
        return true;
    }


    /**
     * Prepares additional vars string
     */
    public function prepareVars()
    {
        if (!empty($this->vars)) {
            $varsString = '';

            foreach ($this->vars as $var) {
                $varsString = $varsString . ' ' . $var;
            }

           return $varsString; 
        }
    }
}
