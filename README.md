Autopkg_api module
=================

The autopkg_api module is designed to be used with AutoPKG to allow it to automatically signal AutoPKG to build a new MunkiReport client PKG when a module or MunkiReport is updated. 

## How It Determines a New Version
The module does three checks to determine if it should signal to AutoPKG to create a new client PKG. 

The first is compare the current `MODULES` array in the .env file against the previous AutoPKG run. If it is different, signal to create a new client PKG. This check is for any changes to enabled/disabled modules.

The second is to generate a hash of all client scripts and compare that to the previous AutoPKG run. If it is different, signal to create a new client PKG. This is done to catch any updated client scripts within the modules.

The last is to compare the MunkiReport version with the previous AutoPKG run. If it is different, signal to create a new client PKG.

## How It Signals to AutoPKG
The module itself doesn't run anything, instead it generates a JSON file every time an API endpoint is called by AutoPKG that contains information for AutoPKG to ingest and work with. 

This API endpoint is `/index.php?/module/autopkg_api/get_autopkg_data_api` and it contains the following information in JSON format:

* `"modules_env":"applications,appusage,ard,bluetooth..."`
	* What modules are in the `MODULES` array in the .env
* `"script_hash":"366b8b7efa3067a9"`
	* The hash of all the client scripts in the modules
* `"last_rebuilt":1687523032`
	* Timestamp of when AutoPKG was last signaled to build a new client PKG
* `"last_polled":1772289712`
	* Timestamp of when AutoPKG last checked for a signal to build a new client PKG
* `"rebuild_pkg":"TRUE"`
	* If AutoPKG should build a new client PKG, `TRUE` or `FALSE`
* `"mr_version":"5.8.0.4284"`
	* Current MunkiReport version
* `"version":"5.8.0.4284.2023062301"`
	* New client PKG version. This version is created by taking the current MunkiReport version, appending YYYYMMDD after that, then adding an incrementing version at the end starting with `01`. This way, the versioning can be fully automated and will always go up. 

AutoPKG should look at `"version"` within the JSON to determine if it should create a new client PKG using its version logic and produce a new client PKG with that version. 

## Admin View
The module contains an admin page for administrators to view status of AutoPKG's most recent run and if there is a pending signal for AutoPKG to create a new client PKG. It also allows for an admin to force AutoPKG to create a new version.


## Table Schema
The module stores information about the previous AutoPKG run in a JSON file in the `cache` table. It does not have a table of its own. 
