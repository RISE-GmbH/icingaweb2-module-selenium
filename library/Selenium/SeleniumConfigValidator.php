<?php

namespace Icinga\Module\Selenium;

use Exception;
use ipl\I18n\Translation;
use ipl\Validator\BaseValidator;

/**
 *
 *

 *
 */
class SeleniumConfigValidator extends BaseValidator
{
    use Translation;


    /**
     * Create a new E-mail address validator with optional options
     *
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct(array $options = [])
    {

    }

    public function validateStructure($data){
        $testIds=[];
        $commandIds=[];
        $suiteIds=[];
        if(!isset($data['id'])){
            $this->addMessage($this->translate('The base element has no id.'));
            return false;
        }
        if(!isset($data['url'])){
            $this->addMessage($this->translate('The base element has no url.'));
            return false;
        }
        if(!isset($data['tests'])){
            $this->addMessage($this->translate('The base element has no tests.'));
            return false;
        }
        if(! is_array($data['tests'])){
            $this->addMessage($this->translate('Tests is not an array.'));
            return false;
        }

        foreach($data['tests'] as $key=>$test){

            if(!isset($test['name'])){
                $element = $key+1;
                $this->addMessage(sprintf($this->translate('Test %s has no name.'), $element));
                return false;
            }

            if(!isset($test['id'])){
                $this->addMessage(sprintf($this->translate('Test %s has no id.'), $test['name']));
                return false;
            }else{
                $testIds[]=$test['id'];
            }
            if(!isset($test['commands'])){
                $this->addMessage(sprintf($this->translate('Test %s has no test.'), $test['name']));
                return false;
            }
            if(! is_array($test['commands'])){
                $this->addMessage(sprintf($this->translate('Test %s commands is no array'), $test['name']));
                return false;
            }
            foreach($test['commands'] as $key2=>$command){

                if(!isset($command['command'])){
                    $element = $key2+1;
                    $this->addMessage(sprintf($this->translate('Test %s command %s has no command.'), $test['name'], $element));
                    return false;
                }

                if(!isset($command['id'])){
                    $this->addMessage(sprintf($this->translate('Test %s command %s has no id.'), $test['name'], $command['command']));
                    return false;
                }else{
                    $commandIds[]=$command['id'];
                }
            }
        }

        if(!isset($data['suites'])){
            $this->addMessage($this->translate('The base element has no suites.'));
            return false;
        }
        if(! is_array($data['suites'])){
            $this->addMessage($this->translate('Suites is not an array.'));
            return false;
        }

        foreach($data['suites'] as $key=>$suite){

            if(!isset($suite['name'])){
                $element = $key+1;
                $this->addMessage(sprintf($this->translate('Suite %s has no name.'), $element));
                return false;
            }

            if(!isset($suite['id'])){
                $this->addMessage(sprintf($this->translate('Suite %s has no id.'), $suite['name']));
                return false;
            }else{
                $suiteIds[]=$suite['id'];
            }
            if(!isset($suite['tests'])){
                $this->addMessage(sprintf($this->translate('Suite %s has no tests.'), $suite['name']));
                return false;
            }
            if(! is_array($suite['tests'])){
                $this->addMessage(sprintf($this->translate('Suite %s tests is no array'), $suite['name']));
                return false;
            }
            foreach($suite['tests'] as $test){
                if(! in_array($test,$testIds)){
                    $this->addMessage(sprintf($this->translate('Test %s mentioned in Suite %s  does not exist'), $test, $suite['name']));
                    return false;
                }
            }
        }
        $testsVsSuites = array_intersect($testIds,$suiteIds);
        $testsVsCommands = array_intersect($testIds,$commandIds);
        $SuitesVsCommands = array_intersect($suiteIds,$commandIds);
        $duplciates = array_merge($testsVsSuites,$testsVsCommands,$SuitesVsCommands);

        if( in_array($data['id'],$testIds) || in_array($data['id'],$commandIds) || in_array($data['id'],$SuitesVsCommands)){
            $duplciates[]=$data['id'];
        }
        if(count($duplciates) > 0){
            $this->addMessage(sprintf($this->translate('Duplicate Ids found: %s'), implode(",",$duplciates)));
            return false;
        }



        return true;
    }
    protected function validateCommands($data){
        $supportedCommands = SuiteHelper::supportedCommands();

        $unsupportedCommands=[];
        foreach($data['tests'] as $testKey=>$test){
            foreach ($test['commands'] as $commandKey=>$command){
                if(! in_array($command['command'],$supportedCommands)){
                    $unsupported[]=$command['command'];
                }
            }
        }
        if(count($unsupportedCommands) > 0){
            $this->addMessage($this->translate('The following commands are not supported: ').implode(",",$unsupportedCommands));
            return false;
        }
        return true;
    }

    /**
     *
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value): bool
    {
        $this->clearMessages();
        try {
            $data = json_decode($value,true);
        }catch (\Throwable $e){
            $this->addMessage($this->translate('The data is not a valid json'));
            return false;
        }

        $validStructure = $this->validateStructure($data);
        if (! $validStructure) {
            return false;
        }
        $commandsValid = $this->validateCommands($data);
        if (! $commandsValid) {
            return false;
        }

        return true;
    }

}
