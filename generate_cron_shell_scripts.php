<?php

/**
 * Generate cron scripts
 */

require_once 'abstract.php';

class Generate_Cron_Scripts_Shell extends Mage_Shell_Abstract {
    public $baseDir;
    public $codeTemplate = <<<'TEMPLATE'
<?php 

/**
 * [[jobCode]] job
 * Cron job code - [[jobCode]]
 */
 
# Cron expression - [[jobExpression]]

require_once __DIR__.'/[[depth]]abstract.php';

class [[sentencedCaseJobCode]]_Shell extends Mage_Shell_Abstract {
    public function run()
    {
        try {
            Mage::log('Started - '.$this->jobCode, null, 'cronrunner.log');
            $job = Mage::getSingleton('[[jobModel]]');
            $job->[[jobModelMethod]]();
            Mage::log('Finished - '.$this->jobCode, null, 'cronrunner.log');
        } catch (Exception $e) {
            Mage::log($e, null, 'cronrunner_exceptions.log');
            echo $e->getMessage();
        }
    }
}

$shell = new [[sentencedCaseJobCode]]_Shell();
$shell->run();
TEMPLATE;

    public $crontabLineTemplate = <<<'TEMPLATE'
[[jobExpression]] /usr/bin/php -f [[cronRunnerPath]] [[pathToFile]] -d display_errors -d memory_limit=1024M
TEMPLATE;

    public function __construct()
    {
        parent::__construct();
        $this->baseDir = __DIR__.'/cronrunner/';
    }

    public function run()
    {
        try {
            $jobCollection = Mage::getConfig()->getNode('crontab/jobs')->asArray();
            $crontab = '';
            foreach ($jobCollection as $jobCode => $jobData) {
                $jobCodeWithSlashes = $this->baseDir.str_replace("_", "/", $jobCode).'/';
                $sentencedCaseJobCode = str_replace(" ", "_", ucwords(str_replace("_", " ", $jobCode)));
                $jobExpression = $jobData['schedule']['cron_expr'];
                $runModel = explode(":", $jobData['run']['model']);
                $jobModel = $runModel[0];
                $jobModelMethod = $runModel[2];


                $directories = explode("_", $jobCode);
                $depth = 2;
                $dirPath = $this->baseDir;
                foreach ($directories as $dir) {
                    $dirPath .= $dir.'/';
                    if (!file_exists($dirPath)) {
                        mkdir($dirPath, 0755, true);
                    }
                    if ($dirPath === $jobCodeWithSlashes) {
                        $absolutePathToFile = $dirPath.$jobCode.'.php';
                        $relativePathToFile = str_replace($this->baseDir, "", $dirPath.$jobCode.'.php');

                        $generatedTemplateCode = $this->fillTemplates(
                            array(
                                '[[jobCode]]',
                                '[[sentencedCaseJobCode]]',
                                '[[jobExpression]]',
                                '[[jobModel]]',
                                '[[jobModelMethod]]',
                                '[[depth]]',
                                '[[pathToFile]]',
                                '[[cronRunnerPath]]'
                            ),
                            array(
                                $jobCode,
                                $sentencedCaseJobCode,
                                $jobExpression,
                                $jobModel,
                                $jobModelMethod,
                                str_repeat("../", $depth),
                                $relativePathToFile,
                                $this->baseDir.'cronRunner.php',
                            ),
                            array(
                                'generatedCode'             => $this->codeTemplate,
                                'crontabLineTemplateCode'   => $this->crontabLineTemplate,
                            )
                        );

                        if (isset($generatedTemplateCode['generatedCode'])) {
                            // create shell script file here
                            $fp = fopen($absolutePathToFile, 'w+');
                            fwrite($fp, $generatedTemplateCode['generatedCode']);
                            fclose($fp);
                        }
                        if (isset($generatedTemplateCode['crontabLineTemplateCode'])) {
                            // skip if $jobExpression is null
                            if ($jobExpression === NULL) {
                                continue;
                            }
                            $crontab .= $generatedTemplateCode['crontabLineTemplateCode'] . "\n";
                        }
                    }
                    $depth += 1;
                }

            }

            // generate crontab file
            $fp = fopen($this->baseDir.'crontab.txt', 'w+');
            fwrite($fp, $crontab);
            fclose($fp);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function fillTemplates($search, $replace, $templates) {
        return str_replace($search, $replace, $templates);
    }
}

$shell = new Generate_Cron_Scripts_Shell();
$shell->run();