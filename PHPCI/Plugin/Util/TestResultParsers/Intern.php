<?php

namespace PHPCI\Plugin\Util\TestResultParsers;


use PHPCI\Builder;


class Intern
{
    protected $phpci;
    protected $resultsXml;
    protected $results;
    protected $totalTests;
    protected $totalTimeTaken;
    protected $totalFailures;
    
    
    /**
     * Standard Constructor
     *
     * @param Builder $phpci
     * @param Build   $build
     * @param array   $options
     */
    public function __construct(Builder $phpci, $resultsXml)
    {
        $this->phpci = $phpci;
        $this->resultsXml = $resultsXml;
        $this->totalTests = 0;
        $this->totalTimeTaken = 0;
        $this->totalFailures = 0;
    }
    


    /**
     * @return array An array of result data
     */
    public function parse()
    {
        $rtn = array();

        $this->results = new \SimpleXMLElement($this->resultsXml);

        $platforms = array();
        $i = 0;

        foreach ($this->results->testsuite as $suite) {
            
            $errors = array();

            $summary = array();
            $summary['name'] = (string)$suite->attributes()->name;
            $summary['failures'] = (string)$suite->attributes()->failures;
            $summary['tests'] = (string)$suite->attributes()->tests;
            $summary['time'] = (string)$suite->attributes()->time;

            $this->totalTests = $this->totalTests + $summary['tests'];
            $this->totalTimeTaken = $this->totalTimeTaken + $summary['time'];

            $platforms[$i] = $summary;

            foreach ($suite->testsuite as $subsuite) {
                $suiteName = (string)$subsuite->testsuite->attributes()->name;

                foreach ($subsuite->testsuite->testcase as $testcase) {
                    if ($testcase->attributes()->status != 0) {

                        $this->totalFailures++;

                        $error = array(
                            'platform' => $summary['name'],
                            'suiteName' => $suiteName,
                            'testName' => (string)$testcase->attributes()->name
                        );

                        if ($testcase->failure) {
                            $error['message'] = (string)$testcase->failure->attributes()->message;
                        } elseif ($testcase->error) {
                            $error['message'] = (string)$testcase->error->attributes()->message;
                        }

                        $errors[] = $error;
                    }
                }
            }

            $platforms[$i]['errors'] = $errors;
            $i++;
        }
        return $platforms;

    }


    public function getTotalTests()
    {
        return $this->totalTests;
    }


    public function getTotalTimeTaken()
    {
        return $this->totalTimeTaken;
    }


    public function getTotalFailures()
    {
        return $this->totalFailures;
    }
}


