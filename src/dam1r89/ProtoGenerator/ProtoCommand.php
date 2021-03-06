<?php

namespace dam1r89\ProtoGenerator;

use Illuminate\Console\Command;
use Illuminate\Foundation\Composer;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProtoCommand extends Command
{

    protected $name = 'proto';
    protected $description = 'Create model, views, controller, migration for a defined model. Example usage php `artisan proto user`';

    private function getContextData()
    {
        return json_decode($this->option('data'), true);
    }

    public function fire()
    {
        $contextData = $this->getContextData();

        $source = __DIR__ . '/templates/' . $this->option('template');
        $dest = base_path($this->option('output'));

        $this->info("Creating on path $dest from source $source");


        $parser = new ContextDataParser($this->argument('model'), json_decode($this->option('fields'), true));

        $additional['namespace'] = with(new ComposerParser(base_path('composer.json')))->getNamespace('App\\');

        $context = array_merge($parser->getContextData(), $additional, $contextData);

        $proto = Proto::create($source, $dest, $context);

        $existing = [];
        foreach ($proto->getFiles() as $file) {
           if (file_exists($file->dest)){
               $existing[] = substr($file->dest, strlen(base_path()) + 1);
           }
        }

        if (count($existing) === 0){
            $proto->generate();
            $this->info('Success');
        }
        else{
            $this->error('Files exists');
            $this->info(implode("\n", $existing));
            if ($this->confirm("Do you want to overwrite these files?")){
                $proto->generate(true);
            };
        }



    }



    protected function getArguments()
    {
        return array(
            array('model', InputArgument::REQUIRED, 'Model for which you want to generate prototype model, controller, views and migration.'),
        );
    }

    protected function getOptions()
    {
        return array(
            array('fields', 'f', InputOption::VALUE_OPTIONAL, 'Model properties separated by comma (id field is included). Example --fields="name,category,test"', '[]'),
            array('data', 'd', InputOption::VALUE_OPTIONAL, 'Additional data', '{}'),
            array('template', 't', InputOption::VALUE_OPTIONAL, 'Template path under the templates folder of source file', 'standard'),
            array('output', 'o', InputOption::VALUE_OPTIONAL, 'Output folder where file/folder structure will be generated, default is app', ''),
            array('override', 'r', InputOption::VALUE_NONE, 'Automatically override all')
        );
    }

}
