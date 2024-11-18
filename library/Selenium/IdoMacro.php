<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Selenium;

use Exception;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Service;
use stdClass;

/**
 * Expand macros in string in the context of MonitoredObjects
 */
class IdoMacro
{
    /**
     * Known icinga macros
     *
     * @var array
     */
    private static $icingaMacros = array(
        'HOSTNAME'      => 'host_name',
        'HOSTADDRESS'   => 'host_address',
        'HOSTADDRESS6'  => 'host_address6',
        'SERVICEDESC'   => 'service_description',
        'host.name'     => 'host_name',
        'host.address'  => 'host_address',
        'host.address6' => 'host_address6',
        'service.description' => 'service_description',
        'service.name'  => 'service_description'
    );

    /**
     * Return the given string with macros being resolved
     *
     * @param   string                      $input      The string in which to look for macros
     * @param   MonitoredObject|stdClass    $object     The host or service used to resolve macros
     *
     * @return  string                                  The substituted or unchanged string
     */
    public static function resolveMacros($input, $object)
    {
        $matches = array();
        if (preg_match_all('@\$([^\$\s]+)\$@', $input, $matches)) {
            foreach ($matches[1] as $key => $value) {
                $newValue = self::resolveMacro($value, $object);
                if ($newValue !== $value) {
                    $input = str_replace($matches[0][$key], $newValue, $input);
                }
            }
        }

        return $input;
    }

    /**
     * Resolve a macro based on the given object
     *
     * @param   string                      $macro      The macro to resolve
     * @param   MonitoredObject|stdClass    $object     The object used to resolve the macro
     *
     * @return  string                                  The new value or the macro if it cannot be resolved
     */
    public static function resolveMacro($macro, $object)
    {
        if (isset(self::$icingaMacros[$macro]) && isset($object->{self::$icingaMacros[$macro]})) {
            return $object->{self::$icingaMacros[$macro]};
        }

        try {
            $value = $object->$macro;
        } catch (Exception $e) {
            $objectName = $object->getName();
            if ($object instanceof Service) {
                $objectName = $object->getHost()->getName() . '!' . $objectName;
            }

            $value = null;
            Logger::debug('Unable to resolve macro "%s" on object "%s". An error occured: %s', $macro, $objectName, $e);
        }
        if($value === null && strpos($macro,"host.vars.")!==false){
            $varToFetch= str_replace("host.vars.","",$macro);
            if($object->type == MonitoredObject::TYPE_HOST){
                $host = $object;
            }else{
                $host = $object->getHost();
            }
            $host->fetchHostVariables();

            try {
                $value = $host->customvarsWithOriginalNames[$varToFetch];
            }catch (\Throwable $e){
                $value = null;
                Logger::debug('Unable to resolve macro "%s" on object "%s". An error occured: %s', $macro, $objectName, $e);
            }
        }
        if($value === null && strpos($macro,"service.vars.")!==false){
            $varToFetch= str_replace("service.vars.","",$macro);
            if($object->type == MonitoredObject::TYPE_SERVICE){
                $service = $object;
            }else{
                Logger::debug('Unable to resolve macro "%s" on object "%s" because it is a service var request on a host object', $macro, $objectName);
            }
            $service->fetchServiceVariables();

            try {
                $value = $service->customvarsWithOriginalNames[$varToFetch];

            }catch (\Throwable $e){
                $value = null;
                Logger::debug('Unable to resolve macro "%s" on object "%s". An error occured: %s', $macro, $objectName, $e);
            }
        }
        return $value !== null ? $value : $macro;
    }
}
