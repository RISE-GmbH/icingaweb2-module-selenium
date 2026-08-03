<?php

// SPDX-FileCopyrightText: 2026 Research Industrial Systems Engineering (RISE) Forschungs-, Entwicklungs- und Großprojektberatung GmbH
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Selenium\Clicommands;


use Icinga\Application\Modules\Module;
use Icinga\Cli\Command;
use Icinga\Module\Selenium\BinaryHelper;


class InitCommand extends Command
{
    /**
     * USAGE:
     *
     *   icingacli selenium init
     */
    public function defaultAction()
    {
        if($this->params->get('with-commands') !== null){
            if($fieldCat = $this->params->get('field-category')){
                $this->Config('director')->setSection('datafield',['category_id']);
            }
        }

        $folders =[
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."binaries",
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."images",
            Module::get("selenium")->getConfigDir().DIRECTORY_SEPARATOR."downloads",
        ];
        foreach($folders as $folder){
            if(!file_exists($folder)){
                mkdir($folder,0755,true);
            }
        }
        $a = new BinaryHelper();
        if($a->update($this->params->get('driverversion'))){
            echo "Init was successful\n";
        }else{
            echo "Init failed\n";
        }


    }

}
