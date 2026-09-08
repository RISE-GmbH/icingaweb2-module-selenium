# Icinga Web 2 Selenium Module Changelog

## What's New

### What's New in Version 0.3.5
* downgraded "symfony/process": "^v7.4.18" 

### What's New in Version 0.3.4
* catch exception in DBMigrationHook

### What's New in Version 0.3.3
* composer dependecy update
* added checksource to testrun

### What's New in Version 0.3.2
* introduction to override_vars_file
* updated doc

### What's New in Version 0.3.1
* url check before open to not block chromedriver

### What's New in Version 0.3.0

* if else end elseif logic
* allow to disable the webdriver and driverversion IcingaWeb2 hooks
* improved restriction handling if user ist unrestricted

### What's New in Version 0.2.9

* improved run parameters

### What's New in Version 0.2.8

* get the closest chrome driver
* fixed activity log on newly created objects

### What's New in Version 0.2.7

* improved sigterm

### What's New in Version 0.2.6

* implement command verifyText
* allow to set a specific chromedriver version with
> icingacli selenium debian --driverversion 138.0.7201.94

### What's New in Version 0.2.5

* using usleep instead of sleep

### What's New in Version 0.2.4

* implementation of verifyText

